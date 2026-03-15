package legacy

import (
	"net/http"
	"net/url"
	"strings"
)

func RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /", func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/" {
			http.NotFound(w, r)
			return
		}

		rawQuery := r.URL.RawQuery
		if rawQuery == "" {
			// No query string — let the home handler deal with it
			// This handler is registered after home, so it won't be reached
			// unless there's a query string
			http.NotFound(w, r)
			return
		}

		// Legacy uses ; as separator
		rawQuery = strings.ReplaceAll(rawQuery, ";", "&")
		values, err := url.ParseQuery(rawQuery)
		if err != nil {
			http.NotFound(w, r)
			return
		}

		page := values.Get("page")
		if page == "" {
			http.NotFound(w, r)
			return
		}

		target := resolveTarget(page, values)
		if target == "" {
			http.NotFound(w, r)
			return
		}

		http.Redirect(w, r, target, http.StatusMovedPermanently)
	})
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
		if file != "" {
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

	case "PackagesSuggest":
		return "/packages"
	}

	return ""
}
