# Architecture Guide

Single Go binary (`archded`) serving a server-rendered HTML website. SQLite database. No ORM — raw `database/sql`.

## High-Level Structure

```
main.go                  — wiring: config → DB → manifest → handlers → middleware → server
internal/
  config/                — env-based config (DATABASE, PORT, PACKAGES_MIRROR, DEFAULT_MIRROR)
  database/              — SQLite setup, auto-migrations (golang-migrate)
  web/                   — HTTP server, middleware stack (recovery, secure headers, cache control)
  packages/              — package search/listing, FTS5-backed search
  packagedetail/         — individual package pages with deps, files, inverse deps
  news/                  — news from forum.archlinux.de (Flarum API)
  mirrors/               — mirror listing with popularity data
  releases/              — Arch Linux release information
  download/              — ISO download page, redirects to configured mirror
  popularity/            — fetches package/mirror popularity from pkgstats.archlinux.de
  home/                  — homepage (recent news + packages)
  feeds/                 — Atom feeds (/news/feed, /packages/feed, /releases/feed)
  sitemap/               — /sitemap.xml
  opensearch/            — /packages/opensearch (OpenSearch descriptor)
  legacy/                — redirects for old Symfony/Vue SPA URLs
  legal/                 — privacy policy, impressum
  sanitize/              — HTML sanitization for news content
  pacmandb/              — parses pacman .files database archives
  vercmp/                — libalpm version comparison (ported from pacman)
  ui/                    — route registration, layout templates, error pages, icon components
```

## Database

Single SQLite file. Migrations are embedded SQL files run automatically on startup via `golang-migrate`. Schema is still evolving — `000001_initial.up.sql` is modified directly instead of adding new migrations.

Key indexes: FTS5 virtual table for package search (name, base, description, groups, provides), plus standard B-tree indexes on foreign keys and lookup columns.

## Data Updates

Six CLI subcommands fetch data from external sources, invoked by external systemd timers:

| Command | Source | Notes |
|---------|--------|-------|
| `update-packages` | Arch mirror `.files` DBs | 6 repos concurrent, SHA256 change detection, FTS rebuild after |
| `update-news` | forum.archlinux.de Flarum API | Paginated, HTML sanitized |
| `update-mirrors` | archlinux.org/mirrors/status/json/ | Filtered by active/HTTPS/completion |
| `update-releases` | archlinux.org/releng/releases/json/ | ISO URLs, checksums, torrent info |
| `update-package-popularities` | pkgstats.archlinux.de/api/packages | Paginated (10k/request) |
| `update-mirror-popularities` | pkgstats.archlinux.de/api/mirrors | Paginated (10k/request) |

## Search

FTS5 indexes: name, base, description, groups, provides (denormalized from package relations). Hyphenated queries are split into individual terms for tokenizer compatibility.

Ranking: exact name match first, then `bm25(10,5,1,1,3) - ln(1+popularity)` — name-weighted with log-scaled popularity boost.

## Middleware Stack

Applied in `main.go` via `web.Chain()` (first = outermost): panic recovery, secure headers (CSP, nosniff, referrer-policy), HTML error page interception, cache control, legacy URL redirects.

Error interception renders styled HTML error pages for browser requests while passing API/non-HTML errors through unchanged. Handlers can call `httperror.SkipIntercept(w)` to render their own custom error pages (e.g. package-not-found with search suggestions).

## UI

Server-rendered HTML using [templ](https://templ.guide/). Each domain package has its own `handler.go` and `templates.templ`. Routes are registered in `internal/ui/routes.go`. German UI text throughout.

Two [Custom Elements](https://developer.mozilla.org/en-US/docs/Web/API/Web_components/Using_custom_elements) for lazy-loading heavy content:
- `<package-files>` — button-triggered file list loading
- `<package-inverse-deps>` — button-triggered inverse dependency loading (glibc has 5000+)

Frontend assets (SCSS/TypeScript) built with [Vite](https://vite.dev/). Styling uses [Bootstrap](https://getbootstrap.com/) + SCSS with PurgeCSS. Vite emits hashed assets to `dist/assets/` and a `dist/manifest.json` that the layout uses to inject `<script>` and `<link>` tags.

Build tags control compile-time behavior: `production` and `development` both embed real Vite assets. Without either tag (tests), stub embeds are used.

## Dev Workflow (`justfile`)

Run `just --list` for available commands. Key ones: `init`, `build`, `run`, `dev`, `test`, `lint`, `fmt`.

Key env vars: `DATABASE` (required), `PORT` (default 8080), `PACKAGES_MIRROR`, `DEFAULT_MIRROR`. See `internal/config/config.go`.

## Patterns to Know

- **Route registration**: every handler implements `RegisterRoutes(*http.ServeMux)`.
- **No framework**: stdlib `net/http` + `http.NewServeMux()` throughout.
- **Logging**: `log/slog` (structured). JSON in production, text in dev.
- **Bootstrap icons**: copied from node_modules via `go:generate`, embedded via `go:embed`.
- **Forward deps**: always server-rendered (max ~170 items). Inverse deps: lazy-loaded via JSON endpoint + custom element.
- **Version comparison**: `internal/vercmp/` for dependency resolution, matching libalpm behavior.
