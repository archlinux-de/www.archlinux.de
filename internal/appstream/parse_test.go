package appstream

import (
	"strings"
	"testing"
)

func TestParseComponentsXML(t *testing.T) {
	const xml = `<?xml version="1.0"?>
<components>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox</name>
  <summary>Web browser</summary>
  <summary xml:lang="de">Webbrowser</summary>
  <summary xml:lang="fr">Navigateur</summary>
  <description><p>Free software web browser.</p></description>
  <categories><category>WebBrowser</category><category>Network</category></categories>
  <keywords><keyword>internet</keyword><keyword>www</keyword></keywords>
</component>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox ESR</name>
  <summary>Extended support</summary>
</component>
</components>`

	acc := make(map[string][]string)
	err := ParseComponentsXML(strings.NewReader(xml), func(name string, parts []string) error {
		acc[name] = append(acc[name], parts...)
		return nil
	})
	if err != nil {
		t.Fatal(err)
	}
	got := dedupeWords(acc["firefox"])
	if got == "" {
		t.Fatal("expected merged keywords for firefox")
	}
	if !strings.Contains(got, "Webbrowser") {
		t.Errorf("expected German summary term, got %q", got)
	}
	if strings.Contains(strings.ToLower(got), "navigateur") {
		t.Errorf("did not expect French summary, got %q", got)
	}
	if !strings.Contains(got, "Network") || !strings.Contains(got, "internet") {
		t.Errorf("expected category and keyword terms, got %q", got)
	}
}
