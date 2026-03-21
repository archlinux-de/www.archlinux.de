package legacy

import (
	"net/http"
	"net/url"
	"strings"
)

func emptyJSONArray(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	_, _ = w.Write([]byte("[]"))
}

func RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/suggest", emptyJSONArray)
}

// HandleLegacyQuery handles legacy ?page= query strings on the root path.
// Returns true if the request was handled.
func HandleLegacyQuery(w http.ResponseWriter, r *http.Request) bool {
	rawQuery := r.URL.RawQuery
	if rawQuery == "" {
		return false
	}

	// Legacy uses ; as separator
	rawQuery = strings.ReplaceAll(rawQuery, ";", "&")
	values, err := url.ParseQuery(rawQuery)
	if err != nil {
		return false
	}

	page := values.Get("page")
	if page == "" {
		return false
	}

	if page == "PackagesSuggest" {
		emptyJSONArray(w, r)
		return true
	}

	target := resolveTarget(page, values)
	if target == "" {
		http.NotFound(w, r)
		return true
	}

	http.Redirect(w, r, target, http.StatusMovedPermanently)
	return true
}

var externalRedirects = map[string]string{
	"ArchitectureDifferences": "https://www.archlinux.org/packages/differences/",
	"MirrorProblems":          "https://www.archlinux.org/mirrors/status/#outofsync",
	"MirrorStatusJSON":        "https://www.archlinux.org/mirrors/status/json/",
	"FunStatistics":           "https://pkgstats.archlinux.de/fun",
	"ModuleStatistics":        "https://pkgstats.archlinux.de/module",
	"PackageStatistics":       "https://pkgstats.archlinux.de/package",
	"Statistics":              "https://pkgstats.archlinux.de/",
}

var internalRedirects = map[string]string{
	"GetRecentNews":     "/news/feed",
	"GetRecentPackages": "/packages/feed",
	"GetOpenSearch":     "/packages/opensearch",
	"MirrorStatus":      "/mirrors",
	"Packages":          "/packages",
	"Start":             "/",
}

func resolveTarget(page string, values url.Values) string {
	if target, ok := externalRedirects[page]; ok {
		return target
	}

	if target, ok := internalRedirects[page]; ok {
		return target
	}

	switch page {
	case "GetFileFromMirror":
		file := values.Get("file")
		if file != "" && !strings.Contains(file, "..") {
			return "/download/" + file
		}
		return "/download"

	case "PackageDetails":
		repo := values.Get("repo")
		arch := values.Get("arch")
		pkgname := values.Get("pkgname")
		if repo != "" && arch != "" && pkgname != "" {
			return "/packages/" + repo + "/" + arch + "/" + pkgname
		}
		return "/packages"

	}

	return ""
}
