package mirrors

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/mirrors", nil))

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/mirrors?search=Germany", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerIndex_NegativeOffset(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/mirrors?offset=-10", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}
