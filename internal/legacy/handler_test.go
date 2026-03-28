package legacy

import (
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestHandleLegacyQuery_NoQuery(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/", nil)
	if HandleLegacyQuery(rr, req) {
		t.Error("expected false for request without query")
	}
}

func TestHandleLegacyQuery_NoPage(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?foo=bar", nil)
	if HandleLegacyQuery(rr, req) {
		t.Error("expected false for request without page param")
	}
}

func TestHandleLegacyQuery_PackagesSuggest(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=PackagesSuggest", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true for PackagesSuggest")
	}

	if ct := rr.Header().Get("Content-Type"); ct != "application/json" {
		t.Errorf("expected Content-Type application/json, got %q", ct)
	}
	if body := rr.Body.String(); body != "[]" {
		t.Errorf("expected empty JSON array, got %q", body)
	}
}

func TestHandleLegacyQuery_SemicolonSeparator(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=PackageDetails;repo=core;arch=x86_64;pkgname=bash", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true for semicolon-separated query")
	}

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301, got %d", rr.Code)
	}
	if loc := rr.Header().Get("Location"); loc != "/packages/core/x86_64/bash" {
		t.Errorf("expected /packages/core/x86_64/bash, got %q", loc)
	}
}

func TestHandleLegacyQuery_InternalRedirects(t *testing.T) {
	tests := []struct {
		page, target string
	}{
		{"GetRecentNews", "/news/feed"},
		{"GetRecentPackages", "/packages/feed"},
		{"GetOpenSearch", "/packages/opensearch"},
		{"MirrorStatus", "/mirrors"},
		{"Packages", "/packages"},
		{"Start", "/"},
	}
	for _, tt := range tests {
		t.Run(tt.page, func(t *testing.T) {
			rr := httptest.NewRecorder()
			req := httptest.NewRequest(http.MethodGet, "/?page="+tt.page, nil)
			if !HandleLegacyQuery(rr, req) {
				t.Fatal("expected true")
			}
			if rr.Code != http.StatusMovedPermanently {
				t.Errorf("expected 301, got %d", rr.Code)
			}
			if loc := rr.Header().Get("Location"); loc != tt.target {
				t.Errorf("expected %q, got %q", tt.target, loc)
			}
		})
	}
}

func TestHandleLegacyQuery_ExternalRedirects(t *testing.T) {
	tests := []struct {
		page, prefix string
	}{
		{"ArchitectureDifferences", "https://www.archlinux.org/"},
		{"FunStatistics", "https://pkgstats.archlinux.de/"},
		{"Statistics", "https://pkgstats.archlinux.de/"},
		{"UserStatistics", "https://pkgstats.archlinux.de/"},
	}
	for _, tt := range tests {
		t.Run(tt.page, func(t *testing.T) {
			rr := httptest.NewRecorder()
			req := httptest.NewRequest(http.MethodGet, "/?page="+tt.page, nil)
			if !HandleLegacyQuery(rr, req) {
				t.Fatal("expected true")
			}
			if rr.Code != http.StatusMovedPermanently {
				t.Errorf("expected 301, got %d", rr.Code)
			}
			loc := rr.Header().Get("Location")
			if len(loc) < len(tt.prefix) || loc[:len(tt.prefix)] != tt.prefix {
				t.Errorf("expected location starting with %q, got %q", tt.prefix, loc)
			}
		})
	}
}

func TestHandleLegacyQuery_PackageDetails(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=PackageDetails&repo=extra&arch=x86_64&pkgname=firefox", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true")
	}
	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301, got %d", rr.Code)
	}
	if loc := rr.Header().Get("Location"); loc != "/packages/extra/x86_64/firefox" {
		t.Errorf("expected /packages/extra/x86_64/firefox, got %q", loc)
	}
}

func TestHandleLegacyQuery_PackageDetailsMissingParams(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=PackageDetails&repo=extra", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true")
	}
	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 redirect to /packages, got %d", rr.Code)
	}
	if loc := rr.Header().Get("Location"); loc != "/packages" {
		t.Errorf("expected /packages, got %q", loc)
	}
}

func TestHandleLegacyQuery_GetFileFromMirror(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=GetFileFromMirror&file=iso/2024.01/archlinux.iso", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true")
	}
	if loc := rr.Header().Get("Location"); loc != "/download/iso/2024.01/archlinux.iso" {
		t.Errorf("expected /download/iso/2024.01/archlinux.iso, got %q", loc)
	}
}

