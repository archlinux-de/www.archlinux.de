package download

import (
	"fmt"
	"net/http"
	"path"
	"strings"
	"time"

	"archded/internal/releases"
	"archded/internal/ui/layout"
)

type Handler struct {
	releases *releases.Repository
	manifest *layout.Manifest
	mirror   string
}

func NewHandler(
	relRepo *releases.Repository,
	manifest *layout.Manifest,
	mirror string,
) *Handler {
	return &Handler{
		releases: relRepo,
		manifest: manifest,
		mirror:   mirror,
	}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /download", h.index)
	mux.HandleFunc("GET /download/iso/{version}/{file}", h.iso)
	mux.HandleFunc("GET /download/iso/{version}/{$}", h.isoDir)
	mux.HandleFunc("GET /download/{repository}/os/{architecture}/{file}", h.pkg)
	mux.HandleFunc("GET /download/{file...}", h.fallback)
}

const mirrorArchive = "https://archive.archlinux.org/"

type downloadData struct {
	Release        *releases.Release
	DownloadURL    string
	UpstreamISODir string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	rel, err := h.releases.LatestAvailable(ctx)
	if err != nil {
		layout.ServerError(w, "get release", err)
		return
	}

	data := downloadData{
		Release:        &rel,
		DownloadURL:    fmt.Sprintf("/download/iso/%s/%s", rel.Version, rel.FileName),
		UpstreamISODir: fmt.Sprintf("https://archlinux.org/iso/%s/", rel.Version),
	}

	page := layout.Page{
		Title:       "Arch Linux herunterladen",
		Description: fmt.Sprintf("Download Arch Linux %s (Kernel %s)", rel.Version, rel.KernelVersion),
		Path:        "/download",
		Manifest:    h.manifest,
		JsonLD: map[string]any{
			"download": map[string]any{
				"@context":        "https://schema.org",
				"@type":           "SoftwareApplication",
				"name":            "Arch Linux",
				"operatingSystem": "Arch Linux",
				"softwareVersion": rel.Version,
				"datePublished":   time.Unix(rel.ReleaseDate, 0).UTC().Format(time.RFC3339),
				"fileSize":        layout.FormatSize(rel.FileLength),
				"offers":          map[string]any{"@type": "Offer", "price": "0", "priceCurrency": "EUR"},
			},
		},
	}

	layout.Render(w, r, page, DownloadPage(data))
}

var versionDirMap = map[string]string{
	"0.7.1":     "0.7",
	"0.7.2":     "0.7",
	"2007.08-2": "2007.08",
}

func (h *Handler) iso(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")
	file := r.PathValue("file")

	ra, err := h.releases.Availability(r.Context(), version)
	if err != nil {
		http.NotFound(w, r)
		return
	}

	if !ra.Available {
		dirVersion := version
		if mapped, ok := versionDirMap[version]; ok {
			dirVersion = mapped
		}
		// #nosec G710 -- mirrorArchive is a constant
		http.Redirect(w, r, mirrorArchive+"iso/"+dirVersion+"/"+file, http.StatusMovedPermanently)
		return
	}

	// #nosec G710 -- h.mirror is from trusted configuration
	http.Redirect(w, r, h.mirror+"iso/"+version+"/"+file, http.StatusTemporaryRedirect)
}

func (h *Handler) isoDir(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")

	ra, err := h.releases.Availability(r.Context(), version)
	if err != nil {
		http.NotFound(w, r)
		return
	}

	if !ra.Available {
		dirVersion := version
		if mapped, ok := versionDirMap[version]; ok {
			dirVersion = mapped
		}
		// #nosec G710 -- mirrorArchive is a constant
		http.Redirect(w, r, mirrorArchive+"iso/"+dirVersion+"/", http.StatusMovedPermanently)
		return
	}

	// #nosec G710 -- h.mirror is from trusted configuration
	http.Redirect(w, r, h.mirror+"iso/"+version+"/", http.StatusTemporaryRedirect)
}

func (h *Handler) pkg(w http.ResponseWriter, r *http.Request) {
	repo := r.PathValue("repository")
	arch := r.PathValue("architecture")
	file := r.PathValue("file")

	// #nosec G710 -- h.mirror is from trusted configuration
	http.Redirect(w, r, h.mirror+repo+"/os/"+arch+"/"+file, http.StatusTemporaryRedirect)
}

func (h *Handler) fallback(w http.ResponseWriter, r *http.Request) {
	file := r.PathValue("file")

	clean := path.Clean(file)
	if clean != file || strings.HasPrefix(clean, "..") {
		http.NotFound(w, r)
		return
	}

	// #nosec G710 -- h.mirror is from trusted configuration
	http.Redirect(w, r, h.mirror+file, http.StatusTemporaryRedirect)
}
