package packagedetail

import (
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"time"

	"www/internal/httperror"
	"www/internal/ui/layout"
)

func formatSize(b int64) string {
	const (
		kb = 1000
		mb = 1000 * kb
		gb = 1000 * mb
	)
	switch {
	case b >= gb:
		return fmt.Sprintf("%d GB", b/gb)
	case b >= mb:
		return fmt.Sprintf("%d MB", b/mb)
	case b >= kb:
		return fmt.Sprintf("%d kB", b/kb)
	default:
		return fmt.Sprintf("%d B", b)
	}
}

type Handler struct {
	repo           *Repository
	manifest       *layout.Manifest
	packagesMirror string
}

func NewHandler(repo *Repository, manifest *layout.Manifest, packagesMirror string) *Handler {
	return &Handler{repo: repo, manifest: manifest, packagesMirror: packagesMirror}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/{arch}/{name}", h.resolve)
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}", h.show)
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}/files", h.files)
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	repoName := r.PathValue("repo")
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")

	pkg, err := h.repo.FindByRepoArchName(r.Context(), repoName, arch, pkgName)
	if errors.Is(err, sql.ErrNoRows) {
		h.notFound(w, r, pkgName)
		return
	}
	if err != nil {
		layout.ServerError(w, "query package", err)
		return
	}

	var jsonLD map[string]any
	if !pkg.Testing {
		jsonLD = map[string]any{
			"@context":        "https://schema.org",
			"@type":           "SoftwareApplication",
			"name":            pkg.Name,
			"operatingSystem": "Arch Linux",
			"softwareVersion": pkg.Version,
			"description":     pkg.Description,
			"url":             pkg.URL,
			"dateModified":    time.Unix(pkg.BuildDate, 0).UTC().Format(time.RFC3339),
			"fileSize":        formatSize(pkg.CompressedSize),
			"offers":          map[string]any{"@type": "Offer", "price": "0", "priceCurrency": "EUR"},
		}
		if pkg.PopularityCount > 0 {
			jsonLD["aggregateRating"] = map[string]any{
				"@type":             "AggregateRating",
				"worstRating":       0,
				"bestRating":        100, //nolint:mnd // popularity scale 0-100
				"ratingCount":       pkg.PopularityCount,
				"ratingValue":       pkg.Popularity,
				"ratingExplanation": fmt.Sprintf("The package %s got %d out of %d votes submitted to pkgstats.", pkg.Name, pkg.PopularityCount, pkg.PopularitySamples),
				"url":               "https://pkgstats.archlinux.de/packages/" + pkg.Name,
			}
		}
	}
	page := layout.Page{
		Title:       pkg.Name,
		Description: pkg.Description,
		Path:        r.URL.Path,
		Manifest:    h.manifest,
		NoIndex:     pkg.Testing,
		JsonLD:      jsonLD,
	}

	layout.Render(w, r, page, PackageDetailPage(pkg, h.packagesMirror))
}

func (h *Handler) resolve(w http.ResponseWriter, r *http.Request) {
	arch := r.PathValue("arch")
	name := r.PathValue("name")
	version := r.URL.Query().Get("v")
	constraint := r.URL.Query().Get("c")

	results := h.repo.Resolve(r.Context(), arch, name, version, constraint, false)
	if len(results) == 0 {
		http.NotFound(w, r)
		return
	}
	if len(results) == 1 {
		http.Redirect(w, r, fmt.Sprintf("/packages/%s/%s/%s", results[0].Repository, results[0].Arch, results[0].Name), http.StatusTemporaryRedirect)
		return
	}

	page := layout.Page{
		Title:    name,
		Path:     r.URL.Path,
		Manifest: h.manifest,
	}
	layout.Render(w, r, page, PackageResolvePage(name, results))
}

const suggestLimit = 5

func (h *Handler) notFound(w http.ResponseWriter, r *http.Request, name string) {
	suggestions := h.repo.Suggest(r.Context(), name, suggestLimit)

	page := layout.Page{
		Title:    name,
		Path:     r.URL.Path,
		Manifest: h.manifest,
		NoIndex:  true,
	}

	httperror.SkipIntercept(w)
	w.WriteHeader(http.StatusNotFound)
	layout.Render(w, r, page, PackageNotFoundPage(name, suggestions))
}

func (h *Handler) files(w http.ResponseWriter, r *http.Request) {
	repoName := r.PathValue("repo")
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")

	files := h.repo.LoadFiles(r.Context(), repoName, arch, pkgName)

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(files)
}
