package pacmandb

import (
	"archive/tar"
	"bytes"
	"compress/gzip"
	"strings"
	"testing"
)

func makeTestArchive(t *testing.T, files map[string]string) *bytes.Buffer {
	t.Helper()
	var buf bytes.Buffer
	gz := gzip.NewWriter(&buf)
	tw := tar.NewWriter(gz)

	for name, content := range files {
		hdr := &tar.Header{
			Name: name,
			Size: int64(len(content)),
			Mode: 0o644,
		}
		if err := tw.WriteHeader(hdr); err != nil {
			t.Fatal(err)
		}
		if _, err := tw.Write([]byte(content)); err != nil {
			t.Fatal(err)
		}
	}

	if err := tw.Close(); err != nil {
		t.Fatal(err)
	}
	if err := gz.Close(); err != nil {
		t.Fatal(err)
	}

	return &buf
}

func TestParseBasicPackage(t *testing.T) {
	desc := `%NAME%
bash

%BASE%
bash

%VERSION%
5.2.037-1

%DESC%
The GNU Bourne Again shell

%URL%
https://www.gnu.org/software/bash/bash.html

%BUILDDATE%
1731580495

%CSIZE%
2153012

%ISIZE%
9761233

%PACKAGER%
Felix Yan <felixonmars@archlinux.org>

%LICENSE%
GPL-3.0-or-later

%GROUPS%
base

%DEPENDS%
readline
glibc
ncurses

%OPTDEPENDS%
bash-completion: for tab completion

%PROVIDES%
sh

%CONFLICTS%
sh

%REPLACES%
sh

`

	filesContent := `%FILES%
usr/
usr/bin/
usr/bin/bash
usr/bin/bashbug
usr/bin/sh

`

	archive := makeTestArchive(t, map[string]string{
		"bash-5.2.037-1/desc":  desc,
		"bash-5.2.037-1/files": filesContent,
	})

	packages, err := Parse(archive)
	if err != nil {
		t.Fatal(err)
	}

	if len(packages) != 1 {
		t.Fatalf("expected 1 package, got %d", len(packages))
	}

	pkg := packages[0]

	if pkg.Name != "bash" {
		t.Errorf("Name = %q, want %q", pkg.Name, "bash")
	}
	if pkg.Base != "bash" {
		t.Errorf("Base = %q, want %q", pkg.Base, "bash")
	}
	if pkg.Version != "5.2.037-1" {
		t.Errorf("Version = %q, want %q", pkg.Version, "5.2.037-1")
	}
	if pkg.Description != "The GNU Bourne Again shell" {
		t.Errorf("Description = %q", pkg.Description)
	}
	if pkg.URL != "https://www.gnu.org/software/bash/bash.html" {
		t.Errorf("URL = %q", pkg.URL)
	}
	if pkg.BuildDate != 1731580495 {
		t.Errorf("BuildDate = %d, want 1731580495", pkg.BuildDate)
	}
	if pkg.CompressedSize != 2153012 {
		t.Errorf("CompressedSize = %d", pkg.CompressedSize)
	}
	if pkg.InstalledSize != 9761233 {
		t.Errorf("InstalledSize = %d", pkg.InstalledSize)
	}
	if pkg.PackagerName != "Felix Yan" {
		t.Errorf("PackagerName = %q", pkg.PackagerName)
	}
	if pkg.PackagerEmail != "felixonmars@archlinux.org" {
		t.Errorf("PackagerEmail = %q", pkg.PackagerEmail)
	}
	if len(pkg.Licenses) != 1 || pkg.Licenses[0] != "GPL-3.0-or-later" {
		t.Errorf("Licenses = %v", pkg.Licenses)
	}
	if len(pkg.Groups) != 1 || pkg.Groups[0] != "base" {
		t.Errorf("Groups = %v", pkg.Groups)
	}

	// Check relations
	relsByType := make(map[string][]Relation)
	for _, r := range pkg.Relations {
		relsByType[r.Type] = append(relsByType[r.Type], r)
	}

	if len(relsByType["depends"]) != 3 {
		t.Errorf("depends count = %d, want 3", len(relsByType["depends"]))
	}
	if len(relsByType["optdepends"]) != 1 {
		t.Errorf("optdepends count = %d, want 1", len(relsByType["optdepends"]))
	}
	if len(relsByType["provides"]) != 1 {
		t.Errorf("provides count = %d, want 1", len(relsByType["provides"]))
	}
	if len(relsByType["conflicts"]) != 1 {
		t.Errorf("conflicts count = %d, want 1", len(relsByType["conflicts"]))
	}
	if len(relsByType["replaces"]) != 1 {
		t.Errorf("replaces count = %d, want 1", len(relsByType["replaces"]))
	}

	// Check files
	if len(pkg.Files) != 5 {
		t.Errorf("files count = %d, want 5", len(pkg.Files))
	}
}

