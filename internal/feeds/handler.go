package feeds

import (
	"encoding/xml"
	"fmt"
	"net/http"
	"time"

	"www/internal/news"
	"www/internal/packages"
	"www/internal/releases"
	"www/internal/ui/layout"
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
}

type atomFeed struct {
	XMLName xml.Name    `xml:"feed"`
	XMLNS   string      `xml:"xmlns,attr"`
	Title   string      `xml:"title"`
	Links   []atomLink  `xml:"link"`
	ID      string      `xml:"id"`
	Updated string      `xml:"updated"`
	Entries []atomEntry `xml:"entry"`
}

type atomLink struct {
	Href string `xml:"href,attr"`
	Rel  string `xml:"rel,attr,omitempty"`
	Type string `xml:"type,attr,omitempty"`
}

type atomEntry struct {
	Title   string       `xml:"title"`
	Link    atomLink     `xml:"link"`
	ID      string       `xml:"id"`
	Updated string       `xml:"updated"`
	Author  *atomAuthor  `xml:"author,omitempty"`
	Summary string       `xml:"summary,omitempty"`
	Content *atomContent `xml:"content,omitempty"`
}

type atomAuthor struct {
	Name string `xml:"name"`
	URI  string `xml:"uri,omitempty"`
}

type atomContent struct {
	Type    string `xml:"type,attr"`
	Content string `xml:",chardata"`
}

func writeAtom(w http.ResponseWriter, feed atomFeed) {
	w.Header().Set("Cache-Control", "public, max-age=86400")
	w.Header().Set("Content-Type", "application/atom+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	_ = enc.Encode(feed)
}

func formatTime(unix int64) string {
	return time.Unix(unix, 0).UTC().Format(time.RFC3339)
}

func (h *Handler) newsFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.news.Latest(r.Context(), feedItems)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := atomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		Title: "Neuigkeiten - www.archlinux.de",
		Links: []atomLink{
			{Href: baseURL + "/news", Rel: "alternate", Type: "text/html"},
			{Href: baseURL + "/news/feed", Rel: "self", Type: "application/atom+xml"},
		},
		ID: baseURL + "/news/feed",
	}

	for _, item := range items {
		entry := atomEntry{
			Title:   item.Title,
			Link:    atomLink{Href: baseURL + item.URL()},
			ID:      baseURL + item.URL(),
			Updated: formatTime(item.LastModified),
		}
		if item.AuthorName != "" {
			entry.Author = &atomAuthor{Name: item.AuthorName, URI: item.AuthorLink}
		}
		if item.Description != "" {
			entry.Content = &atomContent{Type: "html", Content: item.Description}
		}
		feed.Entries = append(feed.Entries, entry)
	}

	if len(feed.Entries) > 0 {
		feed.Updated = feed.Entries[0].Updated
	} else {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}

func (h *Handler) packagesFeed(w http.ResponseWriter, r *http.Request) {
	pkgs, err := h.packages.LatestStable(r.Context(), feedItems)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := atomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		Title: "Pakete - www.archlinux.de",
		Links: []atomLink{
			{Href: baseURL + "/packages", Rel: "alternate", Type: "text/html"},
			{Href: baseURL + "/packages/feed", Rel: "self", Type: "application/atom+xml"},
		},
		ID: baseURL + "/packages/feed",
	}

	for _, p := range pkgs {
		entryURL := baseURL + fmt.Sprintf("/packages/%s/%s/%s", p.Repository, p.Architecture, p.Name)
		entry := atomEntry{
			Title:   p.Name + " " + p.Version,
			Link:    atomLink{Href: entryURL},
			ID:      entryURL,
			Updated: formatTime(p.BuildDate),
			Summary: p.Description,
		}
		if p.PackagerName != "" {
			entry.Author = &atomAuthor{Name: p.PackagerName}
		}
		feed.Entries = append(feed.Entries, entry)
	}

	if len(feed.Entries) > 0 {
		feed.Updated = feed.Entries[0].Updated
	} else {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}

func (h *Handler) releasesFeed(w http.ResponseWriter, r *http.Request) {
	rels, err := h.releases.AllAvailable(r.Context())
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := atomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		Title: "Releases - www.archlinux.de",
		Links: []atomLink{
			{Href: baseURL + "/releases", Rel: "alternate", Type: "text/html"},
			{Href: baseURL + "/releases/feed", Rel: "self", Type: "application/atom+xml"},
		},
		ID: baseURL + "/releases/feed",
	}

	for _, rel := range rels {
		entryURL := baseURL + "/releases/" + rel.Version
		entry := atomEntry{
			Title:   "Arch Linux " + rel.Version,
			Link:    atomLink{Href: entryURL},
			ID:      entryURL,
			Updated: formatTime(rel.ReleaseDate),
			Author:  &atomAuthor{Name: "Arch Linux"},
			Summary: rel.Info,
		}
		feed.Entries = append(feed.Entries, entry)
	}

	if len(feed.Entries) > 0 {
		feed.Updated = feed.Entries[0].Updated
	} else {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}
