package home

import (
	"database/sql"
	"net/http"
	"net/http/httptest"
	"testing"

	"www/internal/news"
	"www/internal/packages"
	"www/internal/ui/layout"

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
			description TEXT NOT NULL DEFAULT '', url TEXT,
			build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0,
			installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT, packager_email TEXT,
			popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0,
			popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT, groups TEXT, provides TEXT,
			UNIQUE(repository_id, name))`,
		`CREATE TABLE news_item (
			id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE,
			description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '',
			author_link TEXT, last_modified INTEGER NOT NULL)`,
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
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`CREATE TABLE repository (id INTEGER PRIMARY KEY, name TEXT NOT NULL, architecture TEXT NOT NULL, testing INTEGER NOT NULL DEFAULT 0, UNIQUE(name, architecture))`,
		`CREATE TABLE package (id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL, name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', url TEXT, build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0, installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT, packager_email TEXT, popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0, popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT, groups TEXT, provides TEXT, UNIQUE(repository_id, name))`,
		`CREATE TABLE news_item (id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE, description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '', author_link TEXT, last_modified INTEGER NOT NULL)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatal(err)
		}
	}

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
