package mirrors

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
	mux.HandleFunc("GET /mirrors", h.index)
}

type mirrorsData struct {
	Mirrors []Mirror
	Search  string
	Total   int
	Offset  int
	Limit   int
}

func (d mirrorsData) HasPrevious() bool { return d.Offset > 0 }
func (d mirrorsData) HasNext() bool     { return d.Offset+d.Limit < d.Total }
func (d mirrorsData) From() int         { return d.Offset + 1 }

func (d mirrorsData) To() int {
	to := d.Offset + d.Limit
	if to > d.Total {
		to = d.Total
	}
	return to
}

func (d mirrorsData) PrevOffset() int {
	o := d.Offset - d.Limit
	if o < 0 {
		o = 0
	}
	return o
}

func (d mirrorsData) NextOffset() int {
	return d.Offset + d.Limit
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	offset, _ := strconv.Atoi(r.URL.Query().Get("offset"))
	if offset < 0 {
		offset = 0
	}

	items, total, err := h.repo.Search(r.Context(), search, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search mirrors", err)
		return
	}

	data := mirrorsData{
		Mirrors: items,
		Search:  search,
		Total:   total,
		Limit:   defaultLimit,
		Offset:  offset,
	}

	page := layout.Page{
		Title:       "Mirror-Status",
		Description: "Paket-Mirror Arch Linux",
		Path:        "/mirrors",
		Manifest:    h.manifest,
		NoIndex:     total == 0,
	}

	layout.Render(w, r, page, MirrorList(data))
}
