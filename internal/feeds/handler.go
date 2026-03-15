package feeds

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
	mux.HandleFunc("GET /news/feed", h.newsFeed)
	mux.HandleFunc("GET /packages/feed", h.packagesFeed)
	mux.HandleFunc("GET /releases/feed", h.releasesFeed)
}

type atomFeed struct {
	XMLName xml.Name    `xml:"feed"`
	XMLNS   string      `xml:"xmlns,attr"`
	Title   string      `xml:"title"`
	Link    atomLink    `xml:"link"`
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
	Title   string      `xml:"title"`
	Link    atomLink    `xml:"link"`
	ID      string      `xml:"id"`
	Updated string      `xml:"updated"`
	Author  *atomAuthor `xml:"author,omitempty"`
	Summary string      `xml:"summary,omitempty"`
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
	w.Header().Set("Content-Type", "application/atom+xml; charset=UTF-8")
	w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	enc.Encode(feed)
}

func formatTime(unix int64) string {
	return time.Unix(unix, 0).UTC().Format(time.RFC3339)
}

func (h *Handler) newsFeed(w http.ResponseWriter, r *http.Request) {
	rows, err := h.db.QueryContext(r.Context(),
		`SELECT id, title, link, COALESCE(description, ''), COALESCE(author_name, ''), COALESCE(author_link, ''), last_modified
		 FROM news_item ORDER BY last_modified DESC LIMIT 25`)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	feed := atomFeed{
		XMLNS:   "http://www.w3.org/2005/Atom",
		Title:   "Neuigkeiten - www.archlinux.de",
		Link:    atomLink{Href: "/news"},
		ID:      "/news/feed",
	}

	for rows.Next() {
		var id int
		var title, link, desc, authorName, authorLink string
		var lastMod int64
		if err := rows.Scan(&id, &title, &link, &desc, &authorName, &authorLink, &lastMod); err != nil {
			continue
		}

		entry := atomEntry{
			Title:   title,
			Link:    atomLink{Href: link},
			ID:      link,
			Updated: formatTime(lastMod),
		}
		if authorName != "" {
			entry.Author = &atomAuthor{Name: authorName, URI: authorLink}
		}
		if desc != "" {
			entry.Content = &atomContent{Type: "html", Content: desc}
		}

		feed.Entries = append(feed.Entries, entry)
		if feed.Updated == "" {
			feed.Updated = entry.Updated
		}
	}

	if feed.Updated == "" {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}

func (h *Handler) packagesFeed(w http.ResponseWriter, r *http.Request) {
	rows, err := h.db.QueryContext(r.Context(),
		`SELECT p.name, p.version, COALESCE(p.description, ''), COALESCE(p.build_date, 0),
		        COALESCE(p.packager_name, ''), r.name, r.architecture
		 FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE r.testing = 0
		 ORDER BY p.build_date DESC LIMIT 25`)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	feed := atomFeed{
		XMLNS:   "http://www.w3.org/2005/Atom",
		Title:   "Pakete - www.archlinux.de",
		Link:    atomLink{Href: "/packages"},
		ID:      "/packages/feed",
	}

	for rows.Next() {
		var name, version, desc, packager, repo, arch string
		var buildDate int64
		if err := rows.Scan(&name, &version, &desc, &buildDate, &packager, &repo, &arch); err != nil {
			continue
		}

		url := "/packages/" + repo + "/" + arch + "/" + name
		entry := atomEntry{
			Title:   name + " " + version,
			Link:    atomLink{Href: url},
			ID:      url,
			Updated: formatTime(buildDate),
			Summary: desc,
		}
		if packager != "" {
			entry.Author = &atomAuthor{Name: packager}
		}

		feed.Entries = append(feed.Entries, entry)
		if feed.Updated == "" {
			feed.Updated = entry.Updated
		}
	}

	if feed.Updated == "" {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}

func (h *Handler) releasesFeed(w http.ResponseWriter, r *http.Request) {
	rows, err := h.db.QueryContext(r.Context(),
		`SELECT version, available, COALESCE(info, ''), COALESCE(release_date, 0), COALESCE(file_name, '')
		 FROM release WHERE available = 1 ORDER BY release_date DESC`)
	if err != nil {
		http.Error(w, "internal error", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	feed := atomFeed{
		XMLNS:   "http://www.w3.org/2005/Atom",
		Title:   "Releases - www.archlinux.de",
		Link:    atomLink{Href: "/releases"},
		ID:      "/releases/feed",
	}

	for rows.Next() {
		var version, info, fileName string
		var available bool
		var releaseDate int64
		if err := rows.Scan(&version, &available, &info, &releaseDate, &fileName); err != nil {
			continue
		}

		url := "/releases/" + version
		entry := atomEntry{
			Title:   "Arch Linux " + version,
			Link:    atomLink{Href: url},
			ID:      url,
			Updated: formatTime(releaseDate),
			Author:  &atomAuthor{Name: "Arch Linux"},
			Summary: info,
		}

		feed.Entries = append(feed.Entries, entry)
		if feed.Updated == "" {
			feed.Updated = entry.Updated
		}
	}

	if feed.Updated == "" {
		feed.Updated = formatTime(time.Now().Unix())
	}

	writeAtom(w, feed)
}
