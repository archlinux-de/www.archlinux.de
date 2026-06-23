package appstream

import (
	"encoding/xml"
	"strings"
	"testing"
)

func TestParseComponentsXML_KeywordsAndCategories(t *testing.T) {
	const xml = `<?xml version="1.0"?>
<components>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox</name>
  <summary>Web browser</summary>
  <description><p>Free software web browser.</p></description>
  <categories><category>Network</category><category>WebBrowser</category></categories>
  <keywords><keyword>internet</keyword><keyword>www</keyword></keywords>
</component>
<component type="desktop-application">
  <pkgname>firefox</pkgname>
  <name>Firefox ESR</name>
  <keywords><keyword>mozilla</keyword></keywords>
</component>
</components>`

	accKW := make(map[string][]string)
	accCat := make(map[string][]string)
	err := ParseComponentsXML(strings.NewReader(xml), func(name string, terms IndexTerms) error {
		accKW[name] = append(accKW[name], terms.Keywords...)
		accCat[name] = append(accCat[name], terms.Categories...)
		return nil
	})
	if err != nil {
		t.Fatal(err)
	}
	gotKW := dedupeWords(accKW["firefox"])
	gotCat := dedupeWords(accCat["firefox"])
	if gotKW == "" {
		t.Fatal("expected merged keywords for firefox")
	}
	if !strings.Contains(gotKW, "internet") || !strings.Contains(gotKW, "www") || !strings.Contains(gotKW, "mozilla") {
		t.Errorf("expected keyword terms, got %q", gotKW)
	}
	if strings.Contains(strings.ToLower(gotKW), "browser") {
		t.Errorf("did not expect description text in keywords, got %q", gotKW)
	}
	if !strings.Contains(gotCat, "Network") || !strings.Contains(gotCat, "WebBrowser") {
		t.Errorf("expected category terms, got %q", gotCat)
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
	err := ParseComponentsXML(strings.NewReader(xml), func(name string, terms IndexTerms) error {
		acc[name] = append(acc[name], terms.Keywords...)
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

func TestParseComponentsXML_CategoriesLangFilter(t *testing.T) {
	const xml = `<?xml version="1.0"?>
<components>
<component>
  <pkgname>demo</pkgname>
  <categories><category>NeutralCat</category></categories>
  <categories xml:lang="de"><category>DeutschCat</category></categories>
  <categories xml:lang="fr"><category>FrCat</category></categories>
</component>
</components>`
	acc := make(map[string][]string)
	err := ParseComponentsXML(strings.NewReader(xml), func(name string, terms IndexTerms) error {
		acc[name] = append(acc[name], terms.Categories...)
		return nil
	})
	if err != nil {
		t.Fatal(err)
	}
	got := dedupeWords(acc["demo"])
	if !strings.Contains(got, "NeutralCat") || !strings.Contains(got, "DeutschCat") {
		t.Errorf("want neutral+de categories, got %q", got)
	}
	if strings.Contains(got, "FrCat") {
		t.Errorf("did not want fr category, got %q", got)
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
