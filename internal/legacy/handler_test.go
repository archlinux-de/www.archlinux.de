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

func TestRegisterRoutes_PackagesSuggest(t *testing.T) {
	mux := http.NewServeMux()
	RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/suggest", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
	if ct := rr.Header().Get("Content-Type"); ct != "application/json" {
		t.Errorf("expected application/json, got %q", ct)
	}
	if body := rr.Body.String(); body != "[]" {
		t.Errorf("expected [], got %q", body)
	}
}
