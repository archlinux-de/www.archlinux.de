package sitemap

import (
	"database/sql"
	"encoding/xml"
	"net/http"
	"time"
)

type Handler struct {
	db *sql.DB
}

func NewHandler(db *sql.DB) *Handler {
	return &Handler{db: db}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /sitemap.xml", h.index)
}

type urlSet struct {
	XMLName xml.Name  `xml:"urlset"`
	XMLNS   string    `xml:"xmlns,attr"`
	URLs    []siteURL `xml:"url"`
}

type siteURL struct {
	Loc     string `xml:"loc"`
	LastMod string `xml:"lastmod,omitempty"`
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	urls := []siteURL{
		{Loc: "/"},
		{Loc: "/packages"},
		{Loc: "/news"},
		{Loc: "/mirrors"},
		{Loc: "/releases"},
		{Loc: "/download"},
	}

	// Packages
	rows, err := h.db.QueryContext(ctx,
		`SELECT p.name, r.name, r.architecture, COALESCE(p.build_date, 0)
		 FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE r.testing = 0`)
	if err == nil {
		defer rows.Close()
		for rows.Next() {
			var name, repo, arch string
			var buildDate int64
			if err := rows.Scan(&name, &repo, &arch, &buildDate); err != nil {
				continue
			}
			u := siteURL{Loc: "/packages/" + repo + "/" + arch + "/" + name}
			if buildDate > 0 {
				u.LastMod = time.Unix(buildDate, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	// News
	rows2, err := h.db.QueryContext(ctx,
		`SELECT id, last_modified FROM news_item`)
	if err == nil {
		defer rows2.Close()
		for rows2.Next() {
			var id int
			var lastMod int64
			if err := rows2.Scan(&id, &lastMod); err != nil {
				continue
			}
			u := siteURL{Loc: "/news/" + itoa(id)}
			if lastMod > 0 {
				u.LastMod = time.Unix(lastMod, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	// Releases
	rows3, err := h.db.QueryContext(ctx,
		`SELECT version, COALESCE(created, 0) FROM release`)
	if err == nil {
		defer rows3.Close()
		for rows3.Next() {
			var version string
			var created int64
			if err := rows3.Scan(&version, &created); err != nil {
				continue
			}
			u := siteURL{Loc: "/releases/" + version}
			if created > 0 {
				u.LastMod = time.Unix(created, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	w.Header().Set("Content-Type", "application/xml; charset=UTF-8")
	w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	enc.Encode(urlSet{
		XMLNS: "http://www.sitemaps.org/schemas/sitemap/0.9",
		URLs:  urls,
	})
}

func itoa(i int) string {
	if i == 0 {
		return "0"
	}
	b := make([]byte, 0, 10)
	for i > 0 {
		b = append(b, byte('0'+i%10))
		i /= 10
	}
	for l, r := 0, len(b)-1; l < r; l, r = l+1, r-1 {
		b[l], b[r] = b[r], b[l]
	}
	return string(b)
}
