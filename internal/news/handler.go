package news

import (
	"database/sql"
	"errors"
	"net/http"
	"strconv"
	"strings"
	"time"

	"archded/internal/ui/layout"
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
	layout.Pagination
	Items  []NewsItem
	Search string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search, offset := layout.ParseSearchParams(r)

	items, total, err := h.repo.Search(r.Context(), search, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search news", err)
		return
	}

	data := newsData{
		Pagination: layout.Pagination{Total: total, Limit: defaultLimit, Offset: offset},
		Items:      items,
		Search:     search,
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

	article := map[string]any{
		"@context":      "https://schema.org",
		"@type":         "NewsArticle",
		"headline":      item.Title,
		"datePublished": time.Unix(item.LastModified, 0).UTC().Format(time.RFC3339),
		"discussionUrl": item.Link,
	}
	if item.AuthorName != "" {
		article["author"] = []map[string]any{{
			"@type": "Person",
			"name":  item.AuthorName,
			"url":   item.AuthorLink,
		}}
	}
	page := layout.Page{
		Title:       item.Title,
		Description: truncate(item.Title, maxDescriptionLen),
		Path:        item.URL(),
		Manifest:    h.manifest,
		JsonLD:      map[string]any{"article": article},
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
