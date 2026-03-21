package download

import (
	"fmt"
	"math/rand/v2"
	"net"
	"net/http"
	"regexp"
	"strings"
	"time"

	"archded/internal/mirrors"
	"archded/internal/packages"
	"archded/internal/releases"
	"archded/internal/ui/layout"
)

type Handler struct {
	releases      *releases.Repository
	packages      *packages.Repository
	mirrors       *mirrors.Repository
	manifest      *layout.Manifest
	defaultMirror string
}

func NewHandler(
	relRepo *releases.Repository,
	pkgRepo *packages.Repository,
	mirRepo *mirrors.Repository,
	manifest *layout.Manifest,
	defaultMirror string,
) *Handler {
	return &Handler{
		releases:      relRepo,
		packages:      pkgRepo,
		mirrors:       mirRepo,
		manifest:      manifest,
		defaultMirror: defaultMirror,
	}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /download", h.index)
	mux.HandleFunc("GET /download/iso/{version}/{file}", h.iso)
	mux.HandleFunc("GET /download/iso/{version}/{$}", h.isoDir)
	mux.HandleFunc("GET /download/{repository}/os/{architecture}/{file}", h.pkg)
	mux.HandleFunc("GET /download/{file...}", h.fallback)
}

const (
	mirrorArchive     = "https://archive.archlinux.org/"
	downloadMirrors   = 10
	selectMirrorCount = 20
	hashMultiplier    = 31
)

type downloadData struct {
	Release     *releases.Release
	Mirrors     []mirrors.MirrorSummary
	ISOPath     string
	ISOSigPath  string
	DownloadURL string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	rel, err := h.releases.LatestAvailable(ctx)
	if err != nil {
		layout.ServerError(w, "get release", err)
		return
	}

	mirrorList, err := h.mirrors.TopByScore(ctx, downloadMirrors)
	if err != nil {
		layout.ServerError(w, "query mirrors", err)
		return
	}

	isoPath := fmt.Sprintf("iso/%s/%s", rel.Version, rel.FileName)

	data := downloadData{
		Release:     &rel,
		Mirrors:     mirrorList,
		ISOPath:     isoPath,
		ISOSigPath:  isoPath + ".sig",
		DownloadURL: fmt.Sprintf("/download/iso/%s/%s", rel.Version, rel.FileName),
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
		http.Redirect(w, r, mirrorArchive+"iso/"+dirVersion+"/"+file, http.StatusMovedPermanently)
		return
	}

	mirror := h.selectMirror(r, ra.Created)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+"iso/"+version+"/"+file, http.StatusTemporaryRedirect)
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
		http.Redirect(w, r, mirrorArchive+"iso/"+dirVersion+"/", http.StatusMovedPermanently)
		return
	}

	mirror := h.selectMirror(r, ra.Created)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+"iso/"+version+"/", http.StatusTemporaryRedirect)
}

var pkgNameRe = regexp.MustCompile(`^([^-]+.*)-[^-]+-[^-]+-.*$`)

func (h *Handler) pkg(w http.ResponseWriter, r *http.Request) {
	repo := r.PathValue("repository")
	arch := r.PathValue("architecture")
	file := r.PathValue("file")

	var buildDate *int64
	matches := pkgNameRe.FindStringSubmatch(file)
	if matches != nil {
		buildDate = h.packages.BuildDate(r.Context(), matches[1], repo, arch)
	}

	mirror := h.selectMirror(r, buildDate)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+repo+"/os/"+arch+"/"+file, http.StatusTemporaryRedirect)
}

func (h *Handler) fallback(w http.ResponseWriter, r *http.Request) {
	file := r.PathValue("file")

	if strings.Contains(file, "..") {
		http.NotFound(w, r)
		return
	}

	mirror := h.selectMirror(r, nil)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+file, http.StatusTemporaryRedirect)
}

func (h *Handler) selectMirror(r *http.Request, lastSync *int64) string {
	urls, err := h.mirrors.SelectByCountry(r.Context(), "", lastSync, selectMirrorCount)
	if err != nil || len(urls) == 0 {
		return h.defaultMirror
	}

	clientIP := clientAddr(r)
	seed := uint64(0)
	for _, b := range []byte(clientIP) {
		seed = seed*hashMultiplier + uint64(b)
	}
	rng := rand.New(rand.NewPCG(seed, 0)) //nolint:gosec // deterministic selection per client IP
	return urls[rng.IntN(len(urls))]
}

func clientAddr(r *http.Request) string {
	if realIP := r.Header.Get("X-Real-IP"); realIP != "" {
		return realIP
	}
	host, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return r.RemoteAddr
	}
	return host
}
