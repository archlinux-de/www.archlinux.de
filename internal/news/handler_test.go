package news

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"www/internal/ui/layout"

	_ "modernc.org/sqlite"
)

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func TestHandlerIndex(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerIndex_WithSearch(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news?search=Special", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerShow(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news/1-first-news", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerShow_IDOnly(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news/2", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerShow_InvalidID(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news/abc", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404 for non-numeric ID, got %d", rr.Code)
	}
}

func TestHandlerShow_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news/999", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}

func TestHandlerIndex_NegativeOffset(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/news?offset=-5", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}
