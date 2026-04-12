// Package appstream parses Arch Linux AppStream component XML (from
// https://sources.archlinux.org/other/packages/archlinux-appstream-data/) and
// builds per-pkgname search text for SQLite FTS.
package appstream

import (
	"encoding/xml"
	"errors"
	"io"
	"strings"
)

// XML element names referenced more than once in the decoder.
const (
	elKeyword = "keyword"
)

// ParseComponentsXML reads a Components-*.xml stream and calls fn for each
// <component>, as soon as the element is complete. Multiple components with the
// same <pkgname> produce multiple invocations; the caller merges by name. This
// matches the streaming style of pacmandb.Parse: only one component is held in
// memory at a time.
func ParseComponentsXML(r io.Reader, fn func(pkgname string, parts []string) error) error {
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

type docParser struct {
	fn         func(string, []string) error
	dec        *xml.Decoder
	stack      []string
	muteLeaf   []bool
	inKeywords bool
	inKeyword  bool
	inCats     bool
	inDesc     int
	cur        *component
}

func (p *docParser) flush() error {
	if p.cur == nil {
		return nil
	}
	name := strings.TrimSpace(p.cur.pkgname)
	parts := append([]string(nil), p.cur.parts...)
	p.cur = nil
	if name == "" {
		return nil
	}
	return p.fn(name, parts)
}

func (p *docParser) startElement(t xml.StartElement) error {
	local := t.Name.Local
	p.stack = append(p.stack, local)
	muted := false
	if local == "name" || local == "summary" {
		for _, a := range t.Attr {
			if a.Name.Local != "lang" || a.Value == "" {
				continue
			}
			if a.Value != "en" && a.Value != "de" {
				muted = true
				break
			}
		}
	}
	p.muteLeaf = append(p.muteLeaf, muted)

	switch local {
	case "component":
		if err := p.flush(); err != nil {
			return err
		}
		p.cur = &component{}
	case "keywords":
		p.inKeywords = true
	case elKeyword:
		if p.inKeywords {
			p.inKeyword = true
		}
	case "categories":
		p.inCats = true
	case "description":
		p.inDesc++
	}
	return nil
}

func (p *docParser) endElement(t xml.EndElement) error {
	local := t.Name.Local
	if len(p.stack) == 0 {
		return nil
	}
	p.stack = p.stack[:len(p.stack)-1]
	if len(p.muteLeaf) > 0 {
		p.muteLeaf = p.muteLeaf[:len(p.muteLeaf)-1]
	}

	switch local {
	case "component":
		return p.flush()
	case "keywords":
		p.inKeywords = false
		p.inKeyword = false
	case elKeyword:
		p.inKeyword = false
	case "categories":
		p.inCats = false
	case "description":
		if p.inDesc > 0 {
			p.inDesc--
		}
	}
	return nil
}

func (p *docParser) charData(t xml.CharData) {
	if p.cur == nil {
		return
	}
	muted := len(p.muteLeaf) > 0 && p.muteLeaf[len(p.muteLeaf)-1]
	if muted {
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
	case "name", "summary":
		p.cur.parts = append(p.cur.parts, text)
	case "category":
		if p.inCats {
			p.cur.parts = append(p.cur.parts, text)
		}
	case elKeyword:
		if p.inKeyword {
			p.cur.parts = append(p.cur.parts, text)
		}
	case "p":
		if p.inDesc > 0 {
			p.cur.parts = append(p.cur.parts, text)
		}
	}
}

type component struct {
	pkgname string
	parts   []string
}

func dedupeWords(parts []string) string {
	seen := make(map[string]struct{})
	var b strings.Builder
	for _, part := range parts {
		for _, w := range strings.Fields(part) {
			key := strings.ToLower(w)
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
