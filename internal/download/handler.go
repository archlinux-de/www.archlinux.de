package download

import (
	"fmt"
	"math/rand/v2"
	"net"
	"net/http"
	"net/netip"
	"regexp"
	"strings"

	"www/internal/mirrors"
	"www/internal/packages"
	"www/internal/releases"
	"www/internal/ui/layout"

	"github.com/oschwald/maxminddb-golang/v2"
)

type Handler struct {
	releases *releases.Repository
	packages *packages.Repository
	mirrors  *mirrors.Repository
	manifest *layout.Manifest
	geodb    *maxminddb.Reader
}

func NewHandler(
	relRepo *releases.Repository,
	pkgRepo *packages.Repository,
	mirRepo *mirrors.Repository,
	manifest *layout.Manifest,
	geodb *maxminddb.Reader,
) *Handler {
	return &Handler{
		releases: relRepo,
		packages: pkgRepo,
		mirrors:  mirRepo,
		manifest: manifest,
		geodb:    geodb,
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

	mirror := h.selectMirror(r, nil)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+file, http.StatusTemporaryRedirect)
}

func (h *Handler) selectMirror(r *http.Request, lastSync *int64) string {
	countryCode := h.lookupCountry(r)

	urls, err := h.mirrors.SelectByCountry(r.Context(), countryCode, lastSync, selectMirrorCount)
	if err != nil || len(urls) == 0 {
		return ""
	}

	clientIP := clientAddr(r)
	seed := uint64(0)
	for _, b := range []byte(clientIP) {
		seed = seed*hashMultiplier + uint64(b)
	}
	rng := rand.New(rand.NewPCG(seed, 0)) //nolint:gosec // deterministic selection per client IP
	return urls[rng.IntN(len(urls))]
}

func (h *Handler) lookupCountry(r *http.Request) string {
	if h.geodb == nil {
		return "DE"
	}

	addr, err := netip.ParseAddr(clientAddr(r))
	if err != nil {
		return "DE"
	}

	var result struct {
		Country struct {
			ISOCode string `maxminddb:"iso_code"`
		} `maxminddb:"country"`
	}
	if err := h.geodb.Lookup(addr).Decode(&result); err != nil || result.Country.ISOCode == "" {
		return "DE"
	}

	return result.Country.ISOCode
}

func clientAddr(r *http.Request) string {
	if forwarded := r.Header.Get("X-Forwarded-For"); forwarded != "" {
		parts := strings.SplitN(forwarded, ",", 2) //nolint:mnd
		return strings.TrimSpace(parts[0])
	}
	host, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return r.RemoteAddr
	}
	return host
}
