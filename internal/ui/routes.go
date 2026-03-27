package ui

import (
	"database/sql"
	"fmt"
	"io/fs"
	"net/http"
	"strings"

	"archded/internal/download"
	"archded/internal/feeds"
	"archded/internal/home"
	"archded/internal/legacy"
	"archded/internal/legal"
	"archded/internal/mirrors"
	"archded/internal/news"
	"archded/internal/opensearch"
	"archded/internal/packagedetail"
	"archded/internal/packages"
	"archded/internal/planet"
	"archded/internal/releases"
	"archded/internal/sitemap"
	"archded/internal/ui/layout"
)

const (
	assetsCacheMaxAge = 31536000 // 1 year
	staticCacheMaxAge = 86400    // 1 day
)

func RegisterRoutes(
	mux *http.ServeMux,
	manifest *layout.Manifest,
	db *sql.DB,
	defaultMirror string,
	assets, static, root fs.FS,
) {
	newsRepo := news.NewRepository(db)
	pkgRepo := packages.NewRepository(db)
	pkgDetailRepo := packagedetail.NewRepository(db)
	relRepo := releases.NewRepository(db)
	mirRepo := mirrors.NewRepository(db)

	home.NewHandler(newsRepo, pkgRepo, manifest).RegisterRoutes(mux)
	legal.NewHandler(manifest).RegisterRoutes(mux)
	packages.NewHandler(pkgRepo, manifest).RegisterRoutes(mux)
	packagedetail.NewHandler(pkgDetailRepo, manifest).RegisterRoutes(mux)
	news.NewHandler(newsRepo, manifest).RegisterRoutes(mux)
	mirrors.NewHandler(mirRepo, manifest).RegisterRoutes(mux)
	releases.NewHandler(relRepo, manifest).RegisterRoutes(mux)
	download.NewHandler(relRepo, manifest, defaultMirror).RegisterRoutes(mux)
	feeds.NewHandler(newsRepo, pkgRepo, relRepo).RegisterRoutes(mux)
	planet.NewHandler(planet.NewRepository(db), manifest).RegisterRoutes(mux)
	sitemap.NewHandler(newsRepo, pkgRepo, relRepo).RegisterRoutes(mux)
	opensearch.RegisterRoutes(mux)
	legacy.RegisterRoutes(mux)
	handleAssets(mux, assets)
	handleStatic(mux, static)
	handleFavicon(mux, root)
	handleManifest(mux, root)
	handleRobots(mux, root)
	handleServiceWorker(mux, root)
}

func handleFavicon(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /favicon.ico", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.ServeFileFS(w, r, root, "root/favicon.ico")
	}), staticCacheMaxAge))
}

func handleManifest(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /manifest.webmanifest", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/manifest+json")
		http.ServeFileFS(w, r, root, "root/manifest.webmanifest")
	}), staticCacheMaxAge))
}

func handleServiceWorker(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /service-worker.js", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.ServeFileFS(w, r, root, "root/service-worker.js")
	}), staticCacheMaxAge))
}

func handleRobots(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /robots.txt", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.ServeFileFS(w, r, root, "root/robots.txt")
	}), staticCacheMaxAge))
}

func handleAssets(mux *http.ServeMux, assets fs.FS) {
	sub, err := fs.Sub(assets, "dist/assets")
	if err != nil {
		panic(err)
	}
	fileServer := http.FileServer(http.FS(sub))
	mux.Handle("GET /assets/", http.StripPrefix("/assets/", cacheHandler(fileServer, assetsCacheMaxAge, "immutable")))
}

func handleStatic(mux *http.ServeMux, static fs.FS) {
	sub, err := fs.Sub(static, "static")
	if err != nil {
		panic(err)
	}
	fileServer := http.FileServer(http.FS(sub))
	mux.Handle("GET /static/", http.StripPrefix("/static/", cacheHandler(fileServer, staticCacheMaxAge)))
}

func cacheHandler(next http.Handler, maxAge int, directives ...string) http.Handler {
	value := fmt.Sprintf("public, max-age=%d", maxAge)
	if len(directives) > 0 {
		value += ", " + strings.Join(directives, ", ")
	}
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Cache-Control", value)
		next.ServeHTTP(w, r)
	})
}
