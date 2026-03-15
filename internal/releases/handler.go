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
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /releases", h.index)
	mux.HandleFunc("GET /releases/{version}", h.show)
}

func (r Release) ISOUrl() string {
	if r.FileName == "" {
		return ""
	}
	return fmt.Sprintf("/download/iso/%s/%s", r.Version, r.FileName)
}

func (r Release) ISOSigUrl() string {
	if r.FileName == "" {
		return ""
	}
	return fmt.Sprintf("/download/iso/%s/%s.sig", r.Version, r.FileName)
}

type releasesData struct {
	Releases []Release
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

	rels, total, err := h.repo.Search(r.Context(), search, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search releases", err)
		return
	}

	data := releasesData{
		Releases: rels,
		Search:   search,
		Total:    total,
		Limit:    defaultLimit,
		Offset:   offset,
	}

	page := layout.Page{
		Title:       "Releases",
		Description: "Arch Linux Release-Archiv",
		Path:        "/releases",
		Manifest:    h.manifest,
		NoIndex:     total == 0,
	}

	layout.Render(w, r, page, ReleaseList(data))
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")

	rel, err := h.repo.FindByVersion(r.Context(), version)
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
