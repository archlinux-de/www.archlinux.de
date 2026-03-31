package feeds

import (
	"fmt"
	"net/http"
	"time"

	"archded/internal/ui/layout"
)

func (h *Handler) packagesFeed(w http.ResponseWriter, r *http.Request) {
	pkgs, err := h.packages.Latest(r.Context(), feedItems)
	if err != nil {
		layout.ServerError(w, "packages feed", err)
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
