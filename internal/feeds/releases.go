package feeds

import (
	"net/http"
	"time"

	"archded/internal/ui/layout"
)

func (h *Handler) releasesFeed(w http.ResponseWriter, r *http.Request) {
	rels, err := h.releases.AllAvailable(r.Context())
	if err != nil {
		layout.ServerError(w, "releases feed", err)
		return
	}

	baseURL := layout.GetBaseURL(r)

	//nolint:goconst // literals are clearer for Atom feeds
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
