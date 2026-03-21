package packages

import (
	"net/http"
	"strconv"

	"www/internal/ui/layout"
)

const defaultLimit = 25

type Handler struct {
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages", h.index)
}

type packagesData struct {
	layout.Pagination
	Packages      []PackageSummary
	Search        string
	Repository    string
	Architecture  string
	Repositories  []string
	Architectures []string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	repo := r.URL.Query().Get("repository")
	arch := r.URL.Query().Get("architecture")
	offset, _ := strconv.Atoi(r.URL.Query().Get("offset"))
	if offset < 0 {
		offset = 0
	}

	ctx := r.Context()

	repos, err := h.repo.ListRepositoryNames(ctx)
	if err != nil {
		layout.ServerError(w, "list repositories", err)
		return
	}

	var archs []string
	if arch != "" {
		archs, err = h.repo.ListArchitectures(ctx)
		if err != nil {
			layout.ServerError(w, "list architectures", err)
			return
		}
	}

	pkgs, total, err := h.repo.Search(ctx, search, repo, arch, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search packages", err)
		return
	}

	data := packagesData{
		Pagination:    layout.Pagination{Total: total, Limit: defaultLimit, Offset: offset},
		Packages:      pkgs,
		Search:        search,
		Repository:    repo,
		Architecture:  arch,
		Repositories:  repos,
		Architectures: archs,
	}

	page := layout.Page{
		Title:       "Paket-Suche",
		Description: "Übersicht und Suche von Arch Linux-Paketen",
		Path:        "/packages",
		Manifest:    h.manifest,
		NoIndex:     total == 0,
	}

	layout.Render(w, r, page, PackageList(data))
}
