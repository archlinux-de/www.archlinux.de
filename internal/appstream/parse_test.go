package appstream

import (
	"encoding/xml"
	"strings"
	"testing"
)

func TestParseComponentsXML_KeywordsOnly(t *testing.T) {
	const xml = `<?xml version="1.0"?>
<components>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox</name>
  <summary>Web browser</summary>
  <description><p>Free software web browser.</p></description>
  <categories><category>WebBrowser</category></categories>
  <keywords><keyword>internet</keyword><keyword>www</keyword></keywords>
</component>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox ESR</name>
  <keywords><keyword>mozilla</keyword></keywords>
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
	if !strings.Contains(got, "internet") || !strings.Contains(got, "www") || !strings.Contains(got, "mozilla") {
		t.Errorf("expected AppStream <keyword> terms only, got %q", got)
	}
	if strings.Contains(strings.ToLower(got), "browser") || strings.Contains(got, "WebBrowser") {
		t.Errorf("did not expect description/name/category text in keywords, got %q", got)
	}
}

func TestParseComponentsXML_KeywordLangFilter(t *testing.T) {
	// AppStream often sets xml:lang on <keywords>, not on each <keyword>.
	const xml = `<?xml version="1.0"?>
<components>
<component>
  <pkgname>demo</pkgname>
  <keywords>
    <keyword>neutral</keyword>
  </keywords>
  <keywords xml:lang="en">
    <keyword>english</keyword>
  </keywords>
  <keywords xml:lang="de">
    <keyword>deutsch</keyword>
  </keywords>
  <keywords xml:lang="de-DE">
    <keyword>deutsch2</keyword>
  </keywords>
  <keywords xml:lang="fr">
    <keyword>francais</keyword>
  </keywords>
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
	got := dedupeWords(acc["demo"])
	for _, need := range []string{"neutral", "english", "deutsch", "deutsch2"} {
		if !strings.Contains(got, need) {
			t.Errorf("missing %q in %q", need, got)
		}
	}
	if strings.Contains(got, "francais") {
		t.Errorf("did not want fr keyword, got %q", got)
	}
}

func TestKeywordLangAccepted(t *testing.T) {
	tests := []struct {
		attrs []xml.Attr
		want  bool
	}{
		{nil, true},
		{[]xml.Attr{{Name: xml.Name{Local: "lang"}, Value: "en"}}, true},
		{[]xml.Attr{{Name: xml.Name{Local: "lang"}, Value: "de"}}, true},
		{[]xml.Attr{{Name: xml.Name{Local: "lang"}, Value: "de-AT"}}, true},
		{[]xml.Attr{{Name: xml.Name{Local: "lang"}, Value: "fr"}}, false},
		{[]xml.Attr{{Name: xml.Name{Local: "lang"}, Value: "pl"}}, false},
	}
	for _, tt := range tests {
		if got := keywordLangAccepted(tt.attrs); got != tt.want {
			t.Errorf("keywordLangAccepted(%v) = %v, want %v", tt.attrs, got, tt.want)
		}
	}
}
