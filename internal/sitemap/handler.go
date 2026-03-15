package sitemap

import (
	"encoding/xml"
	"net/http"
	"strconv"
	"time"

	"www/internal/news"
	"www/internal/packages"
	"www/internal/releases"
)

type Handler struct {
	news     *news.Repository
	packages *packages.Repository
	releases *releases.Repository
}

func NewHandler(n *news.Repository, p *packages.Repository, r *releases.Repository) *Handler {
	return &Handler{news: n, packages: p, releases: r}
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

	if pkgRefs, err := h.packages.AllStableRefs(ctx); err == nil {
		for _, ref := range pkgRefs {
			u := siteURL{Loc: "/packages/" + ref.Repository + "/" + ref.Architecture + "/" + ref.Name}
			if ref.BuildDate > 0 {
				u.LastMod = time.Unix(ref.BuildDate, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	if newsRefs, err := h.news.AllRefs(ctx); err == nil {
		for _, ref := range newsRefs {
			u := siteURL{Loc: "/news/" + strconv.Itoa(ref.ID)}
			if ref.LastModified > 0 {
				u.LastMod = time.Unix(ref.LastModified, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	if relRefs, err := h.releases.AllRefs(ctx); err == nil {
		for _, ref := range relRefs {
			u := siteURL{Loc: "/releases/" + ref.Version}
			if ref.Created > 0 {
				u.LastMod = time.Unix(ref.Created, 0).UTC().Format("2006-01-02")
			}
			urls = append(urls, u)
		}
	}

	w.Header().Set("Content-Type", "application/xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	_ = enc.Encode(urlSet{
		XMLNS: "http://www.sitemaps.org/schemas/sitemap/0.9",
		URLs:  urls,
	})
}
