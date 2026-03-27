package planet

import (
	"context"
	"fmt"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestSyncToDatabase(t *testing.T) {
	db := setupTestDB(t)
	ctx := context.Background()

	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title:       "Example Blog",
				Description: "A blog",
				Link:        "https://example.com",
				Items: []ParsedItem{
					{
						Title:       "Post One",
						Link:        "https://example.com/1",
						Description: "<p>Content</p>",
						AuthorName:  "Alice",
						AuthorURI:   "https://example.com/alice",
					},
				},
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	// Verify feed was inserted
	var feedTitle string
	if err := db.QueryRow("SELECT title FROM planet_feed WHERE url = ?", "https://example.com/feed").Scan(&feedTitle); err != nil {
		t.Fatal(err)
	}
	if feedTitle != "Example Blog" {
		t.Errorf("feed title = %q", feedTitle)
	}

	// Verify item was inserted
	var itemTitle, authorName string
	if err := db.QueryRow("SELECT title, author_name FROM planet_item WHERE link = ?", "https://example.com/1").Scan(&itemTitle, &authorName); err != nil {
		t.Fatal(err)
	}
	if itemTitle != "Post One" {
		t.Errorf("item title = %q", itemTitle)
	}
	if authorName != "Alice" {
		t.Errorf("author = %q", authorName)
	}
}

func TestSyncToDatabase_AuthorFallsBackToFeedTitle(t *testing.T) {
	db := setupTestDB(t)
	ctx := context.Background()

	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title: "My Blog",
				Items: []ParsedItem{
					{
						Title: "Post",
						Link:  "https://example.com/1",
					},
				},
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	var authorName string
	if err := db.QueryRow("SELECT author_name FROM planet_item WHERE link = ?", "https://example.com/1").Scan(&authorName); err != nil {
		t.Fatal(err)
	}
	if authorName != "My Blog" {
		t.Errorf("author should fall back to feed title, got %q", authorName)
	}
}

func TestSyncToDatabase_DeletesStaleItems(t *testing.T) {
	db := setupTestDB(t,
		`INSERT INTO planet_feed (url, title, link) VALUES ('https://example.com/feed', 'Blog', 'https://example.com')`,
		`INSERT INTO planet_item (link, feed_url, title) VALUES ('https://example.com/old', 'https://example.com/feed', 'Old Post')`,
	)
	ctx := context.Background()

	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title: "Blog",
				Link:  "https://example.com",
				Items: []ParsedItem{
					{Title: "New Post", Link: "https://example.com/new"},
				},
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	var count int
	if err := db.QueryRow("SELECT COUNT(*) FROM planet_item").Scan(&count); err != nil {
		t.Fatal(err)
	}
	if count != 1 {
		t.Errorf("items = %d, want 1 (old item should be deleted)", count)
	}

	var title string
	if err := db.QueryRow("SELECT title FROM planet_item").Scan(&title); err != nil {
		t.Fatal(err)
	}
	if title != "New Post" {
		t.Errorf("remaining item = %q", title)
	}
}

func TestSyncToDatabase_DeletesStaleFeed(t *testing.T) {
	db := setupTestDB(t,
		`INSERT INTO planet_feed (url, title, link) VALUES ('https://removed.com/feed', 'Old Blog', 'https://removed.com')`,
	)
	ctx := context.Background()

	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title: "Blog",
				Link:  "https://example.com",
				Items: []ParsedItem{
					{Title: "Post", Link: "https://example.com/1"},
				},
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	var count int
	if err := db.QueryRow("SELECT COUNT(*) FROM planet_feed").Scan(&count); err != nil {
		t.Fatal(err)
	}
	if count != 1 {
		t.Errorf("feeds = %d, want 1", count)
	}
}

func TestSyncToDatabase_ZeroItemsPreservesExisting(t *testing.T) {
	db := setupTestDB(t,
		`INSERT INTO planet_feed (url, title, link) VALUES ('https://example.com/feed', 'Blog', 'https://example.com')`,
		`INSERT INTO planet_item (link, feed_url, title) VALUES ('https://example.com/1', 'https://example.com/feed', 'Existing Post')`,
	)
	ctx := context.Background()

	// Feed returns zero items (transient failure / empty response)
	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title: "Blog",
				Link:  "https://example.com",
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	var count int
	if err := db.QueryRow("SELECT COUNT(*) FROM planet_item").Scan(&count); err != nil {
		t.Fatal(err)
	}
	if count != 1 {
		t.Errorf("items = %d, want 1 (zero-items feed must not delete existing items)", count)
	}
}

func TestSyncToDatabase_SanitizesHTML(t *testing.T) {
	db := setupTestDB(t)
	ctx := context.Background()

	results := []fetchResult{
		{
			url: "https://example.com/feed",
			feed: ParsedFeed{
				Title: "Blog",
				Items: []ParsedItem{
					{
						Title:       "Post",
						Link:        "https://example.com/1",
						Description: `<p>Safe</p><script>alert("xss")</script><img src=x onerror=alert(1)>`,
					},
				},
			},
		},
	}

	if err := syncToDatabase(ctx, db, results); err != nil {
		t.Fatal(err)
	}

	var desc string
	if err := db.QueryRow("SELECT description FROM planet_item WHERE link = ?", "https://example.com/1").Scan(&desc); err != nil {
		t.Fatal(err)
	}
	if desc != "<p>Safe</p>" {
		t.Errorf("description should be sanitized, got %q", desc)
	}
}

func TestFetchAllFeeds_Concurrent(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = fmt.Fprintf(w, `<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Feed %s</title>
  <entry>
    <title>Post</title>
    <link rel="alternate" href="http://example.com%s/1"/>
    <updated>2025-01-01T00:00:00Z</updated>
  </entry>
</feed>`, r.URL.Path, r.URL.Path)
	}))
	defer server.Close()

	urls := []string{
		server.URL + "/a",
		server.URL + "/b",
		server.URL + "/c",
	}

	results := fetchAllFeeds(context.Background(), urls)
	if len(results) != 3 {
		t.Fatalf("results = %d, want 3", len(results))
	}

	for i, r := range results {
		if r.err != nil {
			t.Errorf("result[%d] error: %v", i, r.err)
		}
		if len(r.feed.Items) != 1 {
			t.Errorf("result[%d] items = %d", i, len(r.feed.Items))
		}
	}
}
