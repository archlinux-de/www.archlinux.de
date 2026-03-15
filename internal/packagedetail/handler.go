package packagedetail

import (
	"database/sql"
	"net/http"

	"www/internal/ui/layout"
)

type Handler struct {
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}", h.show)
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	repoName := r.PathValue("repo")
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")

	pkg, err := h.repo.FindByRepoArchName(r.Context(), repoName, arch, pkgName)
	if err == sql.ErrNoRows {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "query package", err)
		return
	}

	page := layout.Page{
		Title:       pkg.Name,
		Description: pkg.Description,
		Path:        r.URL.Path,
		Manifest:    h.manifest,
		NoIndex:     pkg.Testing,
	}

	layout.Render(w, r, page, PackageDetailPage(pkg))
}
