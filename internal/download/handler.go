package download

import (
	"database/sql"
	"fmt"
	"math/rand/v2"
	"net"
	"net/http"
	"net/netip"
	"regexp"
	"strings"

	"www/internal/ui/layout"

	"github.com/oschwald/maxminddb-golang/v2"
)

type Handler struct {
	db       *sql.DB
	manifest *layout.Manifest
	geodb    *maxminddb.Reader
}

func NewHandler(db *sql.DB, manifest *layout.Manifest, geodb *maxminddb.Reader) *Handler {
	return &Handler{db: db, manifest: manifest, geodb: geodb}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /download", h.index)
	mux.HandleFunc("GET /download/iso/{version}/{file}", h.iso)
	mux.HandleFunc("GET /download/{repository}/os/{architecture}/{file}", h.pkg)
	mux.HandleFunc("GET /download/{file...}", h.fallback)
}

const mirrorArchive = "https://archive.archlinux.org/"

type downloadData struct {
	Release     *downloadRelease
	Mirrors     []downloadMirror
	ISOPath     string
	ISOSigPath  string
	DownloadURL string
}

type downloadRelease struct {
	Version       string
	KernelVersion string
	FileLength    int64
	ReleaseDate   int64
	SHA1Sum       string
	SHA256Sum     string
	B2Sum         string
	TorrentURL    string
	MagnetURI     string
	FileName      string
}

type downloadMirror struct {
	URL         string
	CountryName string
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	var rel downloadRelease
	err := h.db.QueryRowContext(ctx,
		`SELECT version, COALESCE(kernel_version, ''), COALESCE(file_length, 0), COALESCE(release_date, 0),
		        COALESCE(sha1_sum, ''), COALESCE(sha256_sum, ''), COALESCE(b2_sum, ''),
		        COALESCE(torrent_url, ''), COALESCE(magnet_uri, ''), COALESCE(file_name, '')
		 FROM release WHERE available = 1 ORDER BY release_date DESC LIMIT 1`).Scan(
		&rel.Version, &rel.KernelVersion, &rel.FileLength, &rel.ReleaseDate,
		&rel.SHA1Sum, &rel.SHA256Sum, &rel.B2Sum,
		&rel.TorrentURL, &rel.MagnetURI, &rel.FileName,
	)
	if err == sql.ErrNoRows {
		layout.ServerError(w, "no available release", fmt.Errorf("no available release found"))
		return
	}
	if err != nil {
		layout.ServerError(w, "get release", err)
		return
	}

	rows, err := h.db.QueryContext(ctx,
		`SELECT m.url, COALESCE(c.name, '')
		 FROM mirror m LEFT JOIN country c ON c.code = m.country_code
		 ORDER BY m.score ASC LIMIT 10`)
	if err != nil {
		layout.ServerError(w, "query mirrors", err)
		return
	}
	defer rows.Close()

	var mirrors []downloadMirror
	for rows.Next() {
		var m downloadMirror
		if err := rows.Scan(&m.URL, &m.CountryName); err != nil {
			layout.ServerError(w, "scan mirror", err)
			return
		}
		mirrors = append(mirrors, m)
	}
	if err := rows.Err(); err != nil {
		layout.ServerError(w, "mirror rows", err)
		return
	}

	isoPath := fmt.Sprintf("iso/%s/%s", rel.Version, rel.FileName)

	data := downloadData{
		Release:     &rel,
		Mirrors:     mirrors,
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
	"0.7.1":    "0.7",
	"0.7.2":    "0.7",
	"2007.08-2": "2007.08",
}

func (h *Handler) iso(w http.ResponseWriter, r *http.Request) {
	version := r.PathValue("version")
	file := r.PathValue("file")

	var available bool
	var buildDate *int64
	err := h.db.QueryRowContext(r.Context(),
		`SELECT available, created FROM release WHERE version = ?`, version).Scan(&available, &buildDate)
	if err == sql.ErrNoRows {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "get release", err)
		return
	}

	if !available {
		dirVersion := version
		if mapped, ok := versionDirMap[version]; ok {
			dirVersion = mapped
		}
		http.Redirect(w, r, mirrorArchive+"iso/"+dirVersion+"/"+file, http.StatusMovedPermanently)
		return
	}

	mirror := h.selectMirror(r, buildDate)
	if mirror == "" {
		http.NotFound(w, r)
		return
	}

	http.Redirect(w, r, mirror+"iso/"+version+"/"+file, http.StatusTemporaryRedirect)
}

var pkgNameRe = regexp.MustCompile(`^([^-]+.*)-[^-]+-[^-]+-.*$`)

func (h *Handler) pkg(w http.ResponseWriter, r *http.Request) {
	repo := r.PathValue("repository")
	arch := r.PathValue("architecture")
	file := r.PathValue("file")

	var buildDate *int64
	matches := pkgNameRe.FindStringSubmatch(file)
	if matches != nil {
		_ = h.db.QueryRowContext(r.Context(),
			`SELECT p.build_date FROM package p
			 JOIN repository r ON r.id = p.repository_id
			 WHERE p.name = ? AND r.name = ? AND r.architecture = ?`,
			matches[1], repo, arch).Scan(&buildDate)
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

	var query string
	var args []any

	if lastSync != nil {
		query = `SELECT m.url FROM mirror m
			LEFT JOIN country c ON c.code = m.country_code
			WHERE m.url LIKE 'https%'
			ORDER BY
				(CASE WHEN m.country_code = ? THEN 1 ELSE 0 END) DESC,
				(CASE WHEN m.last_sync >= ? THEN 1 ELSE 0 END) DESC,
				m.score ASC
			LIMIT 20`
		args = []any{countryCode, *lastSync}
	} else {
		query = `SELECT m.url FROM mirror m
			WHERE m.url LIKE 'https%'
			ORDER BY
				(CASE WHEN m.country_code = ? THEN 1 ELSE 0 END) DESC,
				m.score ASC
			LIMIT 20`
		args = []any{countryCode}
	}

	rows, err := h.db.QueryContext(r.Context(), query, args...)
	if err != nil {
		return ""
	}
	defer rows.Close()

	var mirrors []string
	for rows.Next() {
		var url string
		if err := rows.Scan(&url); err != nil {
			continue
		}
		mirrors = append(mirrors, url)
	}

	if len(mirrors) == 0 {
		return ""
	}

	clientIP := clientAddr(r)
	seed := uint64(0)
	for _, b := range []byte(clientIP) {
		seed = seed*31 + uint64(b)
	}
	rng := rand.New(rand.NewPCG(seed, 0))
	return mirrors[rng.IntN(len(mirrors))]
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
		parts := strings.SplitN(forwarded, ",", 2)
		return strings.TrimSpace(parts[0])
	}
	host, _, err := net.SplitHostPort(r.RemoteAddr)
	if err != nil {
		return r.RemoteAddr
	}
	return host
}
