package packages

import (
	"database/sql"
	"math"
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
	mux.HandleFunc("GET /packages", h.index)
}

type packageRow struct {
	Repository   string
	Architecture string
	Name         string
	Version      string
	Description  string
	BuildDate    int64
}

type packagesData struct {
	Packages     []packageRow
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
	offsetStr := r.URL.Query().Get("offset")
	offset, _ := strconv.Atoi(offsetStr)
	if offset < 0 {
		offset = 0
	}

	data := packagesData{
		Search:     search,
		Repository: repo,
		Limit:      defaultLimit,
		Offset:     offset,
	}

	repos, err := h.listRepositories(r)
	if err != nil {
		layout.ServerError(w, "list repositories", err)
		return
	}
	data.Repositories = repos

	total, pkgs, err := h.searchPackages(r, search, repo, offset, defaultLimit)
	if err != nil {
		layout.ServerError(w, "search packages", err)
		return
	}
	data.Total = total
	data.Packages = pkgs

	page := layout.Page{
		Title:       "Paket-Suche",
		Description: "Übersicht und Suche von Arch Linux-Paketen",
		Path:        "/packages",
		Manifest:    h.manifest,
		NoIndex:     total == 0,
	}

	layout.Render(w, r, page, PackageList(data))
}

func (h *Handler) listRepositories(r *http.Request) ([]string, error) {
	rows, err := h.db.QueryContext(r.Context(),
		`SELECT DISTINCT name FROM repository WHERE testing = 0 ORDER BY name`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var repos []string
	for rows.Next() {
		var name string
		if err := rows.Scan(&name); err != nil {
			return nil, err
		}
		repos = append(repos, name)
	}
	return repos, rows.Err()
}

func (h *Handler) searchPackages(r *http.Request, search, repo string, offset, limit int) (int, []packageRow, error) {
	ctx := r.Context()

	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		// FTS5 search
		ftsSearch := search + "*"
		baseWhere := `FROM package p
			JOIN package_fts fts ON fts.rowid = p.id
			JOIN repository r ON r.id = p.repository_id
			WHERE package_fts MATCH ?`
		countArgs = []any{ftsSearch}
		dataArgs = []any{ftsSearch}

		if repo != "" {
			baseWhere += ` AND r.name = ?`
			countArgs = append(countArgs, repo)
			dataArgs = append(dataArgs, repo)
		}

		countQuery = `SELECT COUNT(*) ` + baseWhere
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, COALESCE(p.description, ''), COALESCE(p.build_date, 0)
			` + baseWhere + ` ORDER BY rank, p.popularity_recent DESC, p.build_date DESC LIMIT ? OFFSET ?`
		dataArgs = append(dataArgs, limit, offset)
	} else {
		baseWhere := `FROM package p
			JOIN repository r ON r.id = p.repository_id
			WHERE 1=1`
		if repo != "" {
			baseWhere += ` AND r.name = ?`
			countArgs = append(countArgs, repo)
			dataArgs = append(dataArgs, repo)
		}

		countQuery = `SELECT COUNT(*) ` + baseWhere
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, COALESCE(p.description, ''), COALESCE(p.build_date, 0)
			` + baseWhere + ` ORDER BY p.build_date DESC LIMIT ? OFFSET ?`
		dataArgs = append(dataArgs, limit, offset)
	}

	var total int
	if err := h.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&total); err != nil {
		return 0, nil, err
	}

	rows, err := h.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		return 0, nil, err
	}
	defer rows.Close()

	var pkgs []packageRow
	for rows.Next() {
		var p packageRow
		if err := rows.Scan(&p.Repository, &p.Architecture, &p.Name, &p.Version, &p.Description, &p.BuildDate); err != nil {
			return 0, nil, err
		}
		pkgs = append(pkgs, p)
	}

	return total, pkgs, rows.Err()
}
