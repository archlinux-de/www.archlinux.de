package sitemap

import (
	"database/sql"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"archded/internal/news"
	"archded/internal/packages"
	"archded/internal/releases"

	_ "modernc.org/sqlite"
)

func setupTestDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`CREATE TABLE repository (
			id INTEGER PRIMARY KEY, name TEXT NOT NULL, architecture TEXT NOT NULL,
			testing INTEGER NOT NULL DEFAULT 0, UNIQUE(name, architecture))`,
		`CREATE TABLE package (
			id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL,
			name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL,
			description TEXT NOT NULL DEFAULT '', url TEXT NOT NULL DEFAULT '',
			build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0,
			installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT NOT NULL DEFAULT '', packager_email TEXT NOT NULL DEFAULT '',
			popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0,
			popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT NOT NULL DEFAULT '', groups TEXT NOT NULL DEFAULT '', provides TEXT NOT NULL DEFAULT '',
			UNIQUE(repository_id, name))`,
		`CREATE TABLE news_item (
			id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE,
			description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '',
			author_link TEXT NOT NULL DEFAULT '', last_modified INTEGER NOT NULL)`,
		`CREATE TABLE release (
			version TEXT PRIMARY KEY, available INTEGER NOT NULL DEFAULT 1,
			info TEXT NOT NULL DEFAULT '', created INTEGER NOT NULL DEFAULT 0,
			release_date INTEGER NOT NULL DEFAULT 0, kernel_version TEXT NOT NULL DEFAULT '',
			file_name TEXT NOT NULL DEFAULT '', file_length INTEGER NOT NULL DEFAULT 0,
			sha1_sum TEXT NOT NULL DEFAULT '', sha256_sum TEXT NOT NULL DEFAULT '',
			b2_sum TEXT NOT NULL DEFAULT '', torrent_url TEXT NOT NULL DEFAULT '',
			magnet_uri TEXT NOT NULL DEFAULT '')`,

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
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`CREATE TABLE repository (id INTEGER PRIMARY KEY, name TEXT NOT NULL, architecture TEXT NOT NULL, testing INTEGER NOT NULL DEFAULT 0, UNIQUE(name, architecture))`,
		`CREATE TABLE package (id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL, name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', url TEXT NOT NULL DEFAULT '', build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0, installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT NOT NULL DEFAULT '', packager_email TEXT NOT NULL DEFAULT '', popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0, popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT NOT NULL DEFAULT '', groups TEXT NOT NULL DEFAULT '', provides TEXT NOT NULL DEFAULT '', UNIQUE(repository_id, name))`,
		`CREATE TABLE news_item (id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE, description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '', author_link TEXT NOT NULL DEFAULT '', last_modified INTEGER NOT NULL)`,
		`CREATE TABLE release (version TEXT PRIMARY KEY, available INTEGER NOT NULL DEFAULT 1, info TEXT NOT NULL DEFAULT '', created INTEGER NOT NULL DEFAULT 0, release_date INTEGER NOT NULL DEFAULT 0, kernel_version TEXT NOT NULL DEFAULT '', file_name TEXT NOT NULL DEFAULT '', file_length INTEGER NOT NULL DEFAULT 0, sha1_sum TEXT NOT NULL DEFAULT '', sha256_sum TEXT NOT NULL DEFAULT '', b2_sum TEXT NOT NULL DEFAULT '', torrent_url TEXT NOT NULL DEFAULT '', magnet_uri TEXT NOT NULL DEFAULT '')`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatal(err)
		}
	}

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
