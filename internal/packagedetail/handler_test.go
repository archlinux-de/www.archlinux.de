package packagedetail

import (
	"database/sql"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"www/internal/ui/layout"

	_ "modernc.org/sqlite"
)

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func setupHandlerDB(t *testing.T) *sql.DB {
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
			id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL REFERENCES repository(id),
			name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL,
			description TEXT NOT NULL DEFAULT '', url TEXT,
			build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0,
			installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT, packager_email TEXT,
			popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0,
			popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT, groups TEXT, provides TEXT,
			UNIQUE(repository_id, name))`,
		`CREATE VIRTUAL TABLE package_fts USING fts5(
			name, base, description, groups, provides,
			content='package', content_rowid='id')`,
		`CREATE TABLE package_relation (
			id INTEGER PRIMARY KEY, package_id INTEGER NOT NULL REFERENCES package(id),
			type TEXT NOT NULL, target_name TEXT NOT NULL,
			target_version TEXT, version_constraint TEXT)`,
		`CREATE INDEX idx_package_relation_target ON package_relation(target_name)`,
		`CREATE TABLE files (package_id INTEGER PRIMARY KEY REFERENCES package(id), file_list TEXT NOT NULL)`,

		// Data
		`INSERT INTO repository (id, name, architecture, testing) VALUES
			(1, 'core', 'x86_64', 0),
			(2, 'extra', 'x86_64', 0),
			(3, 'core-testing', 'x86_64', 1)`,
		`INSERT INTO package (id, repository_id, name, base, version, description, build_date, popularity_recent, licenses) VALUES
			(1, 1, 'bash', 'bash', '5.2-1', 'GNU Bourne Again shell', 1700100000, 40.0, '["GPL"]'),
			(2, 2, 'firefox', 'firefox', '125.0-1', 'Web browser', 1700200000, 30.0, '["MPL-2.0"]'),
			(3, 3, 'bash', 'bash', '5.3-rc1', 'GNU Bourne Again shell (testing)', 1700300000, 0.0, NULL)`,
		`INSERT INTO package_relation (package_id, type, target_name) VALUES
			(1, 'depends', 'glibc'),
			(2, 'depends', 'glibc'),
			(2, 'depends', 'nss')`,
		`INSERT INTO files (package_id, file_list) VALUES
			(1, 'usr/bin/bash
usr/share/bash/bash_completion')`,

		// Populate FTS
		`INSERT INTO package_fts (rowid, name, base, description, groups, provides)
			SELECT id, name, base, description, COALESCE(groups, ''), COALESCE(provides, '') FROM package`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestShow(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/core/x86_64/bash", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestShow_NotFound(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/core/x86_64/nonexistent", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}

func TestResolve_SingleResult(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/x86_64/firefox", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307 redirect, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if loc != "/packages/extra/x86_64/firefox" {
		t.Errorf("expected redirect to /packages/extra/x86_64/firefox, got %q", loc)
	}
}

func TestResolve_MultipleResults(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	// bash exists in core and core-testing
	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/x86_64/bash", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200 (resolve page), got %d", rr.Code)
	}
}

func TestResolveHandler_NotFound(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/x86_64/nonexistent", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}

func TestFiles(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/core/x86_64/bash/files", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
	if ct := rr.Header().Get("Content-Type"); !strings.Contains(ct, "application/json") {
		t.Errorf("expected JSON content-type, got %q", ct)
	}

	var files []string
	if err := json.NewDecoder(rr.Body).Decode(&files); err != nil {
		t.Fatal(err)
	}
	if len(files) != 2 {
		t.Errorf("expected 2 files, got %d", len(files))
	}
}

func TestFiles_NoFiles(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/extra/x86_64/firefox/files", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}

	body := strings.TrimSpace(rr.Body.String())
	if body != "null" {
		t.Errorf("expected null for package without files, got %q", body)
	}
}

func TestInverseDeps(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/core/x86_64/glibc/inverse-dependencies", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}

	var rels map[string][]string
	if err := json.NewDecoder(rr.Body).Decode(&rels); err != nil {
		t.Fatal(err)
	}
	if len(rels["depends"]) != 2 {
		t.Errorf("expected 2 inverse deps on glibc, got %d", len(rels["depends"]))
	}
}

func TestInverseDeps_None(t *testing.T) {
	repo := NewRepository(setupHandlerDB(t))
	handler := NewHandler(repo, testManifest())

	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/extra/x86_64/firefox/inverse-dependencies", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}

	var rels map[string][]string
	if err := json.NewDecoder(rr.Body).Decode(&rels); err != nil {
		t.Fatal(err)
	}
	if len(rels) != 0 {
		t.Errorf("expected empty map, got %v", rels)
	}
}
