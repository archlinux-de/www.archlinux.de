package mirrors

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
	mux.HandleFunc("GET /mirrors", h.index)
}

type mirrorRow struct {
	URL         string
	CountryName string
	DurationAvg float64
	Delay       int
	LastSync    int64
	IPv4        bool
	IPv6        bool
}

type mirrorsData struct {
	Mirrors []mirrorRow
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

	data := mirrorsData{
		Search: search,
		Limit:  defaultLimit,
		Offset: offset,
	}

	ctx := r.Context()
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	baseFrom := `FROM mirror m LEFT JOIN country c ON c.code = m.country_code`

	if search != "" {
		searchArg := "%" + search + "%"
		where := ` WHERE m.url LIKE ? OR c.name LIKE ?`
		countQuery = `SELECT COUNT(*) ` + baseFrom + where
		countArgs = []any{searchArg, searchArg}

		dataQuery = `SELECT m.url, COALESCE(c.name, ''), COALESCE(m.duration_avg, 0), COALESCE(m.delay, 0), COALESCE(m.last_sync, 0), m.ipv4, m.ipv6 ` +
			baseFrom + where + ` ORDER BY m.score ASC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, defaultLimit, offset}
	} else {
		countQuery = `SELECT COUNT(*) ` + baseFrom
		dataQuery = `SELECT m.url, COALESCE(c.name, ''), COALESCE(m.duration_avg, 0), COALESCE(m.delay, 0), COALESCE(m.last_sync, 0), m.ipv4, m.ipv6 ` +
			baseFrom + ` ORDER BY m.score ASC LIMIT ? OFFSET ?`
		dataArgs = []any{defaultLimit, offset}
	}

	if err := h.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&data.Total); err != nil {
		layout.ServerError(w, "count mirrors", err)
		return
	}

	rows, err := h.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		layout.ServerError(w, "query mirrors", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var m mirrorRow
		if err := rows.Scan(&m.URL, &m.CountryName, &m.DurationAvg, &m.Delay, &m.LastSync, &m.IPv4, &m.IPv6); err != nil {
			layout.ServerError(w, "scan mirror", err)
			return
		}
		data.Mirrors = append(data.Mirrors, m)
	}
	if err := rows.Err(); err != nil {
		layout.ServerError(w, "mirror rows", err)
		return
	}

	page := layout.Page{
		Title:       "Mirror-Status",
		Description: "Paket-Mirror Arch Linux",
		Path:        "/mirrors",
		Manifest:    h.manifest,
		NoIndex:     data.Total == 0,
	}

	layout.Render(w, r, page, MirrorList(data))
}
