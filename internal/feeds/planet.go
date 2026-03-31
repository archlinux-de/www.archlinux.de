package feeds

import (
	"encoding/xml"
	"log/slog"
	"net/http"
	"time"

	"archded/internal/ui/layout"
)

const planetFeedItems = 30

func (h *Handler) planetAtomFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.news.Latest(r.Context(), planetFeedItems)
	if err != nil {
		layout.ServerError(w, "planet atom feed", err)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := atomFeed{
		XMLNS: "http://www.w3.org/2005/Atom",
		ID:    baseURL + "/planet/atom.xml",
		Title: "Arch Linux Planet",
		Links: []atomLink{
			{Href: baseURL + "/news", Rel: "alternate"},
			{Href: baseURL + "/planet/atom.xml", Rel: "self"},
		},
		Icon: baseURL + "/img/archicon.svg",
		Logo: baseURL + "/img/archlogo.svg",
	}

	for _, item := range items {
		entry := atomEntry{
			ID:      item.Link,
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

type rssFeed struct {
	XMLName xml.Name   `xml:"rss"`
	Version string     `xml:"version,attr"`
	NSAtom  string     `xml:"xmlns:atom,attr"`
	NSDC    string     `xml:"xmlns:dc,attr"`
	Channel rssChannel `xml:"channel"`
}

type rssChannel struct {
	Title       string    `xml:"title"`
	Link        string    `xml:"link"`
	Description string    `xml:"description"`
	PubDate     string    `xml:"pubDate"`
	AtomLink    atomLink  `xml:"atom:link"`
	Items       []rssItem `xml:"item"`
}

type rssItem struct {
	Title   string  `xml:"title"`
	Link    string  `xml:"link"`
	Desc    string  `xml:"description"`
	Creator string  `xml:"dc:creator"`
	GUID    rssGUID `xml:"guid"`
	PubDate string  `xml:"pubDate"`
}

type rssGUID struct {
	IsPermaLink string `xml:"isPermaLink,attr"`
	Value       string `xml:",chardata"`
}

func (h *Handler) planetRSSFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.news.Latest(r.Context(), planetFeedItems)
	if err != nil {
		layout.ServerError(w, "planet rss feed", err)
		return
	}

	baseURL := layout.GetBaseURL(r)

	feed := rssFeed{
		Version: "2.0",
		NSAtom:  "http://www.w3.org/2005/Atom",
		NSDC:    "http://purl.org/dc/elements/1.1/",
		Channel: rssChannel{
			Title:       "Arch Linux Planet",
			Link:        baseURL + "/news",
			Description: "planet.archlinux.de",
			AtomLink:    atomLink{Href: baseURL + "/planet/rss.xml", Rel: "self"},
		},
	}

	for _, item := range items {
		feed.Channel.Items = append(feed.Channel.Items, rssItem{
			Title:   item.Title,
			Link:    baseURL + item.URL(),
			Desc:    item.Description,
			Creator: item.AuthorName,
			GUID:    rssGUID{IsPermaLink: "true", Value: item.Link},
			PubDate: time.Unix(item.LastModified, 0).UTC().Format(time.RFC1123Z),
		})
	}

	if len(feed.Channel.Items) > 0 {
		feed.Channel.PubDate = feed.Channel.Items[0].PubDate
	} else {
		feed.Channel.PubDate = time.Unix(time.Now().Unix(), 0).UTC().Format(time.RFC1123Z)
	}

	w.Header().Set("Cache-Control", "public, max-age=86400")
	w.Header().Set("Content-Type", "application/rss+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	if err := enc.Encode(feed); err != nil {
		slog.Error("encode planet rss feed", "error", err)
	}
}
