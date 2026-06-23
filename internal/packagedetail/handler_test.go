package packagedetail

import (
	"database/sql"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"archded/internal/database"
	"archded/internal/ui/layout"
)

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func setupHandlerDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		// Data
		`INSERT INTO repository (id, name, architecture, testing) VALUES
			(1, 'core', 'x86_64', 0),
			(2, 'extra', 'x86_64', 0),
			(3, 'core-testing', 'x86_64', 1)`,
		`INSERT INTO package (id, repository_id, name, base, version, description, build_date, popularity_recent, licenses) VALUES
			(1, 1, 'bash', 'bash', '5.2-1', 'GNU Bourne Again shell', 1700100000, 40.0, '["GPL"]'),
			(2, 2, 'firefox', 'firefox', '125.0-1', 'Web browser', 1700200000, 30.0, '["MPL-2.0"]'),
			(3, 3, 'bash', 'bash', '5.3-rc1', 'GNU Bourne Again shell (testing)', 1700300000, 0.0, '')`,
		`INSERT INTO package_relation (package_id, type, target_name) VALUES
			(1, 'depends', 'glibc'),
			(2, 'depends', 'glibc'),
			(2, 'depends', 'nss')`,
		`INSERT INTO files (package_id, file_list) VALUES
			(1, 'usr/bin/bash
usr/share/bash/bash_completion')`,

		// Populate FTS
		`INSERT INTO package_fts (rowid, name, base, description, groups, provides, keywords, categories)
			SELECT id, name, base, description, groups, provides, keywords, categories FROM package`,
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
