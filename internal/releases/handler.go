package releases

import (
	"database/sql"
	"fmt"
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
	mux.HandleFunc("GET /releases", h.index)
	mux.HandleFunc("GET /releases/{version}", h.show)
}

type releaseRow struct {
	Version       string
	Available     bool
	Info          string
	ReleaseDate   int64
	KernelVersion string
	FileLength    int64
	FileName      string
	SHA1Sum       string
	SHA256Sum     string
	B2Sum         string
	TorrentURL    string
	MagnetURI     string
}

func (r releaseRow) ISOUrl() string {
	if r.FileName == "" {
		return ""
	}
	return fmt.Sprintf("/download/iso/%s/%s", r.Version, r.FileName)
}

func (r releaseRow) ISOSigUrl() string {
	if r.FileName == "" {
		return ""
	}
	return fmt.Sprintf("/download/iso/%s/%s.sig", r.Version, r.FileName)
}

type releasesData struct {
	Releases []releaseRow
	Search   string
	Total    int
	Offset   int
	Limit    int
}

func (d releasesData) HasPrevious() bool { return d.Offset > 0 }
func (d releasesData) HasNext() bool     { return d.Offset+d.Limit < d.Total }
func (d releasesData) From() int         { return d.Offset + 1 }

func (d releasesData) To() int {
	to := d.Offset + d.Limit
	if to > d.Total {
		to = d.Total
	}
	return to
}

func (d releasesData) PrevOffset() int {
	o := d.Offset - d.Limit
	if o < 0 {
		o = 0
	}
	return o
}

func (d releasesData) NextOffset() int {
	return d.Offset + d.Limit
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search := r.URL.Query().Get("search")
	offset, _ := strconv.Atoi(r.URL.Query().Get("offset"))
	if offset < 0 {
		offset = 0
	}

	data := releasesData{
		Search: search,
		Limit:  defaultLimit,
		Offset: offset,
	}

	ctx := r.Context()
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		searchArg := "%" + search + "%"
		where := ` WHERE version LIKE ? OR info LIKE ? OR kernel_version LIKE ?`
		countQuery = `SELECT COUNT(*) FROM release` + where
		countArgs = []any{searchArg, searchArg, searchArg}

		dataQuery = `SELECT version, available, COALESCE(info, ''), COALESCE(release_date, 0), COALESCE(kernel_version, ''), COALESCE(file_length, 0), COALESCE(file_name, '')
			FROM release` + where + ` ORDER BY release_date DESC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, searchArg, defaultLimit, offset}
	} else {
		countQuery = `SELECT COUNT(*) FROM release`
		dataQuery = `SELECT version, available, COALESCE(info, ''), COALESCE(release_date, 0), COALESCE(kernel_version, ''), COALESCE(file_length, 0), COALESCE(file_name, '')
			FROM release ORDER BY release_date DESC LIMIT ? OFFSET ?`
		dataArgs = []any{defaultLimit, offset}
	}

	if err := h.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&data.Total); err != nil {
		layout.ServerError(w, "count releases", err)
		return
	}

	rows, err := h.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		layout.ServerError(w, "query releases", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var rel releaseRow
		if err := rows.Scan(&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion, &rel.FileLength, &rel.FileName); err != nil {
			layout.ServerError(w, "scan release", err)
			return
		}
		data.Releases = append(data.Releases, rel)
	}
	if err := rows.Err(); err != nil {
		layout.ServerError(w, "release rows", err)
		return
	}

	page := layout.Page{
		Title:       "Releases",
		Description: "Arch Linux Release-Archiv",
		Path:        "/releases",
		Manifest:    h.manifest,
		NoIndex:     data.Total == 0,
	}

	layout.Render(w, r, page, ReleaseList(data))
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")

	var rel releaseRow
	err := h.db.QueryRowContext(r.Context(),
		`SELECT version, available, COALESCE(info, ''), COALESCE(release_date, 0), COALESCE(kernel_version, ''),
		        COALESCE(file_length, 0), COALESCE(file_name, ''), COALESCE(sha1_sum, ''), COALESCE(sha256_sum, ''),
		        COALESCE(b2_sum, ''), COALESCE(torrent_url, ''), COALESCE(magnet_uri, '')
		 FROM release WHERE version = ?`, version).Scan(
		&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion,
		&rel.FileLength, &rel.FileName, &rel.SHA1Sum, &rel.SHA256Sum,
		&rel.B2Sum, &rel.TorrentURL, &rel.MagnetURI,
	)
	if err == sql.ErrNoRows {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "get release", err)
		return
	}

	page := layout.Page{
		Title:       "Arch Linux " + rel.Version,
		Description: "Arch Linux Release " + rel.Version,
		Path:        "/releases/" + version,
		Manifest:    h.manifest,
	}

	layout.Render(w, r, page, ReleaseDetail(rel))
}
