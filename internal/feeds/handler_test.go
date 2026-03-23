package feeds

import (
	"database/sql"
	"encoding/xml"
	"net/http"
	"net/http/httptest"
	"testing"

	"archded/internal/database"
	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/releases"
)

func setupTestDB(t *testing.T, statements ...string) *sql.DB {
	t.Helper()
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range statements {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func newTestHandler(t *testing.T, statements ...string) *http.ServeMux {
	t.Helper()
	db := setupTestDB(t, statements...)
	h := NewHandler(news.NewRepository(db), packages.NewRepository(db), releases.NewRepository(db))
	mux := http.NewServeMux()
	h.RegisterRoutes(mux)
	return mux
}

func getFeed(t *testing.T, mux *http.ServeMux, path string) atomFeed {
	t.Helper()
	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, path, nil))

	if rr.Code != http.StatusOK {
		t.Fatalf("%s: expected 200, got %d", path, rr.Code)
	}
	ct := rr.Header().Get("Content-Type")
	if ct != "application/atom+xml; charset=UTF-8" {
		t.Errorf("%s: content-type = %q", path, ct)
	}

	var feed atomFeed
	if err := xml.Unmarshal(rr.Body.Bytes(), &feed); err != nil {
		t.Fatalf("%s: xml parse error: %v", path, err)
	}
	return feed
}

func TestNewsFeed_Structure(t *testing.T) {
	mux := newTestHandler(t,
		`INSERT INTO news_item (id, title, link, description, author_name, author_link, last_modified) VALUES
			(42, 'Wichtige Neuigkeit', 'https://archlinux.org/news/1', '<p>Inhalt</p>', 'Dirk', 'https://forum.archlinux.de/u/Dirk', 1700000000)`,
	)
	feed := getFeed(t, mux, "/news/feed")

	if feed.Title != "Aktuelle Arch Linux Neuigkeiten" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Icon == "" {
		t.Error("missing icon")
	}
	if feed.Logo == "" {
		t.Error("missing logo")
	}
	if len(feed.Links) != 2 {
		t.Fatalf("expected 2 links, got %d", len(feed.Links))
	}
	if feed.Links[0].Rel != "alternate" {
		t.Errorf("first link rel = %q", feed.Links[0].Rel)
	}
	if feed.Links[1].Rel != "self" {
		t.Errorf("second link rel = %q", feed.Links[1].Rel)
	}

	if len(feed.Entries) != 1 {
		t.Fatalf("expected 1 entry, got %d", len(feed.Entries))
	}
	e := feed.Entries[0]

	// ID must be numeric-only (no slug) to not break existing subscribers
	if e.ID != "http://example.com/news/42" {
		t.Errorf("entry id = %q, want numeric-only URL", e.ID)
	}
	if e.Link.Href == e.ID {
		t.Error("entry link should use slug URL, not match numeric-only ID")
	}
	if e.Link.Rel != "alternate" {
		t.Errorf("entry link rel = %q", e.Link.Rel)
	}
	if e.Title != "Wichtige Neuigkeit" {
		t.Errorf("entry title = %q", e.Title)
	}
	if e.Author == nil || e.Author.Name != "Dirk" {
		t.Error("missing or wrong author")
	}
	if e.Author.URI != "https://forum.archlinux.de/u/Dirk" {
		t.Errorf("author uri = %q", e.Author.URI)
	}
	if e.Content == nil || e.Content.Type != "html" {
		t.Error("missing or wrong content")
	}
	if e.Content.Content != "<p>Inhalt</p>" {
		t.Errorf("content = %q", e.Content.Content)
	}
	if e.Updated == "" {
		t.Error("missing updated")
	}
}

