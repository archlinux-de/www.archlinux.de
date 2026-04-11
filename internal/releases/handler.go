package releases

import (
	"database/sql"
	"errors"
	"fmt"
	"net/http"

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
	if r.FileName == "" || r.ReleaseDate < 1342310400 { // 2012-07-15
		return ""
	}
	return fmt.Sprintf("/download/iso/%s/%s.sig", r.Version, r.FileName)
}

func (r Release) DirectoryURL() string {
	return fmt.Sprintf("/download/iso/%s/", r.Version)
}

type releasesData struct {
	layout.Pagination
	Releases []Release
	Search   string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	search, offset := layout.ParseSearchParams(r)

	rels, total, err := h.repo.Search(r.Context(), search, defaultLimit, offset)
	if err != nil {
		layout.ServerError(w, "search releases", err)
		return
	}

	data := releasesData{
		Pagination: layout.Pagination{Total: total, Limit: defaultLimit, Offset: offset},
		Releases:   rels,
		Search:     search,
	}

	page := layout.Page{
		Title:       "Releases",
		Description: "Arch Linux Release-Archiv",
		Path:        "/releases",
		Manifest:    h.manifest,
		NoIndex:     total == 0 || search != "" || offset > 0,
	}

	layout.Render(w, r, page, ReleaseList(data))
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")

	rel, err := h.repo.FindByVersion(r.Context(), version)
	if errors.Is(err, sql.ErrNoRows) {
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
