package news

import (
	"database/sql"
	"net/http"
	"strconv"

	"www/internal/ui/layout"
)

const defaultLimit = 25

type Handler struct {
	db       *sql.DB
	manifest *layout.Manifest
}

func NewHandler(db *sql.DB, manifest *layout.Manifest) *Handler {
	return &Handler{db: db, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /news", h.index)
	mux.HandleFunc("GET /news/{id}", h.show)
}

type newsRow struct {
	ID           int
	Title        string
	Link         string
	Description  string
	AuthorName   string
	AuthorLink   string
	LastModified int64
}

type newsData struct {
	Items  []newsRow
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

	data := newsData{
		Search: search,
		Limit:  defaultLimit,
		Offset: offset,
	}

	ctx := r.Context()
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		countQuery = `SELECT COUNT(*) FROM news_item WHERE title LIKE ? OR description LIKE ?`
		searchArg := "%" + search + "%"
		countArgs = []any{searchArg, searchArg}

		dataQuery = `SELECT id, title, link, COALESCE(description, ''), COALESCE(author_name, ''), COALESCE(author_link, ''), last_modified
			FROM news_item WHERE title LIKE ? OR description LIKE ?
			ORDER BY last_modified DESC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, defaultLimit, offset}
	} else {
		countQuery = `SELECT COUNT(*) FROM news_item`
		dataQuery = `SELECT id, title, link, COALESCE(description, ''), COALESCE(author_name, ''), COALESCE(author_link, ''), last_modified
			FROM news_item ORDER BY last_modified DESC LIMIT ? OFFSET ?`
		dataArgs = []any{defaultLimit, offset}
	}

	if err := h.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&data.Total); err != nil {
		layout.ServerError(w, "count news", err)
		return
	}

	rows, err := h.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		layout.ServerError(w, "query news", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var item newsRow
		if err := rows.Scan(&item.ID, &item.Title, &item.Link, &item.Description, &item.AuthorName, &item.AuthorLink, &item.LastModified); err != nil {
			layout.ServerError(w, "scan news", err)
			return
		}
		data.Items = append(data.Items, item)
	}
	if err := rows.Err(); err != nil {
		layout.ServerError(w, "news rows", err)
		return
	}

	page := layout.Page{
		Title:       "Neuigkeiten",
		Description: "Neuigkeiten und Mitteilungen zu Arch Linux",
		Path:        "/news",
		Manifest:    h.manifest,
		NoIndex:     data.Total == 0,
	}

	layout.Render(w, r, page, NewsList(data))
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	idStr := r.PathValue("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		http.NotFound(w, r)
		return
	}

	var item newsRow
	err = h.db.QueryRowContext(r.Context(),
		`SELECT id, title, link, COALESCE(description, ''), COALESCE(author_name, ''), COALESCE(author_link, ''), last_modified
		 FROM news_item WHERE id = ?`, id).Scan(
		&item.ID, &item.Title, &item.Link, &item.Description,
		&item.AuthorName, &item.AuthorLink, &item.LastModified,
	)
	if err == sql.ErrNoRows {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "get news item", err)
		return
	}

	page := layout.Page{
		Title:       item.Title,
		Description: truncate(item.Title, 100),
		Path:        "/news/" + idStr,
		Manifest:    h.manifest,
	}

	layout.Render(w, r, page, NewsDetail(item))
}

func truncate(s string, maxLen int) string {
	if len(s) <= maxLen {
		return s
	}
	return s[:maxLen]
}
