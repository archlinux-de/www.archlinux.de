package feeds

import (
	"fmt"
	"net/http"
	"time"

	"archded/internal/ui/layout"
)

func (h *Handler) newsFeed(w http.ResponseWriter, r *http.Request) {
	items, err := h.news.Latest(r.Context(), feedItems)
	if err != nil {
		layout.ServerError(w, "news feed", err)
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
