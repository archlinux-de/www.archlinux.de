package packages

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages", nil))

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages?search=firefox", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerIndex_WithRepoFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages?repository=core", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerIndex_WithArchFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages?architecture=x86_64", nil))

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
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages?offset=-5", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestHandlerIndex_EmptySearch(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages?search=nonexistentxyz", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestPackagesData_Pagination(t *testing.T) {
	d := packagesData{Pagination: layout.Pagination{Total: 100, Limit: 25, Offset: 0}}
	if !d.HasNext() {
		t.Error("expected HasNext")
	}
	if d.HasPrevious() {
		t.Error("expected no HasPrevious at offset 0")
	}
	if d.From() != 1 {
		t.Errorf("expected From=1, got %d", d.From())
	}
	if d.To() != 25 {
		t.Errorf("expected To=25, got %d", d.To())
	}

	d2 := packagesData{Pagination: layout.Pagination{Total: 100, Limit: 25, Offset: 50}}
	if !d2.HasPrevious() {
		t.Error("expected HasPrevious")
	}
	if d2.PrevOffset() != 25 {
		t.Errorf("expected PrevOffset=25, got %d", d2.PrevOffset())
	}
	if d2.NextOffset() != 75 {
		t.Errorf("expected NextOffset=75, got %d", d2.NextOffset())
	}

	d3 := packagesData{Pagination: layout.Pagination{Total: 10, Limit: 25, Offset: 0}}
	if d3.HasNext() {
		t.Error("expected no HasNext when total < limit")
	}
	if d3.To() != 10 {
		t.Errorf("expected To=10, got %d", d3.To())
	}
}
