package mirrors

import (
	"net/http"
	"strconv"

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
	mux.HandleFunc("GET /mirrors", h.index)
}

type mirrorsData struct {
	layout.Pagination
	Mirrors []Mirror
	Search  string
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
		Pagination: layout.Pagination{Total: total, Limit: defaultLimit, Offset: offset},
		Mirrors:    items,
		Search:     search,
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
