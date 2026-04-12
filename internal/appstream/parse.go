// Package appstream parses Arch Linux AppStream component XML (from
// https://sources.archlinux.org/other/packages/archlinux-appstream-data/) and
// builds per-pkgname search text for SQLite FTS.
package appstream

import (
	"encoding/xml"
	"io"
	"strings"
)

// ParseComponentsXML reads a Components-*.xml stream and calls fn for each
// <component>, as soon as the element is complete. Multiple components with the
// same <pkgname> produce multiple invocations; the caller merges by name. This
// matches the streaming style of pacmandb.Parse: only one component is held in
// memory at a time.
func ParseComponentsXML(r io.Reader, fn func(pkgname string, parts []string) error) error {
	d := xml.NewDecoder(r)
	d.Strict = false

	var (
		stack      []string
		muteLeaf   []bool
		inKeywords bool
		inKeyword  bool
		inCats     bool
		inDesc     int
	)
	var cur *component

	flush := func() error {
		if cur == nil {
			return nil
		}
		name := strings.TrimSpace(cur.pkgname)
		parts := append([]string(nil), cur.parts...)
		cur = nil
		if name == "" {
			return nil
		}
		return fn(name, parts)
	}

	for {
		tok, err := d.Token()
		if err == io.EOF {
			if err := flush(); err != nil {
				return err
			}
			break
		}
		if err != nil {
			return err
		}

		switch t := tok.(type) {
		case xml.StartElement:
			local := t.Name.Local
			stack = append(stack, local)
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
			muteLeaf = append(muteLeaf, muted)

			switch local {
			case "component":
				if err := flush(); err != nil {
					return err
				}
				cur = &component{}
			case "keywords":
				inKeywords = true
			case "keyword":
				if inKeywords {
					inKeyword = true
				}
			case "categories":
				inCats = true
			case "description":
				inDesc++
			case "p":
				// paragraph inside description
			}

		case xml.EndElement:
			local := t.Name.Local
			if len(stack) == 0 {
				continue
			}
			stack = stack[:len(stack)-1]
			if len(muteLeaf) > 0 {
				muteLeaf = muteLeaf[:len(muteLeaf)-1]
			}

			switch local {
			case "component":
				if err := flush(); err != nil {
					return err
				}
			case "keywords":
				inKeywords = false
				inKeyword = false
			case "keyword":
				inKeyword = false
			case "categories":
				inCats = false
			case "description":
				if inDesc > 0 {
					inDesc--
				}
			}

		case xml.CharData:
			if cur == nil {
				continue
			}
			muted := len(muteLeaf) > 0 && muteLeaf[len(muteLeaf)-1]
			if muted {
				continue
			}
			text := strings.TrimSpace(string(t))
			if text == "" {
				continue
			}

			parent := ""
			if len(stack) > 0 {
				parent = stack[len(stack)-1]
			}

			switch parent {
			case "pkgname":
				cur.pkgname += text
			case "name", "summary":
				cur.parts = append(cur.parts, text)
			case "category":
				if inCats {
					cur.parts = append(cur.parts, text)
				}
			case "keyword":
				if inKeyword {
					cur.parts = append(cur.parts, text)
				}
			case "p":
				if inDesc > 0 {
					cur.parts = append(cur.parts, text)
				}
			}
		}
	}

	return nil
}

type component struct {
	pkgname string
	parts   []string
}

func dedupeWords(parts []string) string {
	seen := make(map[string]struct{})
	var b strings.Builder
	for _, p := range parts {
		for _, w := range strings.Fields(p) {
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
