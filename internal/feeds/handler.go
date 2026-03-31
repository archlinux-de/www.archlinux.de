package feeds

import (
	"encoding/xml"
	"log/slog"
	"net/http"
	"time"

	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/releases"
)

const feedItems = 25

type Handler struct {
	news     *news.Repository
	packages *packages.Repository
	releases *releases.Repository
}

func NewHandler(n *news.Repository, p *packages.Repository, r *releases.Repository) *Handler {
	return &Handler{news: n, packages: p, releases: r}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /news/feed", h.newsFeed)
	mux.HandleFunc("GET /packages/feed", h.packagesFeed)
	mux.HandleFunc("GET /releases/feed", h.releasesFeed)
	mux.HandleFunc("GET /planet/atom.xml", h.planetAtomFeed)
	mux.HandleFunc("GET /planet/rss.xml", h.planetRSSFeed)
	mux.HandleFunc("GET /planet", func(w http.ResponseWriter, r *http.Request) {
		http.Redirect(w, r, "/news", http.StatusMovedPermanently)
	})
}

type atomFeed struct {
	XMLName xml.Name    `xml:"feed"`
	XMLNS   string      `xml:"xmlns,attr"`
	ID      string      `xml:"id"`
	Title   string      `xml:"title"`
	Updated string      `xml:"updated"`
	Links   []atomLink  `xml:"link"`
	Icon    string      `xml:"icon"`
	Logo    string      `xml:"logo"`
	Entries []atomEntry `xml:"entry"`
}

type atomLink struct {
	Href string `xml:"href,attr"`
	Rel  string `xml:"rel,attr,omitempty"`
	Type string `xml:"type,attr,omitempty"`
}

type atomEntry struct {
	ID      string       `xml:"id"`
	Title   string       `xml:"title"`
	Updated string       `xml:"updated"`
	Link    atomLink     `xml:"link"`
	Author  *atomAuthor  `xml:"author,omitempty"`
	Summary string       `xml:"summary,omitempty"`
	Content *atomContent `xml:"content,omitempty"`
}

type atomAuthor struct {
	Name  string `xml:"name"`
	URI   string `xml:"uri,omitempty"`
	Email string `xml:"email,omitempty"`
}

type atomContent struct {
	Type    string `xml:"type,attr,omitempty"`
	Src     string `xml:"src,attr,omitempty"`
	Content string `xml:",chardata"`
}

func writeAtom(w http.ResponseWriter, feed atomFeed) {
	w.Header().Set("Cache-Control", "public, max-age=86400")
	w.Header().Set("Content-Type", "application/atom+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	if err := enc.Encode(feed); err != nil {
		slog.Error("encode atom feed", "error", err)
	}
}

func formatTime(unix int64) string {
	return time.Unix(unix, 0).UTC().Format(time.RFC3339)
}
