// Package appstream parses Arch Linux AppStream component XML (from
// https://sources.archlinux.org/other/packages/archlinux-appstream-data/) and
// builds per-pkgname search text for SQLite FTS.
//
// Parsing model: encoding/xml streams tokens; we keep one open <component> in
// docParser.cur. We read <pkgname>, <keywords>/<keyword>, and <categories>/<category>.
// AppStream puts xml:lang on parent blocks; only neutral or en/de blocks are indexed —
// rejected blocks are skipped wholesale via xml.Decoder.Skip. flush runs at </component>
// (and before the next <component>) to emit pkgname + terms.
package appstream

import (
	"encoding/xml"
	"errors"
	"io"
	"strings"
)

// IndexTerms holds text extracted from one <component> for FTS (merged by pkgname in update.go).
type IndexTerms struct {
	Keywords   []string
	Categories []string
}

// ParseComponentsXML streams the decoder and calls fn once per completed
// <component> (same pkgname may appear many times). dedupeWords runs in the caller after merge.
func ParseComponentsXML(r io.Reader, fn func(pkgname string, terms IndexTerms) error) error {
	p := &docParser{fn: fn, dec: xml.NewDecoder(r)}
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
			p.endElement(t)
		case xml.CharData:
			p.charData(t)
		}
	}
}

// docParser holds decoder state between tokens. Rejected <keywords>/<categories>
// blocks are skipped by the decoder so we never see their children — no skip flags needed.
type docParser struct {
	fn           func(string, IndexTerms) error
	dec          *xml.Decoder
	cur          *component
	inPkgname    bool
	inKeywords   bool
	inKeyword    bool
	inCategories bool
	inCategory   bool
}

// flush emits cur via fn and clears it. EOF calls flush for the last component.
func (p *docParser) flush() error {
	if p.cur == nil {
		return nil
	}
	name := strings.TrimSpace(p.cur.pkgname)
	terms := IndexTerms{Keywords: p.cur.keywords, Categories: p.cur.categories}
	p.cur = nil
	if name == "" {
		return nil
	}
	return p.fn(name, terms)
}

func (p *docParser) startElement(t xml.StartElement) error {
	switch t.Name.Local {
	case "component":
		if err := p.flush(); err != nil {
			return err
		}
		p.cur = &component{}
	case "pkgname":
		if p.cur != nil {
			p.inPkgname = true
		}
	case "keywords":
		if !keywordLangAccepted(t.Attr) {
			return p.dec.Skip()
		}
		p.inKeywords = true
	case "keyword":
		if !p.inKeywords {
			return nil
		}
		if !keywordLangAccepted(t.Attr) {
			return p.dec.Skip()
		}
		p.inKeyword = true
	case "categories":
		if !keywordLangAccepted(t.Attr) {
			return p.dec.Skip()
		}
		p.inCategories = true
	case "category":
		if !p.inCategories {
			return nil
		}
		if !keywordLangAccepted(t.Attr) {
			return p.dec.Skip()
		}
		p.inCategory = true
	}
	return nil
}

func (p *docParser) endElement(t xml.EndElement) {
	switch t.Name.Local {
	case "component":
		_ = p.flush()
	case "pkgname":
		p.inPkgname = false
	case "keywords":
		p.inKeywords = false
	case "keyword":
		p.inKeyword = false
	case "categories":
		p.inCategories = false
	case "category":
		p.inCategory = false
	}
}

func (p *docParser) charData(t xml.CharData) {
	if p.cur == nil {
		return
	}
	var dst *[]string
	switch {
	case p.inPkgname:
		text := strings.TrimSpace(string(t))
		if text != "" {
			p.cur.pkgname += text
		}
		return
	case p.inKeyword:
		dst = &p.cur.keywords
	case p.inCategory:
		dst = &p.cur.categories
	default:
		return
	}
	text := strings.TrimSpace(string(t))
	if text != "" {
		*dst = append(*dst, text)
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
