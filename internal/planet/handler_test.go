package planet

import (
	"database/sql"
	"encoding/xml"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"archded/internal/database"
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

func insertTestData(t *testing.T) *sql.DB {
	t.Helper()
	return setupTestDB(t,
		`INSERT INTO planet_feed (url, title, description, link, last_modified) VALUES
			('https://example.com/feed', 'Example Blog', 'A blog', 'https://example.com', 1700000000),
			('https://other.com/feed.xml', 'Other Blog', 'Another blog', 'https://other.com', 1699000000)`,
		`INSERT INTO planet_item (link, feed_url, title, description, author_name, author_uri, last_modified) VALUES
			('https://example.com/post/1', 'https://example.com/feed', 'First Post', '<p>Content one</p>', 'Alice', 'https://example.com/alice', 1700000000),
			('https://other.com/article/1', 'https://other.com/feed.xml', 'Other Post', '<p>Content two</p>', 'Bob', '', 1699500000),
			('https://example.com/post/2', 'https://example.com/feed', 'Old Post', '<p>Old content</p>', 'Alice', 'https://example.com/alice', 1698000000)`,
	)
}

func TestAtomFeed_Structure(t *testing.T) {
	db := insertTestData(t)
	h := NewHandler(NewRepository(db))
	mux := http.NewServeMux()
	h.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/planet/atom.xml", nil))

	if rr.Code != http.StatusOK {
		t.Fatalf("status = %d", rr.Code)
	}
	if ct := rr.Header().Get("Content-Type"); ct != "application/atom+xml; charset=UTF-8" {
		t.Errorf("content-type = %q", ct)
	}

	var feed planetAtomFeed
	if err := xml.Unmarshal(rr.Body.Bytes(), &feed); err != nil {
		t.Fatalf("xml parse: %v", err)
	}

	if feed.Title != "Arch Linux Planet" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Subtitle.Text != "planet.archlinux.de" {
		t.Errorf("subtitle = %q", feed.Subtitle.Text)
	}
	if len(feed.Entries) != 3 {
		t.Fatalf("entries = %d, want 3", len(feed.Entries))
	}

	// Sorted by last_modified DESC
	first := feed.Entries[0]
	if first.Title != "First Post" {
		t.Errorf("first entry title = %q", first.Title)
	}
	if first.Author.Name != "Alice" {
		t.Errorf("first author = %q", first.Author.Name)
	}
	if first.Author.URI != "https://example.com/alice" {
		t.Errorf("first author uri = %q", first.Author.URI)
	}
	if first.Summary.Type != "html" {
		t.Errorf("summary type = %q", first.Summary.Type)
	}
	if first.Source == nil {
		t.Fatal("missing source")
	}
	if first.Source.Title != "Example Blog" {
		t.Errorf("source title = %q", first.Source.Title)
	}
	if first.Source.ID != "https://example.com/feed" {
		t.Errorf("source id = %q", first.Source.ID)
	}
	// Source updated must use the feed's last_modified, not the item's
	wantSourceUpdated := formatAtomTime(1700000000)
	if first.Source.Updated != wantSourceUpdated {
		t.Errorf("source updated = %q, want feed time %q", first.Source.Updated, wantSourceUpdated)
	}

	// Bob has no URI
	second := feed.Entries[1]
	if second.Author.URI != "" {
		t.Errorf("bob should have no uri, got %q", second.Author.URI)
	}
}

// test-only structs for RSS unmarshalling (Go xml needs full namespace URIs)
type testRSSFeed struct {
	Version string         `xml:"version,attr"`
	Channel testRSSChannel `xml:"channel"`
}

type testRSSChannel struct {
	Title       string        `xml:"title"`
	Description string        `xml:"description"`
	Items       []testRSSItem `xml:"item"`
}

type testRSSItem struct {
	Title   string          `xml:"title"`
	Creator string          `xml:"http://purl.org/dc/elements/1.1/ creator"`
	GUID    planetRSSGUID   `xml:"guid"`
	Source  planetRSSSource `xml:"source"`
}

func TestRSSFeed_Structure(t *testing.T) {
	db := insertTestData(t)
	h := NewHandler(NewRepository(db))
	mux := http.NewServeMux()
	h.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/planet/rss.xml", nil))

	if rr.Code != http.StatusOK {
		t.Fatalf("status = %d", rr.Code)
	}
	if ct := rr.Header().Get("Content-Type"); ct != "application/rss+xml; charset=UTF-8" {
		t.Errorf("content-type = %q", ct)
	}

	var feed testRSSFeed
	if err := xml.Unmarshal(rr.Body.Bytes(), &feed); err != nil {
		t.Fatalf("xml parse: %v", err)
	}

	if feed.Version != "2.0" {
		t.Errorf("version = %q", feed.Version)
	}
	ch := feed.Channel
	if ch.Title != "Arch Linux Planet" {
		t.Errorf("title = %q", ch.Title)
	}
	if ch.Description != "planet.archlinux.de" {
		t.Errorf("description = %q", ch.Description)
	}
	if len(ch.Items) != 3 {
		t.Fatalf("items = %d, want 3", len(ch.Items))
	}

	first := ch.Items[0]
	if first.Title != "First Post" {
		t.Errorf("first title = %q", first.Title)
	}
	if first.Creator != "Alice" {
		t.Errorf("dc:creator = %q", first.Creator)
	}
	if first.GUID.IsPermaLink != "true" {
		t.Errorf("guid isPermaLink = %q", first.GUID.IsPermaLink)
	}
	if first.GUID.Value != "https://example.com/post/1" {
		t.Errorf("guid = %q", first.GUID.Value)
	}
	if first.Source.URL != "https://example.com/feed" {
		t.Errorf("source url = %q", first.Source.URL)
	}
	if first.Source.Title != "Example Blog" {
		t.Errorf("source title = %q", first.Source.Title)
	}

	// Verify XML contains namespaces
	body := rr.Body.String()
	if !strings.Contains(body, "xmlns:dc=") {
		t.Error("missing dc namespace")
	}
	if !strings.Contains(body, "xmlns:atom=") {
		t.Error("missing atom namespace")
	}
}

func TestFeeds_EmptyDB(t *testing.T) {
	db := setupTestDB(t)
	h := NewHandler(NewRepository(db))
	mux := http.NewServeMux()
	h.RegisterRoutes(mux)

	for _, path := range []string{"/planet/atom.xml", "/planet/rss.xml"} {
		rr := httptest.NewRecorder()
		mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, path, nil))

		if rr.Code != http.StatusOK {
			t.Errorf("%s: status = %d", path, rr.Code)
		}
	}
}
