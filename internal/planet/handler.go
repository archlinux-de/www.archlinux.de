package planet

import (
	"encoding/xml"
	"log/slog"
	"net/http"
	"time"

	"archded/internal/ui/layout"
)

const planetFeedItems = 30

type Handler struct {
	repo     *Repository
	manifest *layout.Manifest
}

func NewHandler(repo *Repository, manifest *layout.Manifest) *Handler {
	return &Handler{repo: repo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /planet", h.index)
	mux.HandleFunc("GET /planet/atom.xml", h.atomFeed)
	mux.HandleFunc("GET /planet/rss.xml", h.rssFeed)
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	items, err := h.repo.LatestItems(r.Context(), planetFeedItems)
	if err != nil {
		layout.ServerError(w, "list planet items", err)
		return
	}

	page := layout.Page{
		Title:       "Planet",
		Description: "Arch Linux Planet — Blogbeiträge aus der deutschsprachigen Arch-Linux-Community",
		Path:        "/planet",
		Manifest:    h.manifest,
	}

	layout.Render(w, r, page, PlanetIndex(items))
}

// Atom output

type planetAtomFeed struct {
	XMLName  xml.Name          `xml:"feed"`
	XMLNS    string            `xml:"xmlns,attr"`
	ID       string            `xml:"id"`
	Title    string            `xml:"title"`
	Subtitle planetAtomText    `xml:"subtitle"`
	Updated  string            `xml:"updated"`
	Links    []planetAtomLink  `xml:"link"`
	Icon     string            `xml:"icon"`
	Logo     string            `xml:"logo"`
	Entries  []planetAtomEntry `xml:"entry"`
}

type planetAtomText struct {
	Type string `xml:"type,attr,omitempty"`
	Text string `xml:",chardata"`
}

type planetAtomLink struct {
	Href string `xml:"href,attr"`
	Rel  string `xml:"rel,attr,omitempty"`
}

type planetAtomAuthor struct {
	Name string `xml:"name"`
	URI  string `xml:"uri,omitempty"`
}

type planetAtomSource struct {
	Title   string           `xml:"title"`
	Updated string           `xml:"updated"`
	Links   []planetAtomLink `xml:"link"`
	ID      string           `xml:"id"`
}

type planetAtomEntry struct {
	ID      string            `xml:"id"`
	Title   string            `xml:"title"`
	Updated string            `xml:"updated"`
	Link    planetAtomLink    `xml:"link"`
	Author  planetAtomAuthor  `xml:"author"`
	Summary planetAtomText    `xml:"summary"`
	Source  *planetAtomSource `xml:"source,omitempty"`
}

func (h *Handler) atomFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.repo.LatestItems(r.Context(), planetFeedItems)
	if err != nil {
		layout.ServerError(w, "planet atom feed", err)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := planetAtomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		ID:    baseURL + "/planet/atom.xml",
		Title: "Arch Linux Planet",
		Subtitle: planetAtomText{
			Type: "text",
			Text: "planet.archlinux.de",
		},
		Links: []planetAtomLink{
			{Href: baseURL + "/", Rel: "alternate"},
			{Href: baseURL + "/planet/atom.xml", Rel: "self"},
		},
		Icon: baseURL + "/img/archicon.svg",
		Logo: baseURL + "/img/archlogo.svg",
	}

	for _, item := range items {
		entry := planetAtomEntry{
			ID:      item.Link,
			Title:   item.Title,
			Updated: formatAtomTime(item.LastModified),
			Link:    planetAtomLink{Href: item.Link, Rel: "alternate"},
			Author:  planetAtomAuthor{Name: item.AuthorName, URI: item.AuthorURI},
			Summary: planetAtomText{Type: "html", Text: item.Description},
		}

		entry.Source = &planetAtomSource{
			Title:   item.FeedTitle,
			Updated: formatAtomTime(item.FeedLastModified),
			Links: []planetAtomLink{
				{Href: item.FeedLink, Rel: "alternate"},
				{Href: item.FeedURL, Rel: "self"},
			},
			ID: item.FeedURL,
		}

		feed.Entries = append(feed.Entries, entry)
	}

	if len(feed.Entries) > 0 {
		feed.Updated = feed.Entries[0].Updated
	} else {
		feed.Updated = formatAtomTime(time.Now().Unix())
	}

	w.Header().Set("Cache-Control", "public, max-age=600")
	w.Header().Set("Content-Type", "application/atom+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "    ")
	if err := enc.Encode(feed); err != nil {
		slog.Error("encode planet atom feed", "error", err)
	}
}

func formatAtomTime(unix int64) string {
	t := time.Unix(unix, 0).UTC()
	t = t.Truncate(time.Minute)
	return t.Format("2006-01-02T15:04:05+00:00")
}

// RSS 2.0 output

type planetRSSFeed struct {
	XMLName xml.Name         `xml:"rss"`
	Version string           `xml:"version,attr"`
	NSAtom  string           `xml:"xmlns:atom,attr"`
	NSDC    string           `xml:"xmlns:dc,attr"`
	Channel planetRSSChannel `xml:"channel"`
}

type planetRSSChannel struct {
	Title       string          `xml:"title"`
	Link        string          `xml:"link"`
	Description string          `xml:"description"`
	PubDate     string          `xml:"pubDate"`
	AtomLink    planetAtomLink  `xml:"atom:link"`
	Items       []planetRSSItem `xml:"item"`
}

type planetRSSItem struct {
	Title   string          `xml:"title"`
	Link    string          `xml:"link"`
	Desc    string          `xml:"description"`
	Creator string          `xml:"dc:creator"`
	GUID    planetRSSGUID   `xml:"guid"`
	PubDate string          `xml:"pubDate"`
	Source  planetRSSSource `xml:"source"`
}

type planetRSSGUID struct {
	IsPermaLink string `xml:"isPermaLink,attr"`
	Value       string `xml:",chardata"`
}

type planetRSSSource struct {
	URL   string `xml:"url,attr"`
	Title string `xml:",chardata"`
}

func (h *Handler) rssFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.repo.LatestItems(r.Context(), planetFeedItems)
	if err != nil {
		layout.ServerError(w, "planet rss feed", err)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := planetRSSFeed{
		Version: "2.0",
		NSAtom:  "http://www.w3.org/2005/Atom",
		NSDC:    "http://purl.org/dc/elements/1.1/",
		Channel: planetRSSChannel{
			Title:       "Arch Linux Planet",
			Link:        baseURL + "/",
			Description: "planet.archlinux.de",
			AtomLink:    planetAtomLink{Href: baseURL + "/planet/rss.xml", Rel: "self"},
		},
	}

	for _, item := range items {
		rssItem := planetRSSItem{
			Title:   item.Title,
			Link:    item.Link,
			Desc:    item.Description,
			Creator: item.AuthorName,
			GUID:    planetRSSGUID{IsPermaLink: "true", Value: item.Link},
			PubDate: formatRSSTime(item.LastModified),
			Source:  planetRSSSource{URL: item.FeedURL, Title: item.FeedTitle},
		}
		feed.Channel.Items = append(feed.Channel.Items, rssItem)
	}

	if len(feed.Channel.Items) > 0 {
		feed.Channel.PubDate = feed.Channel.Items[0].PubDate
	} else {
		feed.Channel.PubDate = formatRSSTime(time.Now().Unix())
	}

	w.Header().Set("Cache-Control", "public, max-age=600")
	w.Header().Set("Content-Type", "application/rss+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "    ")
	if err := enc.Encode(feed); err != nil {
		slog.Error("encode planet rss feed", "error", err)
	}
}

func formatRSSTime(unix int64) string {
	t := time.Unix(unix, 0).UTC()
	t = t.Truncate(time.Minute)
	return t.Format(time.RFC1123Z)
}
