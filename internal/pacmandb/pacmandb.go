// Package pacmandb parses pacman .files database archives (gzip-compressed tar).
package pacmandb

import (
	"archive/tar"
	"compress/gzip"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"path"
	"regexp"
	"strconv"
	"strings"
)

type Package struct {
	Name           string
	Base           string
	Version        string
	Description    string
	URL            string
	BuildDate      int64
	CompressedSize int64
	InstalledSize  int64
	PackagerName   string
	PackagerEmail  string
	Licenses       []string
	Groups         []string
	Relations      []Relation
	Files          []string
}

type Relation struct {
	Type              string
	TargetName        string
	TargetVersion     string
	VersionConstraint string
}

// Parse reads a gzip-compressed tar archive (.files) and calls fn for each package.
// Packages are emitted as they are parsed, keeping memory usage constant.
func Parse(r io.Reader, fn func(Package) error) error {
	gz, err := gzip.NewReader(r)
	if err != nil {
		return fmt.Errorf("gzip: %w", err)
	}
	defer func() { _ = gz.Close() }()

	tr := tar.NewReader(gz)

	var curDir, desc, files string

	emit := func() error {
		if desc == "" {
			return nil
		}
		fields := parseDesc(desc + "\n" + files)
		return fn(buildPackage(fields))
	}

	for {
		hdr, err := tr.Next()
		if errors.Is(err, io.EOF) {
			break
		}
		if err != nil {
			return fmt.Errorf("tar: %w", err)
		}

		dir := path.Dir(hdr.Name)
		base := path.Base(hdr.Name)

		if base != "desc" && base != "files" {
			continue
		}

		if dir != curDir && curDir != "" {
			if err := emit(); err != nil {
				return err
			}
			desc = ""
			files = ""
		}
		curDir = dir

		data, err := io.ReadAll(tr)
		if err != nil {
			return fmt.Errorf("read %s: %w", hdr.Name, err)
		}

		if base == "desc" {
			desc = string(data)
		} else {
			files = string(data)
		}
	}

	return emit()
}

// parseDesc parses the %FIELD%\nvalue\n\n format into a map.
func parseDesc(data string) map[string][]string {
	result := make(map[string][]string)
	var key string

	for _, line := range strings.Split(data, "\n") {
		trimmed := strings.TrimSpace(line)
		if trimmed == "" {
			key = ""
			continue
		}
		if len(trimmed) > 2 && trimmed[0] == '%' && trimmed[len(trimmed)-1] == '%' {
			key = trimmed[1 : len(trimmed)-1]
			result[key] = nil
			continue
		}
		if key != "" {
			result[key] = append(result[key], trimmed)
		}
	}

	return result
}

func buildPackage(fields map[string][]string) Package {
	pkg := Package{
		Name:        first(fields["NAME"]),
		Base:        first(fields["BASE"]),
		Version:     first(fields["VERSION"]),
		Description: first(fields["DESC"]),
		URL:         first(fields["URL"]),
	}

	if pkg.Base == "" {
		pkg.Base = pkg.Name
	}

	if v := first(fields["BUILDDATE"]); v != "" {
		pkg.BuildDate, _ = strconv.ParseInt(v, 10, 64)
	}
	if v := first(fields["CSIZE"]); v != "" {
		pkg.CompressedSize, _ = strconv.ParseInt(v, 10, 64)
	}
	if v := first(fields["ISIZE"]); v != "" {
		pkg.InstalledSize, _ = strconv.ParseInt(v, 10, 64)
	}

	parsePackager(first(fields["PACKAGER"]), &pkg)

	pkg.Licenses = parseLicenses(fields["LICENSE"])
	pkg.Groups = fields["GROUPS"]

	addRelations(&pkg, "depends", fields["DEPENDS"])
	addRelations(&pkg, "optdepends", fields["OPTDEPENDS"])
	addRelations(&pkg, "makedepends", fields["MAKEDEPENDS"])
	addRelations(&pkg, "checkdepends", fields["CHECKDEPENDS"])
	addRelations(&pkg, "conflicts", fields["CONFLICTS"])
	addRelations(&pkg, "replaces", fields["REPLACES"])
	addRelations(&pkg, "provides", fields["PROVIDES"])

	pkg.Files = fields["FILES"]

	return pkg
}

var packagerRe = regexp.MustCompile(`^([^<>]+?)(?:\s*<(.+?)>)?$`)

func parsePackager(s string, pkg *Package) {
	if s == "" {
		return
	}
	m := packagerRe.FindStringSubmatch(s)
	if m == nil {
		pkg.PackagerName = s
		return
	}
	pkg.PackagerName = strings.TrimSpace(m[1])
	pkg.PackagerEmail = strings.TrimSpace(m[2])
}

var relationRe = regexp.MustCompile(`^([\w\-+@.]+?)(?:(<=|>=|<|>|=)([\w.:\-]+))?(?::.+)?$`)

func addRelations(pkg *Package, relType string, values []string) {
	for _, v := range values {
		r := Relation{Type: relType}
		m := relationRe.FindStringSubmatch(v)
		if m != nil {
			r.TargetName = m[1]
			r.VersionConstraint = constraintToCode(m[2])
			r.TargetVersion = m[3]
		} else {
			r.TargetName = v
		}
		pkg.Relations = append(pkg.Relations, r)
	}
}

func constraintToCode(op string) string {
	switch op {
	case "=":
		return "EQ"
	case "<=":
		return "LE"
	case "<":
		return "LT"
	case ">=":
		return "GE"
	case ">":
		return "GT"
	default:
		return ""
	}
}

func parseLicenses(values []string) []string {
	var result []string
	for _, v := range values {
		parts := regexp.MustCompile(`(?i)\s+and\s+`).Split(v, -1)
		for _, p := range parts {
			p = strings.Trim(p, " \t()")
			if p != "" {
				result = append(result, p)
			}
		}
	}
	return result
}

func first(s []string) string {
	if len(s) > 0 {
		return s[0]
	}
	return ""
}

// LicensesJSON returns the JSON representation for storing in the database.
func LicensesJSON(licenses []string) string {
	if len(licenses) == 0 {
		return "[]"
	}
	b, _ := json.Marshal(licenses)
	return string(b)
}

// GroupsJSON returns the JSON representation for storing in the database.
func GroupsJSON(groups []string) string {
	if len(groups) == 0 {
		return "[]"
	}
	b, _ := json.Marshal(groups)
	return string(b)
}
