package packages

import (
	"math"
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
	Packages     []PackageSummary
	Search       string
	Repository   string
	Repositories []string
	Total        int
	Offset       int
	Limit        int
}

func (d packagesData) HasPrevious() bool { return d.Offset > 0 }
func (d packagesData) HasNext() bool     { return d.Offset+d.Limit < d.Total }
func (d packagesData) From() int         { return d.Offset + 1 }

func (d packagesData) To() int {
	to := d.Offset + d.Limit
	if to > d.Total {
		to = d.Total
	}
	return to
}

func (d packagesData) PrevOffset() int {
	o := d.Offset - d.Limit
	if o < 0 {
		o = 0
	}
	return o
}

func (d packagesData) NextOffset() int {
	return d.Offset + d.Limit
}

func (d packagesData) TotalPages() int {
	return int(math.Ceil(float64(d.Total) / float64(d.Limit)))
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	repo := r.URL.Query().Get("repository")
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

	pkgs, total, err := h.repo.Search(ctx, search, repo, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search packages", err)
		return
	}

	data := packagesData{
		Packages:     pkgs,
		Search:       search,
		Repository:   repo,
		Repositories: repos,
		Total:        total,
		Limit:        defaultLimit,
		Offset:       offset,
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
