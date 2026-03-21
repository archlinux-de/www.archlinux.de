package releases

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"archded/internal/ui/layout"

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/releases", nil))

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/releases?search=6.6.7", nil))

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/releases/2024.01.01", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerShow_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/releases/9999.99.99", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}