func TestHandleLegacyQuery_GetFileFromMirrorTraversal(t *testing.T) {
	for _, file := range []string{
		"../../etc/passwd",
		"//evil.com/file",
		"/etc/passwd",
		"foo/../../../etc/passwd",
	} {
		rr := httptest.NewRecorder()
		req := httptest.NewRequest(http.MethodGet, "/?page=GetFileFromMirror&file="+file, nil)
		if !HandleLegacyQuery(rr, req) {
			t.Fatalf("file=%q: expected true", file)
		}
		if loc := rr.Header().Get("Location"); loc != "/download" {
			t.Errorf("file=%q: expected /download, got %q", file, loc)
		}
	}
}

func TestHandleLegacyQuery_GetFileFromMirrorNoFile(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=GetFileFromMirror", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true")
	}
	if loc := rr.Header().Get("Location"); loc != "/download" {
		t.Errorf("expected /download, got %q", loc)
	}
}

func TestHandleLegacyQuery_UnknownPage(t *testing.T) {
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/?page=UnknownPage", nil)
	if !HandleLegacyQuery(rr, req) {
		t.Fatal("expected true")
	}
	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}

func TestLegacyMiddleware(t *testing.T) {
	next := http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	})
	handler := LegacyMiddleware(next)

	tests := []struct {
		name       string
		path       string
		wantStatus int
		wantTarget string
	}{
		{"archicon without hash", "/img/archicon.svg", http.StatusMovedPermanently, "/static/archicon.svg"},
		{"archicon with hash", "/img/archicon.a1b2c3.svg", http.StatusMovedPermanently, "/static/archicon.svg"},
		{"archlogo without hash", "/img/archlogo.svg", http.StatusMovedPermanently, "/static/archlogo.svg"},
		{"archlogo with hash", "/img/archlogo.deadbeef.svg", http.StatusMovedPermanently, "/static/archlogo.svg"},
		{"statistics root", "/statistics", http.StatusMovedPermanently, "https://pkgstats.archlinux.de/"},
		{"statistics subpath", "/statistics/fun", http.StatusMovedPermanently, "https://pkgstats.archlinux.de/fun"},
		{"statistics deep path", "/statistics/packages/linux", http.StatusMovedPermanently, "https://pkgstats.archlinux.de/packages/linux"},
		{"old webpack js bundle", "/js/app.a1b2c3.js", http.StatusGone, ""},
		{"old webpack css bundle", "/css/app.a1b2c3.css", http.StatusGone, ""},
		{"old workbox file", "/workbox-08bdcb2c.js", http.StatusGone, ""},
		{"unknown img path", "/img/something.png", http.StatusGone, ""},
		{"api packages", "/api/packages", http.StatusGone, ""},
		{"api news", "/api/news/1", http.StatusGone, ""},
		{"api package detail", "/api/packages/extra/x86_64/linux", http.StatusGone, ""},
		{"community package", "/packages/community/x86_64/remmina", http.StatusMovedPermanently, "/packages/extra/x86_64/remmina"},
		{"community-testing package", "/packages/community-testing/x86_64/remmina", http.StatusMovedPermanently, "/packages/extra-testing/x86_64/remmina"},
		{"download community-testing", "/download/community-testing/os/x86_64/pkg.tar.zst", http.StatusMovedPermanently, "/download/extra-testing/os/x86_64/pkg.tar.zst"},
		{"download community", "/download/community/os/x86_64/pkg.tar.zst", http.StatusMovedPermanently, "/download/extra/os/x86_64/pkg.tar.zst"},
		{"style favicon", "/style/favicon.ico", http.StatusMovedPermanently, "/favicon.ico"},
		{"unrelated path passes through", "/packages", http.StatusOK, ""},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest(http.MethodGet, tt.path, nil)
			rr := httptest.NewRecorder()
			handler.ServeHTTP(rr, req)

			if rr.Code != tt.wantStatus {
				t.Errorf("status = %d, want %d", rr.Code, tt.wantStatus)
			}

			if tt.wantTarget != "" {
				loc := rr.Header().Get("Location")
				if loc != tt.wantTarget {
					t.Errorf("Location = %q, want %q", loc, tt.wantTarget)
				}
			}

			if tt.wantStatus != http.StatusOK {
				cc := rr.Header().Get("Cache-Control")
				if cc == "" {
					t.Error("expected Cache-Control header to be set")
				}
			}
		})
	}
}
