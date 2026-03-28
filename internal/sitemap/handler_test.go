package sitemap

import (
	"database/sql"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"archded/internal/database"
	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/releases"
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
		`INSERT INTO package (id, repository_id, name, base, version, build_date) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1', 1700300000)`,
		`INSERT INTO news_item (id, title, link, last_modified) VALUES
			(1, 'Test News', 'https://example.com/1', 1700000000)`,
		`INSERT INTO release (version, created) VALUES ('2024.01.01', 1704067200)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestSitemap(t *testing.T) {
	db := setupTestDB(t)
	handler := NewHandler(news.NewRepository(db), packages.NewRepository(db), releases.NewRepository(db))

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/sitemap.xml", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
	ct := rr.Header().Get("Content-Type")
	if !strings.Contains(ct, "application/xml") {
		t.Errorf("expected XML content-type, got %q", ct)
	}

	body := rr.Body.String()
	if !strings.Contains(body, "<?xml") {
		t.Error("expected XML header")
	}
	if !strings.Contains(body, "/packages/core/x86_64/linux") {
		t.Error("expected package URL in sitemap")
	}
	if !strings.Contains(body, "/releases/2024.01.01") {
		t.Error("expected release URL in sitemap")
	}
	// Static pages
	for _, path := range []string{"/packages", "/news", "/mirrors", "/releases", "/download"} {
		if !strings.Contains(body, path) {
			t.Errorf("expected %q in sitemap", path)
		}
	}
}

func TestSitemap_EmptyDB(t *testing.T) {
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	handler := NewHandler(news.NewRepository(db), packages.NewRepository(db), releases.NewRepository(db))
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/sitemap.xml", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200 for empty sitemap, got %d", rr.Code)
	}
	// Should still contain static URLs
	if !strings.Contains(rr.Body.String(), "/packages") {
		t.Error("expected static URLs even with empty DB")
	}
}
