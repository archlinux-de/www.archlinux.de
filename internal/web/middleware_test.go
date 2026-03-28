package web

import (
	"net/http"
	"net/http/httptest"
	"testing"
	"time"
)

func TestRedirectTrailingSlash(t *testing.T) {
	inner := http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	})
	handler := RedirectTrailingSlash()(inner)

	tests := []struct {
		name       string
		path       string
		wantStatus int
		wantTarget string
	}{
		{"root unchanged", "/", http.StatusOK, ""},
		{"no slash unchanged", "/news/feed", http.StatusOK, ""},
		{"trailing slash redirects", "/news/feed/", http.StatusMovedPermanently, "/news/feed"},
		{"trailing slash with query", "/download/?foo=bar", http.StatusMovedPermanently, "/download?foo=bar"},
		{"multiple trailing slashes", "/planet//", http.StatusMovedPermanently, "/planet"},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			rr := httptest.NewRecorder()
			rr.Body = nil
			handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, tt.path, nil))

			if rr.Code != tt.wantStatus {
				t.Errorf("status = %d, want %d", rr.Code, tt.wantStatus)
			}
			if tt.wantTarget != "" {
				if loc := rr.Header().Get("Location"); loc != tt.wantTarget {
					t.Errorf("Location = %q, want %q", loc, tt.wantTarget)
				}
			}
		})
	}
}

func TestChain(t *testing.T) {
	var order []string
	a := func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			order = append(order, "a")
			next.ServeHTTP(w, r)
		})
	}
	b := func(next http.Handler) http.Handler {
		return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
			order = append(order, "b")
			next.ServeHTTP(w, r)
		})
	}
	inner := http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		order = append(order, "inner")
		w.WriteHeader(http.StatusOK)
	})

	handler := Chain(inner, a, b)
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if len(order) != 3 || order[0] != "a" || order[1] != "b" || order[2] != "inner" {
		t.Errorf("expected [a b inner], got %v", order)
	}
}

func TestRecovery(t *testing.T) {
	handler := Recovery()(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		panic("test panic")
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if rr.Code != http.StatusInternalServerError {
		t.Errorf("expected 500, got %d", rr.Code)
	}
}

func TestRecovery_NoPanic(t *testing.T) {
	handler := Recovery()(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestSecureHeaders(t *testing.T) {
	handler := SecureHeaders()(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if csp := rr.Header().Get("Content-Security-Policy"); csp == "" {
		t.Error("expected Content-Security-Policy header")
	}
	if rr.Header().Get("X-Content-Type-Options") != "nosniff" {
		t.Error("expected X-Content-Type-Options: nosniff")
	}
	if rr.Header().Get("Referrer-Policy") != "strict-origin-when-cross-origin" {
		t.Error("expected Referrer-Policy: strict-origin-when-cross-origin")
	}
}

func TestCacheControl_GET(t *testing.T) {
	handler := CacheControl(5 * time.Minute)(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if cc := rr.Header().Get("Cache-Control"); cc != "public, max-age=300" {
		t.Errorf("expected Cache-Control 'public, max-age=300', got %q", cc)
	}
}

func TestCacheControl_POST(t *testing.T) {
	handler := CacheControl(5 * time.Minute)(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodPost, "/", nil))

	if cc := rr.Header().Get("Cache-Control"); cc != "" {
		t.Errorf("expected no Cache-Control for POST, got %q", cc)
	}
}

func TestNoCache_WriteHeader(t *testing.T) {
	handler := NoCache()(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected Cache-Control 'no-store', got %q", cc)
	}
}

func TestNoCache_Write(t *testing.T) {
	handler := NoCache()(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		_, _ = w.Write([]byte("body"))
	}))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected Cache-Control 'no-store', got %q", cc)
	}
}

func TestNoCache_OverridesCacheControl(t *testing.T) {
	inner := http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusOK)
	})
	handler := CacheControl(5 * time.Minute)(NoCache()(inner))

	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected no-store to win, got %q", cc)
	}
}
