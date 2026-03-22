package packagedetail

import (
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"net/http"
	"time"

	"archded/internal/ui/httperror"
	"archded/internal/ui/layout"
)

type Handler struct {
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/{arch}/{name}", h.resolve)
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}", h.show)
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}/files", h.files)
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}/inverse-dependencies", h.inverseDeps)
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
		app := map[string]any{
			"@context":        "https://schema.org",
			"@type":           "SoftwareApplication",
			"name":            pkg.Name,
			"operatingSystem": "Arch Linux",
			"softwareVersion": pkg.Version,
			"description":     pkg.Description,
			"url":             pkg.URL,
			"dateModified":    time.Unix(pkg.BuildDate, 0).UTC().Format(time.RFC3339),
			"fileSize":        layout.FormatSize(pkg.CompressedSize),
			"offers":          map[string]any{"@type": "Offer", "price": "0", "priceCurrency": "EUR"},
		}
		if pkg.PopularityCount > 0 {
			app["aggregateRating"] = map[string]any{
				"@type":             "AggregateRating",
				"worstRating":       0,
				"bestRating":        100, //nolint:mnd // popularity scale 0-100
				"ratingCount":       pkg.PopularityCount,
				"ratingValue":       pkg.Popularity,
				"ratingExplanation": fmt.Sprintf("The package %s got %d out of %d votes submitted to pkgstats.", pkg.Name, pkg.PopularityCount, pkg.PopularitySamples),
				"url":               "https://pkgstats.archlinux.de/packages/" + pkg.Name,
			}
		}
		jsonLD = map[string]any{"package": app}
	}
	page := layout.Page{
		Title:       pkg.Name,
		Description: pkg.Description,
		Path:        r.URL.Path,
		Manifest:    h.manifest,
		NoIndex:     pkg.Testing,
		JsonLD:      jsonLD,
	}

	layout.Render(w, r, page, PackageDetailPage(pkg))
}

func (h *Handler) resolve(w http.ResponseWriter, r *http.Request) {
	arch := r.PathValue("arch")
	name := r.PathValue("name")
	version := r.URL.Query().Get("v")
	constraint := r.URL.Query().Get("c")

	results := h.repo.Resolve(r.Context(), arch, name, version, constraint)
	if len(results) == 0 {
		h.notFound(w, r, name)
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

func (h *Handler) inverseDeps(w http.ResponseWriter, r *http.Request) {
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")

	rels := h.repo.LoadInverseRelations(r.Context(), pkgName, arch)

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(rels)
}

func (h *Handler) files(w http.ResponseWriter, r *http.Request) {
	repoName := r.PathValue("repo")
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")

	files := h.repo.LoadFiles(r.Context(), repoName, arch, pkgName)

	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(files)
}
