package feeds

import (
	"encoding/xml"
	"fmt"
	"net/http"
	"time"

	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/releases"
	"archded/internal/ui/layout"
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
		ID:    baseURL + "/news/feed",
		Title: "Aktuelle Arch Linux Neuigkeiten",
		Links: []atomLink{
			{Href: baseURL + "/news", Rel: "alternate"},
			{Href: baseURL + "/news/feed", Rel: "self"},
		},
		Icon: baseURL + "/img/archicon.svg",
		Logo: baseURL + "/img/archlogo.svg",
	}

	for _, item := range items {
		entry := atomEntry{
			ID:      fmt.Sprintf("%s/news/%d", baseURL, item.ID),
			Title:   item.Title,
			Updated: formatTime(item.LastModified),
			Link:    atomLink{Href: baseURL + item.URL(), Rel: "alternate"},
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
	pkgs, err := h.packages.Latest(r.Context(), feedItems)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := atomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		ID:    baseURL + "/packages/feed",
		Title: "Aktuelle Arch Linux Pakete",
		Links: []atomLink{
			{Href: baseURL + "/packages", Rel: "alternate"},
			{Href: baseURL + "/packages/feed", Rel: "self"},
		},
		Icon: baseURL + "/img/archicon.svg",
		Logo: baseURL + "/img/archlogo.svg",
	}

	for _, p := range pkgs {
		entryURL := baseURL + fmt.Sprintf("/packages/%s/%s/%s", p.Repository, p.Architecture, p.Name)
		entry := atomEntry{
			ID:      entryURL,
			Title:   p.Name + " " + p.Version,
			Updated: formatTime(p.BuildDate),
			Link:    atomLink{Href: entryURL, Rel: "alternate"},
			Summary: p.Description,
		}
		if p.PackagerName != "" {
			author := &atomAuthor{Name: p.PackagerName}
			if p.PackagerEmail != "" {
				author.Email = p.PackagerEmail
			}
			entry.Author = author
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
		ID:    baseURL + "/releases/feed",
		Title: "Arch Linux Releases",
		Links: []atomLink{
			{Href: baseURL + "/releases", Rel: "alternate"},
			{Href: baseURL + "/releases/feed", Rel: "self"},
		},
		Icon: baseURL + "/img/archicon.svg",
		Logo: baseURL + "/img/archlogo.svg",
	}

	for _, rel := range rels {
		entryURL := baseURL + "/releases/" + rel.Version
		entry := atomEntry{
			ID:      entryURL,
			Title:   rel.Version,
			Updated: formatTime(rel.ReleaseDate),
			Link:    atomLink{Href: entryURL, Rel: "alternate"},
			Author:  &atomAuthor{Name: "Arch Linux"},
			Summary: "Arch Linux ISO image Version " + rel.Version,
		}
		if rel.FileName != "" {
			entry.Content = &atomContent{
				Src:  baseURL + "/download/iso/" + rel.Version + "/" + rel.FileName,
				Type: "application/x-iso9660-image",
			}
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
