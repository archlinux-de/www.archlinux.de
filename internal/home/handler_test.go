package home

import (
	"database/sql"
	"net/http"
	"net/http/httptest"
	"testing"

	"archded/internal/database"
	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/ui/layout"
)

func setupTestDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`INSERT INTO repository (id, name, architecture) VALUES (1, 'core', 'x86_64')`,
		`INSERT INTO package (id, repository_id, name, base, version, description, build_date, packager_name) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1', 'The Linux kernel', 1700300000, 'Jan')`,
		`INSERT INTO news_item (id, title, link, description, author_name, last_modified) VALUES
			(1, 'Test News', 'https://example.com/1', '<p>Content</p>', 'Alice', 1700000000)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func TestIndex(t *testing.T) {
	db := setupTestDB(t)
	handler := NewHandler(news.NewRepository(db), packages.NewRepository(db), testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestIndex_EmptyDB(t *testing.T) {
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	handler := NewHandler(news.NewRepository(db), packages.NewRepository(db), testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200 even with empty DB, got %d", rr.Code)
	}
}

func TestIndex_LegacyRedirect(t *testing.T) {
	db := setupTestDB(t)
	handler := NewHandler(news.NewRepository(db), packages.NewRepository(db), testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/?page=Packages", nil))

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 for legacy redirect, got %d", rr.Code)
	}
	if loc := rr.Header().Get("Location"); loc != "/packages" {
		t.Errorf("expected /packages, got %q", loc)
	}
}
