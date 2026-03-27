package planet

import (
	"strings"
	"testing"
	"time"
)

func TestParseAtomFeed(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Test Blog</title>
  <subtitle>A test blog</subtitle>
  <link rel="alternate" href="https://example.com/"/>
  <link rel="self" href="https://example.com/feed"/>
  <entry>
    <title>First Post</title>
    <link rel="alternate" href="https://example.com/post/1"/>
    <content type="html">&lt;p&gt;Hello world&lt;/p&gt;</content>
    <updated>2025-01-15T10:30:00Z</updated>
    <author>
      <name>Alice</name>
      <uri>https://example.com/alice</uri>
    </author>
  </entry>
  <entry>
    <title>Second Post</title>
    <link rel="alternate" href="https://example.com/post/2"/>
    <summary type="html">&lt;p&gt;Summary only&lt;/p&gt;</summary>
    <published>2025-01-10T08:00:00+01:00</published>
  </entry>
</feed>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Title != "Test Blog" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Description != "A test blog" {
		t.Errorf("description = %q", feed.Description)
	}
	if feed.Link != "https://example.com/" {
		t.Errorf("link = %q", feed.Link)
	}
	if len(feed.Items) != 2 {
		t.Fatalf("items = %d, want 2", len(feed.Items))
	}

	first := feed.Items[0]
	if first.Title != "First Post" {
		t.Errorf("first title = %q", first.Title)
	}
	if first.Link != "https://example.com/post/1" {
		t.Errorf("first link = %q", first.Link)
	}
	if first.Description != "<p>Hello world</p>" {
		t.Errorf("first description = %q", first.Description)
	}
	if first.AuthorName != "Alice" {
		t.Errorf("first author name = %q", first.AuthorName)
	}
	if first.AuthorURI != "https://example.com/alice" {
		t.Errorf("first author uri = %q", first.AuthorURI)
	}
	if !first.LastModified.Equal(time.Date(2025, 1, 15, 10, 30, 0, 0, time.UTC)) {
		t.Errorf("first date = %v", first.LastModified)
	}

	second := feed.Items[1]
	if second.Description != "<p>Summary only</p>" {
		t.Errorf("second description (from summary) = %q", second.Description)
	}
	if second.AuthorName != "" {
		t.Errorf("second author should be empty, got %q", second.AuthorName)
	}
}