func TestParseRelationWithVersion(t *testing.T) {
	desc := `%NAME%
testpkg

%VERSION%
1.0-1

%DESC%
test

%BUILDDATE%
1000000

%CSIZE%
100

%ISIZE%
200

%PACKAGER%
Test

%DEPENDS%
foo>=1.0
bar<2.0
baz=1.5
qux

`
	archive := makeTestArchive(t, map[string]string{
		"testpkg-1.0-1/desc": desc,
	})

	packages, err := Parse(archive)
	if err != nil {
		t.Fatal(err)
	}
	if len(packages) != 1 {
		t.Fatalf("expected 1 package, got %d", len(packages))
	}

	rels := packages[0].Relations
	if len(rels) != 4 {
		t.Fatalf("expected 4 relations, got %d", len(rels))
	}

	expected := []struct {
		name, ver, constraint string
	}{
		{"foo", "1.0", "GE"},
		{"bar", "2.0", "LT"},
		{"baz", "1.5", "EQ"},
		{"qux", "", ""},
	}

	for i, e := range expected {
		if rels[i].TargetName != e.name {
			t.Errorf("rel[%d].TargetName = %q, want %q", i, rels[i].TargetName, e.name)
		}
		if rels[i].TargetVersion != e.ver {
			t.Errorf("rel[%d].TargetVersion = %q, want %q", i, rels[i].TargetVersion, e.ver)
		}
		if rels[i].VersionConstraint != e.constraint {
			t.Errorf("rel[%d].VersionConstraint = %q, want %q", i, rels[i].VersionConstraint, e.constraint)
		}
	}
}

func TestParseDesc(t *testing.T) {
	input := `%NAME%
test

%VERSION%
1.0

%DEPENDS%
foo
bar

`
	fields := parseDesc(input)

	if v := fields["NAME"]; len(v) != 1 || v[0] != "test" {
		t.Errorf("NAME = %v", v)
	}
	if v := fields["DEPENDS"]; len(v) != 2 || v[0] != "foo" || v[1] != "bar" {
		t.Errorf("DEPENDS = %v", v)
	}
}

func TestParseLicenses(t *testing.T) {
	tests := []struct {
		input []string
		want  []string
	}{
		{[]string{"GPL-3.0-or-later"}, []string{"GPL-3.0-or-later"}},
		{[]string{"LGPL-2.1-or-later AND GPL-2.0-or-later"}, []string{"LGPL-2.1-or-later", "GPL-2.0-or-later"}},
		{nil, nil},
	}

	for _, tt := range tests {
		got := parseLicenses(tt.input)
		if len(got) != len(tt.want) {
			t.Errorf("parseLicenses(%v) = %v, want %v", tt.input, got, tt.want)
			continue
		}
		for i := range got {
			if got[i] != tt.want[i] {
				t.Errorf("parseLicenses(%v)[%d] = %q, want %q", tt.input, i, got[i], tt.want[i])
			}
		}
	}
}

func TestParsePackager(t *testing.T) {
	tests := []struct {
		input     string
		wantName  string
		wantEmail string
	}{
		{"Felix Yan <felixonmars@archlinux.org>", "Felix Yan", "felixonmars@archlinux.org"},
		{"John Doe", "John Doe", ""},
		{"", "", ""},
	}

	for _, tt := range tests {
		var pkg Package
		parsePackager(tt.input, &pkg)
		if pkg.PackagerName != tt.wantName {
			t.Errorf("parsePackager(%q) name = %q, want %q", tt.input, pkg.PackagerName, tt.wantName)
		}
		if pkg.PackagerEmail != tt.wantEmail {
			t.Errorf("parsePackager(%q) email = %q, want %q", tt.input, pkg.PackagerEmail, tt.wantEmail)
		}
	}
}

func TestLicensesJSON(t *testing.T) {
	got := LicensesJSON([]string{"GPL-3.0", "MIT"})
	if !strings.Contains(got, "GPL-3.0") || !strings.Contains(got, "MIT") {
		t.Errorf("LicensesJSON = %q", got)
	}

	if LicensesJSON(nil) != "[]" {
		t.Errorf("LicensesJSON(nil) = %q", LicensesJSON(nil))
	}
}
