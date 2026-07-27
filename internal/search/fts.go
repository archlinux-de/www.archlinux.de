package search

import "strings"

// FTSQuery builds an FTS5 MATCH expression from a user search string.
// Splits on hyphens, quotes each term, and adds a prefix wildcard to the last term.
func FTSQuery(search string) string {
	search = strings.ReplaceAll(search, `"`, `""`)
	terms := strings.Fields(strings.ReplaceAll(search, "-", " "))
	if len(terms) == 0 {
		return `""`
	}
	var b strings.Builder
	for i, t := range terms {
		if i > 0 {
			b.WriteByte(' ')
		}
		b.WriteByte('"')
		b.WriteString(t)
		b.WriteByte('"')
	}
	b.WriteByte('*')
	return b.String()
}

// FallbackFTSQuery returns a prefix query for the first part of a hyphenated
// package-like search. It is intended for use only after FTSQuery finds no results.
func FallbackFTSQuery(search string) string {
	if !strings.Contains(search, "-") {
		return ""
	}

	terms := strings.Fields(strings.ReplaceAll(search, "-", " "))
	if len(terms) < 2 {
		return ""
	}
	return FTSQuery(terms[0])
}
