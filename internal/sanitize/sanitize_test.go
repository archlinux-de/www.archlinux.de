package sanitize

import "testing"

func TestHTML(t *testing.T) {
	tests := []struct {
		name, input, want string
	}{
		{"plain text", "hello world", "hello world"},
		{"allowed tags", "<p>text</p>", "<p>text</p>"},
		{"allowed inline", "<strong>bold</strong> <em>italic</em>", "<strong>bold</strong> <em>italic</em>"},
		{"strips script", `<script>alert("xss")</script>`, ""},
		{"strips onclick", `<p onclick="alert(1)">text</p>`, "<p>text</p>"},
		{"strips img", `<img src="x" onerror="alert(1)">`, ""},
		{"strips iframe", `<iframe src="evil.com"></iframe>`, ""},
		{"allows links", `<a href="https://example.com">link</a>`, `<a href="https://example.com" rel="nofollow noreferrer">link</a>`},
		{"strips javascript href", `<a href="javascript:alert(1)">click</a>`, "click"},
		{"allows lists", "<ul><li>item</li></ul>", "<ul><li>item</li></ul>"},
		{"allows code blocks", "<pre><code>x := 1</code></pre>", "<pre><code>x := 1</code></pre>"},
		{"strips style", `<p style="color:red">text</p>`, "<p>text</p>"},
		{"strips div", "<div>text</div>", "text"},
		{"allows headings h3-h6", "<h3>title</h3>", "<h3>title</h3>"},
		{"strips h1 h2", "<h1>big</h1><h2>medium</h2>", "bigmedium"},
		{"empty input", "", ""},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := HTML(tt.input)
			if got != tt.want {
				t.Errorf("HTML(%q) = %q, want %q", tt.input, got, tt.want)
			}
		})
	}
}

func TestURL(t *testing.T) {
	tests := []struct {
		name, input, want string
	}{
		{"https", "https://example.com/path", "https://example.com/path"},
		{"http", "http://example.com", "http://example.com"},
		{"magnet", "magnet:?xt=urn:btih:abc", "magnet:?xt=urn:btih:abc"},
		{"javascript", "javascript:alert(1)", "#"},
		{"data uri", "data:text/html,<script>alert(1)</script>", "#"},
		{"ftp", "ftp://example.com/file", "#"},
		{"empty", "", "#"},
		{"whitespace", "  https://example.com  ", "  https://example.com  "},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := URL(tt.input)
			if got != tt.want {
				t.Errorf("URL(%q) = %q, want %q", tt.input, got, tt.want)
			}
		})
	}
}

func TestIsValidURL(t *testing.T) {
	tests := []struct {
		name    string
		input   string
		schemes []string
		want    bool
	}{
		{"valid https", "https://example.com", nil, true},
		{"valid http", "http://example.com", nil, true},
		{"valid magnet", "magnet:?xt=urn:btih:abc", nil, false}, // magnet has no host
		{"no scheme", "example.com", nil, false},
		{"javascript", "javascript:alert(1)", nil, false},
		{"empty", "", nil, false},
		{"custom schemes", "ftp://example.com", []string{"ftp"}, true},
		{"custom schemes reject", "https://example.com", []string{"ftp"}, false},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			got := IsValidURL(tt.input, tt.schemes...)
			if got != tt.want {
				t.Errorf("IsValidURL(%q, %v) = %v, want %v", tt.input, tt.schemes, got, tt.want)
			}
		})
	}
}