func TestPackagesFeed_Structure(t *testing.T) {
	mux := newTestHandler(t,
		`INSERT INTO repository (id, name, architecture, testing) VALUES
			(1, 'core', 'x86_64', 0),
			(2, 'extra-testing', 'x86_64', 1)`,
		`INSERT INTO package (id, repository_id, name, base, version, description, build_date, packager_name, packager_email) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1', 'The Linux kernel', 1700300000, 'Jan Steffens', 'jan@archlinux.org'),
			(2, 2, 'mesa', 'mesa', '24.0.0-1', 'Open source graphics', 1700400000, 'Laurent Carlier', 'lc@archlinux.org'),
			(3, 1, 'bash', 'bash', '5.2-1', 'The GNU Bourne Again shell', 1700200000, 'Allan', NULL)`,
	)
	feed := getFeed(t, mux, "/packages/feed")

	if feed.Title != "Aktuelle Arch Linux Pakete" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Icon == "" {
		t.Error("missing icon")
	}
	if feed.Logo == "" {
		t.Error("missing logo")
	}

	// Must include both stable and testing repos
	if len(feed.Entries) != 3 {
		t.Fatalf("expected 3 entries (stable + testing), got %d", len(feed.Entries))
	}

	// Entries sorted by build_date DESC
	mesa := feed.Entries[0]
	if mesa.Title != "mesa 24.0.0-1" {
		t.Errorf("first entry title = %q", mesa.Title)
	}
	if mesa.Author == nil {
		t.Fatal("missing author on mesa")
	}
	if mesa.Author.Email != "lc@archlinux.org" {
		t.Errorf("author email = %q", mesa.Author.Email)
	}
	if mesa.Link.Rel != "alternate" {
		t.Errorf("entry link rel = %q", mesa.Link.Rel)
	}
	if mesa.Summary != "Open source graphics" {
		t.Errorf("summary = %q", mesa.Summary)
	}

	linux := feed.Entries[1]
	if linux.Author == nil || linux.Author.Email != "jan@archlinux.org" {
		t.Error("linux entry missing packager email")
	}

	// NULL email should be omitted
	bash := feed.Entries[2]
	if bash.Author == nil || bash.Author.Name != "Allan" {
		t.Error("bash entry missing author")
	}
	if bash.Author.Email != "" {
		t.Errorf("expected empty email when NULL, got %q", bash.Author.Email)
	}
}

func TestReleasesFeed_Structure(t *testing.T) {
	mux := newTestHandler(t,
		`INSERT INTO release (version, available, info, release_date, file_name) VALUES
			('2024.01.01', 1, 'January release', 1704067200, 'archlinux-2024.01.01-x86_64.iso'),
			('2023.12.01', 1, 'December release', 1701388800, 'archlinux-2023.12.01-x86_64.iso'),
			('2023.06.01', 0, 'Old release', 1685577600, 'archlinux-2023.06.01-x86_64.iso')`,
	)
	feed := getFeed(t, mux, "/releases/feed")

	if feed.Title != "Arch Linux Releases" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Icon == "" {
		t.Error("missing icon")
	}

	// Only available releases (2 of 3)
	if len(feed.Entries) != 2 {
		t.Fatalf("expected 2 available entries, got %d", len(feed.Entries))
	}

	e := feed.Entries[0]

	// Title is just the version
	if e.Title != "2024.01.01" {
		t.Errorf("title = %q, want just version", e.Title)
	}
	if e.Link.Rel != "alternate" {
		t.Errorf("link rel = %q", e.Link.Rel)
	}
	if e.Author == nil || e.Author.Name != "Arch Linux" {
		t.Error("missing or wrong author")
	}
	if e.Summary != "Arch Linux ISO image Version 2024.01.01" {
		t.Errorf("summary = %q", e.Summary)
	}

	// Content element with ISO download link
	if e.Content == nil {
		t.Fatal("missing content element with ISO link")
	}
	if e.Content.Type != "application/x-iso9660-image" {
		t.Errorf("content type = %q", e.Content.Type)
	}
	wantSrc := "http://example.com/download/iso/2024.01.01/archlinux-2024.01.01-x86_64.iso"
	if e.Content.Src != wantSrc {
		t.Errorf("content src = %q, want %q", e.Content.Src, wantSrc)
	}
}

func TestFeeds_EmptyDB(t *testing.T) {
	mux := newTestHandler(t)

	for _, path := range []string{"/news/feed", "/packages/feed", "/releases/feed"} {
		feed := getFeed(t, mux, path)
		if feed.Updated == "" {
			t.Errorf("%s: missing updated on empty feed", path)
		}
		if feed.Icon == "" {
			t.Errorf("%s: missing icon on empty feed", path)
		}
	}
}
