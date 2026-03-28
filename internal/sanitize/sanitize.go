package sanitize

import (
	"net/url"
	"strings"

	"github.com/microcosm-cc/bluemonday"
)

var htmlPolicy *bluemonday.Policy

func init() {
	htmlPolicy = bluemonday.NewPolicy()
	htmlPolicy.AllowElements("p", "code", "pre", "ul", "ol", "li", "br",
		"strong", "b", "em", "i", "del", "ins", "blockquote",
		"h3", "h4", "h5", "h6")
	htmlPolicy.AllowStandardURLs()
	htmlPolicy.AllowAttrs("href").OnElements("a")
	htmlPolicy.RequireNoFollowOnLinks(true)
	htmlPolicy.RequireNoReferrerOnLinks(true)
	htmlPolicy.AddTargetBlankToFullyQualifiedLinks(false)
}

func HTML(s string) string {
	return htmlPolicy.Sanitize(s)
}

var allowedSchemes = map[string]bool{
	"http":   true,
	"https":  true,
	"magnet": true,
}

func URL(raw string) string {
	u, err := url.Parse(strings.TrimSpace(raw))
	if err != nil || !allowedSchemes[u.Scheme] {
		return "#"
	}
	return u.String()
}

func IsValidURL(raw string, schemes ...string) bool {
	u, err := url.Parse(strings.TrimSpace(raw))
	if err != nil || u.Host == "" {
		return false
	}
	allowed := allowedSchemes
	if len(schemes) > 0 {
		allowed = make(map[string]bool, len(schemes))
		for _, s := range schemes {
			allowed[s] = true
		}
	}
	return allowed[u.Scheme]
}