func TestParseAtomFeed_CDATA(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title type="html"><![CDATA[My &#8220;Blog&#8221;]]></title>
  <entry>
    <title type="html"><![CDATA[Post &amp; Title]]></title>
    <link rel="alternate" href="https://example.com/post/1"/>
    <content type="html"><![CDATA[<p>Content with <strong>HTML</strong></p>]]></content>
    <updated>2025-01-01T00:00:00Z</updated>
  </entry>
</feed>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Items[0].Title != `Post & Title` {
		t.Errorf("title = %q", feed.Items[0].Title)
	}
	if feed.Items[0].Description != `<p>Content with <strong>HTML</strong></p>` {
		t.Errorf("description = %q", feed.Items[0].Description)
	}
}

func TestParseRSSFeed(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>Test RSS Blog</title>
    <link>https://example.com</link>
    <description>An RSS blog</description>
    <item>
      <title>RSS Post</title>
      <link>https://example.com/rss/1</link>
      <description>&lt;p&gt;RSS content&lt;/p&gt;</description>
      <pubDate>Sun, 06 Oct 2024 10:24:38 +0000</pubDate>
      <author>bob@example.com (Bob Smith)</author>
      <guid>https://example.com/rss/1</guid>
    </item>
    <item>
      <title>No Author Post</title>
      <link>https://example.com/rss/2</link>
      <description>Plain text</description>
      <pubDate>Thu, 21 Feb 2019 21:04:15 +0000</pubDate>
    </item>
  </channel>
</rss>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Title != "Test RSS Blog" {
		t.Errorf("title = %q", feed.Title)
	}
	if feed.Link != "https://example.com" {
		t.Errorf("link = %q", feed.Link)
	}
	if feed.Description != "An RSS blog" {
		t.Errorf("description = %q", feed.Description)
	}
	if len(feed.Items) != 2 {
		t.Fatalf("items = %d, want 2", len(feed.Items))
	}

	first := feed.Items[0]
	if first.Title != "RSS Post" {
		t.Errorf("first title = %q", first.Title)
	}
	if first.Link != "https://example.com/rss/1" {
		t.Errorf("first link = %q", first.Link)
	}
	if first.Description != "<p>RSS content</p>" {
		t.Errorf("first description = %q", first.Description)
	}
	if first.AuthorName != "Bob Smith" {
		t.Errorf("first author = %q, want %q", first.AuthorName, "Bob Smith")
	}
	if first.LastModified.IsZero() {
		t.Error("first date is zero")
	}

	second := feed.Items[1]
	if second.AuthorName != "" {
		t.Errorf("second author should be empty, got %q", second.AuthorName)
	}
}

func TestParseRSSFeed_DCCreator(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>Blog</title>
    <link>https://example.com</link>
    <item>
      <title>Post</title>
      <link>https://example.com/1</link>
      <dc:creator>Jane Doe</dc:creator>
      <pubDate>Mon, 01 Jan 2024 00:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Items[0].AuthorName != "Jane Doe" {
		t.Errorf("author = %q, want Jane Doe", feed.Items[0].AuthorName)
	}
}

func TestParseRSSFeed_GUIDFallback(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
  <channel>
    <title>Blog</title>
    <link>https://example.com</link>
    <item>
      <title>Post</title>
      <guid>https://example.com/guid-link</guid>
      <pubDate>Mon, 01 Jan 2024 00:00:00 +0000</pubDate>
    </item>
  </channel>
</rss>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Items[0].Link != "https://example.com/guid-link" {
		t.Errorf("link = %q, want GUID fallback", feed.Items[0].Link)
	}
}

func TestExtractRSSAuthorName(t *testing.T) {
	tests := []struct {
		input string
		want  string
	}{
		{"bob@example.com (Bob Smith)", "Bob Smith"},
		{"alice@example.com (Alice)", "Alice"},
		{"Just A Name", "Just A Name"},
		{"", ""},
	}
	for _, tt := range tests {
		got := extractRSSAuthorName(tt.input)
		if got != tt.want {
			t.Errorf("extractRSSAuthorName(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}

func TestParseRSSDate(t *testing.T) {
	tests := []struct {
		input string
		want  time.Time
	}{
		{"Sun, 06 Oct 2024 10:24:38 +0000", time.Date(2024, 10, 6, 10, 24, 38, 0, time.UTC)},
		{"Thu, 21 Feb 2019 21:04:15 +0000", time.Date(2019, 2, 21, 21, 4, 15, 0, time.UTC)},
		{"Mon, 09 Sep 2024 00:00:00 +0000", time.Date(2024, 9, 9, 0, 0, 0, 0, time.UTC)},
		{"", time.Time{}},
	}
	for _, tt := range tests {
		got := parseRSSDate(tt.input)
		if !got.Equal(tt.want) {
			t.Errorf("parseRSSDate(%q) = %v, want %v", tt.input, got, tt.want)
		}
	}
}

func TestParseFeed_UnknownFormat(t *testing.T) {
	_, err := ParseFeed(strings.NewReader(`<html><body>Not a feed</body></html>`))
	if err == nil {
		t.Error("expected error for unknown format")
	}
}

func TestAtomContentPreferredOverSummary(t *testing.T) {
	input := `<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title>Blog</title>
  <entry>
    <title>Post</title>
    <link rel="alternate" href="https://example.com/1"/>
    <content type="html">&lt;p&gt;Full content&lt;/p&gt;</content>
    <summary type="html">&lt;p&gt;Short summary&lt;/p&gt;</summary>
    <updated>2025-01-01T00:00:00Z</updated>
  </entry>
</feed>`

	feed, err := ParseFeed(strings.NewReader(input))
	if err != nil {
		t.Fatal(err)
	}

	if feed.Items[0].Description != "<p>Full content</p>" {
		t.Errorf("should prefer content over summary, got %q", feed.Items[0].Description)
	}
}
