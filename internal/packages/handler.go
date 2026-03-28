package packages

import (
	"encoding/json"
	"log/slog"
	"net/http"

	"archded/internal/ui/layout"
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
	mux.HandleFunc("GET /packages/suggest", h.suggest)
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

const (
	suggestLimit   = 10
	suggestTermMax = 255
)

func (h *Handler) suggest(w http.ResponseWriter, r *http.Request) {
	term := r.URL.Query().Get("term")
	if len(term) > suggestTermMax {
		term = term[:suggestTermMax]
	}

	names, err := h.repo.Suggest(r.Context(), term, suggestLimit)
	if err != nil {
		http.Error(w, "suggest failed", http.StatusInternalServerError)
		return
	}
	if names == nil {
		names = []string{}
	}

	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(names); err != nil {
		slog.Error("encode suggestions", "error", err)
	}
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search, offset := layout.ParseSearchParams(r)
	repo := r.URL.Query().Get("repository")
	arch := r.URL.Query().Get("architecture")

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
