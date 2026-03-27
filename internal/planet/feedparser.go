package planet

import (
	"encoding/xml"
	"fmt"
	"html"
	"io"
	"regexp"
	"strings"
	"time"
)

type ParsedFeed struct {
	Title       string
	Description string
	Link        string
	Items       []ParsedItem
}

type ParsedItem struct {
	Title        string
	Link         string
	Description  string
	AuthorName   string
	AuthorURI    string
	LastModified time.Time
}

func ParseFeed(r io.Reader) (ParsedFeed, error) {
	data, err := io.ReadAll(r)
	if err != nil {
		return ParsedFeed{}, fmt.Errorf("read feed: %w", err)
	}

	// Try Atom first, fall back to RSS
	var root struct {
		XMLName xml.Name
	}
	if err := xml.Unmarshal(data, &root); err != nil {
		return ParsedFeed{}, fmt.Errorf("detect feed type: %w", err)
	}

	switch root.XMLName.Local {
	case "feed":
		return parseAtom(data)
	case "rss":
		return parseRSS(data)
	default:
		return ParsedFeed{}, fmt.Errorf("unknown feed root element: %s", root.XMLName.Local)
	}
}

// Atom parsing

type atomFeedParsed struct {
	Title    string            `xml:"title"`
	Subtitle string            `xml:"subtitle"`
	Links    []atomLinkParsed  `xml:"link"`
	Entries  []atomEntryParsed `xml:"entry"`
}

type atomLinkParsed struct {
	Href string `xml:"href,attr"`
	Rel  string `xml:"rel,attr"`
}

type atomEntryParsed struct {
	Title     string           `xml:"title"`
	Links     []atomLinkParsed `xml:"link"`
	Content   string           `xml:"content"`
	Summary   string           `xml:"summary"`
	Updated   string           `xml:"updated"`
	Published string           `xml:"published"`
	Author    *struct {
		Name string `xml:"name"`
		URI  string `xml:"uri"`
	} `xml:"author"`
}

func parseAtom(data []byte) (ParsedFeed, error) {
	var raw atomFeedParsed
	if err := xml.Unmarshal(data, &raw); err != nil {
		return ParsedFeed{}, fmt.Errorf("parse atom: %w", err)
	}

	feed := ParsedFeed{
		Title:       raw.Title,
		Description: raw.Subtitle,
		Link:        atomAlternateLink(raw.Links),
	}

	for _, e := range raw.Entries {
		item := ParsedItem{
			Title: strings.TrimSpace(decodeHTMLEntities(e.Title)),
			Link:  atomAlternateLink(e.Links),
		}

		if e.Content != "" {
			item.Description = e.Content
		} else {
			item.Description = e.Summary
		}

		if e.Author != nil {
			item.AuthorName = e.Author.Name
			item.AuthorURI = e.Author.URI
		}

		item.LastModified = parseAtomDate(e.Updated, e.Published)

		if item.Link != "" {
			feed.Items = append(feed.Items, item)
		}
	}

	return feed, nil
}

func atomAlternateLink(links []atomLinkParsed) string {
	for _, l := range links {
		if l.Rel == "alternate" || l.Rel == "" {
			return l.Href
		}
	}
	if len(links) > 0 {
		return links[0].Href
	}
	return ""
}

func parseAtomDate(updated, published string) time.Time {
	for _, s := range []string{updated, published} {
		if s == "" {
			continue
		}
		if t, err := time.Parse(time.RFC3339, s); err == nil {
			return t
		}
		// Some feeds use slightly non-standard formats
		if t, err := time.Parse("2006-01-02T15:04:05Z0700", s); err == nil {
			return t
		}
	}
	return time.Time{}
}

// RSS parsing

type rssFeedParsed struct {
	Channel struct {
		Title       string          `xml:"title"`
		Link        string          `xml:"link"`
		Description string          `xml:"description"`
		Items       []rssItemParsed `xml:"item"`
	} `xml:"channel"`
}

type rssItemParsed struct {
	Title       string `xml:"title"`
	Link        string `xml:"link"`
	Description string `xml:"description"`
	GUID        string `xml:"guid"`
	PubDate     string `xml:"pubDate"`
	Author      string `xml:"author"`
	Creator     string `xml:"http://purl.org/dc/elements/1.1/ creator"`
}

func parseRSS(data []byte) (ParsedFeed, error) {
	var raw rssFeedParsed
	if err := xml.Unmarshal(data, &raw); err != nil {
		return ParsedFeed{}, fmt.Errorf("parse rss: %w", err)
	}

	ch := raw.Channel
	feed := ParsedFeed{
		Title:       ch.Title,
		Description: ch.Description,
		Link:        ch.Link,
	}

	for _, item := range ch.Items {
		p := ParsedItem{
			Title:       strings.TrimSpace(decodeHTMLEntities(item.Title)),
			Link:        item.Link,
			Description: item.Description,
		}

		if p.Link == "" {
			p.Link = item.GUID
		}

		switch {
		case item.Creator != "":
			p.AuthorName = item.Creator
		case item.Author != "":
			p.AuthorName = extractRSSAuthorName(item.Author)
		}

		p.LastModified = parseRSSDate(item.PubDate)

		if p.Link != "" {
			feed.Items = append(feed.Items, p)
		}
	}

	return feed, nil
}

// extractRSSAuthorName handles the "email (Name)" format common in RSS
var rssAuthorRe = regexp.MustCompile(`^[^(]*\(([^)]+)\)`)

func extractRSSAuthorName(s string) string {
	if m := rssAuthorRe.FindStringSubmatch(s); len(m) > 1 {
		return strings.TrimSpace(m[1])
	}
	// If no parenthesized name, return as-is (might be just a name)
	return strings.TrimSpace(s)
}

var rssDateFormats = []string{
	time.RFC1123Z,
	time.RFC822Z,
	"Mon, 2 Jan 2006 15:04:05 -0700",
	"02 Jan 2006 15:04:05 -0700",
	"2 Jan 2006 15:04:05 -0700",
}

func parseRSSDate(s string) time.Time {
	s = strings.TrimSpace(s)
	if s == "" {
		return time.Time{}
	}
	for _, layout := range rssDateFormats {
		if t, err := time.Parse(layout, s); err == nil {
			return t
		}
	}
	return time.Time{}
}

func decodeHTMLEntities(s string) string {
	return html.UnescapeString(s)
}
