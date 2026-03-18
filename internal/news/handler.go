package news

import (
	"database/sql"
	"errors"
	"net/http"
	"strconv"
	"strings"

	"www/internal/ui/layout"
)

const (
	defaultLimit      = 25
	maxDescriptionLen = 100
)

type Handler struct {
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /news", h.index)
	mux.HandleFunc("GET /news/{id}", h.show)
}

type newsData struct {
	Items  []NewsItem
	Search string
	Total  int
	Offset int
	Limit  int
}

func (d newsData) HasPrevious() bool { return d.Offset > 0 }
func (d newsData) HasNext() bool     { return d.Offset+d.Limit < d.Total }
func (d newsData) From() int         { return d.Offset + 1 }

func (d newsData) To() int {
	to := d.Offset + d.Limit
	if to > d.Total {
		to = d.Total
	}
	return to
}

func (d newsData) PrevOffset() int {
	o := d.Offset - d.Limit
	if o < 0 {
		o = 0
	}
	return o
}

func (d newsData) NextOffset() int {
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
		layout.ServerError(w, "search news", err)
		return
	}

	data := newsData{
		Items:  items,
		Search: search,
		Total:  total,
		Limit:  defaultLimit,
		Offset: offset,
	}

	page := layout.Page{
		Title:       "Neuigkeiten",
		Description: "Neuigkeiten und Mitteilungen zu Arch Linux",
		Path:        "/news",
		Manifest:    h.manifest,
		NoIndex:     total == 0,
	}

	layout.Render(w, r, page, NewsList(data))
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	idStr := r.PathValue("id")
	if i := strings.IndexByte(idStr, '-'); i > 0 {
		idStr = idStr[:i]
	}

	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.NotFound(w, r)
		return
	}

	item, err := h.repo.FindByID(r.Context(), id)
	if errors.Is(err, sql.ErrNoRows) {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "get news item", err)
		return
	}

	page := layout.Page{
		Title:       item.Title,
		Description: truncate(item.Title, maxDescriptionLen),
		Path:        item.URL(),
		Manifest:    h.manifest,
	}

	layout.Render(w, r, page, NewsDetail(item))
}

func truncate(s string, maxLen int) string {
	r := []rune(s)
	if len(r) <= maxLen {
		return s
	}
	return string(r[:maxLen])
}
