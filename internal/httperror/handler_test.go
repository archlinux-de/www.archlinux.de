package httperror

import (
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"archded/internal/ui/layout"
)

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func TestMiddleware_Intercepts404(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		http.Error(w, "not found", http.StatusNotFound)
	}))

	req := httptest.NewRequest(http.MethodGet, "/missing", nil)
	req.Header.Set("Accept", "text/html")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected Cache-Control no-store, got %q", cc)
	}
	if ct := rr.Header().Get("Content-Type"); !strings.Contains(ct, "text/html") {
		t.Errorf("expected text/html content-type, got %q", ct)
	}
}

func TestMiddleware_Intercepts500(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		http.Error(w, "error", http.StatusInternalServerError)
	}))

	req := httptest.NewRequest(http.MethodGet, "/error", nil)
	req.Header.Set("Accept", "text/html")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusInternalServerError {
		t.Errorf("expected 500, got %d", rr.Code)
	}
	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected Cache-Control no-store, got %q", cc)
	}
}

func TestMiddleware_SkipIntercept(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		SkipIntercept(w)
		w.WriteHeader(http.StatusNotFound)
		_, _ = w.Write([]byte("custom not found"))
	}))

	req := httptest.NewRequest(http.MethodGet, "/custom", nil)
	req.Header.Set("Accept", "text/html")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
	if body := rr.Body.String(); body != "custom not found" {
		t.Errorf("expected custom body, got %q", body)
	}
}

func TestMiddleware_PassesThrough200(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		_, _ = w.Write([]byte("ok"))
	}))

	req := httptest.NewRequest(http.MethodGet, "/ok", nil)
	req.Header.Set("Accept", "text/html")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
	if body := rr.Body.String(); body != "ok" {
		t.Errorf("expected 'ok', got %q", body)
	}
}

func TestMiddleware_SkipsNonHTML(t *testing.T) {
	originalBody := `{"error":"not found"}`
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		http.Error(w, originalBody, http.StatusNotFound)
	}))

	req := httptest.NewRequest(http.MethodGet, "/api/missing", nil)
	req.Header.Set("Accept", "application/json")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
	// Original body should pass through unmodified
	if !strings.Contains(rr.Body.String(), originalBody) {
		t.Error("expected original body to pass through for non-HTML request")
	}
}

func TestMiddleware_InterceptsWildcardAccept(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		http.Error(w, "not found", http.StatusNotFound)
	}))

	req := httptest.NewRequest(http.MethodGet, "/missing", nil)
	req.Header.Set("Accept", "*/*")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected interception for */* accept, got Cache-Control %q", cc)
	}
}

func TestMiddleware_InterceptsEmptyAccept(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		http.Error(w, "not found", http.StatusNotFound)
	}))

	req := httptest.NewRequest(http.MethodGet, "/missing", nil)
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if cc := rr.Header().Get("Cache-Control"); cc != "no-store" {
		t.Errorf("expected interception for empty accept, got Cache-Control %q", cc)
	}
}

func TestMiddleware_SuppressesOriginalBody(t *testing.T) {
	handler := Middleware(testManifest())(http.HandlerFunc(func(w http.ResponseWriter, _ *http.Request) {
		w.WriteHeader(http.StatusNotFound)
		_, _ = w.Write([]byte("original body that should be discarded"))
	}))

	req := httptest.NewRequest(http.MethodGet, "/missing", nil)
	req.Header.Set("Accept", "text/html")
	rr := httptest.NewRecorder()
	handler.ServeHTTP(rr, req)

	if strings.Contains(rr.Body.String(), "original body") {
		t.Error("original body should be suppressed")
	}
}
