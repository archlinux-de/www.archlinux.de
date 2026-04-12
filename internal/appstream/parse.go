// Package appstream parses Arch Linux AppStream component XML (from
// https://sources.archlinux.org/other/packages/archlinux-appstream-data/) and
// builds per-pkgname search text for SQLite FTS.
//
// Parsing model: encoding/xml streams tokens; we keep one open <component> in
// docParser.cur. We read <pkgname>, <keywords>/<keyword>, and <categories>/<category>.
// AppStream puts xml:lang on parent blocks; only neutral or en/de blocks are indexed.
// flush runs at </component> (and before the next <component>) to emit pkgname + terms.
package appstream

import (
	"encoding/xml"
	"errors"
	"io"
	"strings"
)

// XML element names referenced more than once in the decoder.
const (
	elKeyword  = "keyword"
	elCategory = "category"
)

// IndexTerms holds text extracted from one <component> for FTS (merged by pkgname in update.go).
type IndexTerms struct {
	Keywords   []string
	Categories []string
}

// ParseComponentsXML streams the decoder and calls fn once per completed
// <component> (same pkgname may appear many times). dedupeWords runs in the caller after merge.
func ParseComponentsXML(r io.Reader, fn func(pkgname string, terms IndexTerms) error) error {
	d := xml.NewDecoder(r)
	d.Strict = false
	p := &docParser{fn: fn, dec: d}
	for {
		tok, err := p.dec.Token()
		if errors.Is(err, io.EOF) {
			return p.flush()
		}
		if err != nil {
			return err
		}
		switch t := tok.(type) {
		case xml.StartElement:
			if err := p.startElement(t); err != nil {
				return err
			}
		case xml.EndElement:
			if err := p.endElement(t); err != nil {
				return err
			}
		case xml.CharData:
			p.charData(t)
		}
	}
}

// docParser holds decoder state between tokens.
type docParser struct {
	fn                  func(string, IndexTerms) error
	dec                 *xml.Decoder
	stack               []string
	inKeywords          bool
	keywordsBlockSkip   bool
	inKeyword           bool
	keywordSkip         bool
	inCategories        bool
	categoriesBlockSkip bool
	inCategory          bool
	categorySkip        bool
	cur                 *component
}

// flush emits cur via fn and clears it. EOF calls flush for the last component.
func (p *docParser) flush() error {
	if p.cur == nil {
		return nil
	}
	name := strings.TrimSpace(p.cur.pkgname)
	terms := IndexTerms{
		Keywords:   append([]string(nil), p.cur.keywords...),
		Categories: append([]string(nil), p.cur.categories...),
	}
	p.cur = nil
	if name == "" {
		return nil
	}
	return p.fn(name, terms)
}

// startElement pushes stack; on <component> flushes the previous component then starts a new cur.
func (p *docParser) startElement(t xml.StartElement) error {
	local := t.Name.Local
	p.stack = append(p.stack, local)

	switch local {
	case "component":
		if err := p.flush(); err != nil {
			return err
		}
		p.cur = &component{}
	case "keywords":
		p.inKeywords = true
		p.keywordsBlockSkip = !keywordLangAccepted(t.Attr)
	case elKeyword:
		if p.inKeywords {
			p.inKeyword = true
			p.keywordSkip = !keywordLangAccepted(t.Attr)
		}
	case "categories":
		p.inCategories = true
		p.categoriesBlockSkip = !keywordLangAccepted(t.Attr)
	case elCategory:
		if p.inCategories {
			p.inCategory = true
			p.categorySkip = !keywordLangAccepted(t.Attr)
		}
	}
	return nil
}

// endElement pops stack; on </component> flushes the finished component.
func (p *docParser) endElement(t xml.EndElement) error {
	local := t.Name.Local
	if len(p.stack) == 0 {
		return nil
	}
	p.stack = p.stack[:len(p.stack)-1]

	switch local {
	case "component":
		return p.flush()
	case "keywords":
		p.inKeywords = false
		p.inKeyword = false
		p.keywordsBlockSkip = false
	case elKeyword:
		p.inKeyword = false
		p.keywordSkip = false
	case "categories":
		p.inCategories = false
		p.inCategory = false
		p.categoriesBlockSkip = false
	case elCategory:
		p.inCategory = false
		p.categorySkip = false
	}
	return nil
}

// charData collects pkgname, <keyword>, and <category> text.
func (p *docParser) charData(t xml.CharData) {
	if p.cur == nil {
		return
	}
	text := strings.TrimSpace(string(t))
	if text == "" {
		return
	}

	parent := ""
	if len(p.stack) > 0 {
		parent = p.stack[len(p.stack)-1]
	}

	switch parent {
	case "pkgname":
		p.cur.pkgname += text
	case elKeyword:
		if p.inKeyword && !p.keywordsBlockSkip && !p.keywordSkip {
			p.cur.keywords = append(p.cur.keywords, text)
		}
	case elCategory:
		if p.inCategory && !p.categoriesBlockSkip && !p.categorySkip {
			p.cur.categories = append(p.cur.categories, text)
		}
	}
}

// keywordLangAccepted is used for <keywords>, <keyword>, <categories>, and <category>
// start tags: true if there is no xml:lang, or it is en/de (including BCP47 prefixes like de-DE).
func keywordLangAccepted(attrs []xml.Attr) bool {
	for _, a := range attrs {
		if a.Name.Local != "lang" || a.Value == "" {
			continue
		}
		v := strings.ToLower(strings.TrimSpace(a.Value))
		if i := strings.IndexByte(v, '-'); i > 0 {
			v = v[:i]
		}
		return v == "en" || v == "de"
	}
	return true
}

// component is one AppStream <component> being accumulated until flush.
type component struct {
	pkgname    string
	keywords   []string
	categories []string
}

// dedupeWords joins fragments, removes English/German stop words, and deduplicates
// tokens (case-insensitive) for FTS.
func dedupeWords(parts []string) string {
	seen := make(map[string]struct{})
	var b strings.Builder
	for _, part := range parts {
		for _, w := range strings.Fields(part) {
			key := strings.ToLower(w)
			if _, ok := stopword[key]; ok {
				continue
			}
			if _, ok := seen[key]; ok {
				continue
			}
			seen[key] = struct{}{}
			if b.Len() > 0 {
				b.WriteByte(' ')
			}
			b.WriteString(w)
		}
	}
	return b.String()
}
