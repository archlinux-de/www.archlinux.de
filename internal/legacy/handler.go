package legacy

import (
	"net/http"
	"net/url"
	"regexp"
	"strings"
)

func emptyJSONArray(w http.ResponseWriter, _ *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	_, _ = w.Write([]byte("[]"))
}

func RegisterRoutes(mux *http.ServeMux) {
}

type legacyRoute struct {
	pattern *regexp.Regexp
	target  string
}

var legacyRoutes = []legacyRoute{
	{regexp.MustCompile(`^/img/(archicon|archlogo)(?:\.[a-f0-9]+)?\.svg$`), "/static/{1}.svg"},
	{regexp.MustCompile(`^/statistics$`), "https://pkgstats.archlinux.de/"},
	{regexp.MustCompile(`^/statistics/(.+)$`), "https://pkgstats.archlinux.de/{1}"},
	{regexp.MustCompile(`^/js/`), ""},
	{regexp.MustCompile(`^/css/`), ""},
	{regexp.MustCompile(`^/workbox-[a-f0-9]+\.js$`), ""},
	{regexp.MustCompile(`^/img/`), ""},
	{regexp.MustCompile(`^/api/`), ""},
}

func LegacyMiddleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		for _, route := range legacyRoutes {
			matches := route.pattern.FindStringSubmatch(r.URL.Path)
			if matches == nil {
				continue
			}

			w.Header().Set("Cache-Control", "public, max-age=86400")

			if route.target == "" {
				w.WriteHeader(http.StatusGone)
				return
			}

			target := route.target
			for i := 1; i < len(matches); i++ {
				target = strings.ReplaceAll(target, "{"+string(rune('0'+i))+"}", matches[i])
			}

			http.Redirect(w, r, target, http.StatusMovedPermanently)
			return
		}

		next.ServeHTTP(w, r)
	})
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
