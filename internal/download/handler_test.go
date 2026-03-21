package download

import (
	"database/sql"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"www/internal/mirrors"
	"www/internal/packages"
	"www/internal/releases"
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
		`CREATE TABLE country (code TEXT PRIMARY KEY, name TEXT NOT NULL)`,
		`CREATE TABLE mirror (
			url TEXT PRIMARY KEY, country_code TEXT REFERENCES country(code),
			last_sync INTEGER, delay INTEGER, duration_avg REAL, duration_stddev REAL,
			score REAL, completion_pct REAL, ipv4 INTEGER NOT NULL DEFAULT 0,
			ipv6 INTEGER NOT NULL DEFAULT 0)`,
		`CREATE TABLE release (
			version TEXT PRIMARY KEY, available INTEGER NOT NULL DEFAULT 1,
			info TEXT, created INTEGER, release_date INTEGER, kernel_version TEXT,
			file_name TEXT, file_length INTEGER, sha1_sum TEXT, sha256_sum TEXT,
			b2_sum TEXT, torrent_url TEXT, magnet_uri TEXT)`,

		`INSERT INTO repository (id, name, architecture) VALUES (1, 'core', 'x86_64')`,
		`INSERT INTO package (id, repository_id, name, base, version, build_date) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1', 1700300000)`,
		`INSERT INTO country (code, name) VALUES ('DE', 'Germany')`,
		`INSERT INTO mirror (url, country_code, score, ipv4) VALUES
			('https://mirror.example.com/archlinux/', 'DE', 1.0, 1)`,
		`INSERT INTO release (version, available, info, release_date, kernel_version, file_name, file_length, created) VALUES
			('2024.01.01', 1, 'January release', 1704067200, '6.6.7', 'archlinux-2024.01.01-x86_64.iso', 900000000, 1704067200),
			('2023.06.01', 0, 'June release', 1685577600, '6.3.9', 'archlinux-2023.06.01-x86_64.iso', 800000000, 1685577600),
			('0.7.1', 0, 'Legacy release', 1180000000, '', 'archlinux-0.7.1.iso', 0, 1180000000)`,
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

func newTestMux(t *testing.T) *http.ServeMux {
	t.Helper()
	db := setupTestDB(t)
	handler := NewHandler(
		releases.NewRepository(db),
		packages.NewRepository(db),
		mirrors.NewRepository(db),
		testManifest(),
		"https://fallback.mirror.example.com/",
	)
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)
	return mux
}

func TestIndex(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
}

func TestISO_Available(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2024.01.01/archlinux-2024.01.01-x86_64.iso", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.Contains(loc, "mirror.example.com") {
		t.Errorf("expected redirect to mirror, got %q", loc)
	}
	if !strings.Contains(loc, "iso/2024.01.01/archlinux-2024.01.01-x86_64.iso") {
		t.Errorf("expected ISO path in redirect, got %q", loc)
	}
}

func TestISO_Unavailable(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2023.06.01/archlinux-2023.06.01-x86_64.iso", nil))

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 to archive, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.HasPrefix(loc, "https://archive.archlinux.org/") {
		t.Errorf("expected archive redirect, got %q", loc)
	}
}

func TestISO_NotFound(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/9999.99.99/file.iso", nil))

	if rr.Code != http.StatusNotFound {
		t.Errorf("expected 404, got %d", rr.Code)
	}
}

func TestISODir_Available(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2024.01.01/", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
}

func TestISODir_Unavailable(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2023.06.01/", nil))

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 to archive, got %d", rr.Code)
	}
}

func TestPkg(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/core/os/x86_64/linux-6.6.7-1-x86_64.pkg.tar.zst", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.Contains(loc, "core/os/x86_64/linux-6.6.7-1-x86_64.pkg.tar.zst") {
		t.Errorf("expected package path in redirect, got %q", loc)
	}
}

func TestFallback(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/some/path", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
}

func TestISO_VersionDirMap(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/0.7.1/archlinux-0.7.1.iso", nil))

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 to archive, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.Contains(loc, "iso/0.7/archlinux-0.7.1.iso") {
		t.Errorf("expected version dir mapping 0.7.1→0.7, got %q", loc)
	}
}

func TestISODir_VersionDirMap(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/0.7.1/", nil))

	if rr.Code != http.StatusMovedPermanently {
		t.Errorf("expected 301 to archive, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.Contains(loc, "iso/0.7/") {
		t.Errorf("expected version dir mapping, got %q", loc)
	}
}

func TestISO_SigFile(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2024.01.01/archlinux-2024.01.01-x86_64.iso.sig", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.HasSuffix(loc, ".iso.sig") {
		t.Errorf("expected .sig in redirect, got %q", loc)
	}
}

func TestPkg_ZstExtension(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/core/os/x86_64/linux-6.6.7-1-x86_64.pkg.tar.zst", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
}

func TestPkg_XzExtension(t *testing.T) {
	mux := newTestMux(t)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/core/os/x86_64/linux-6.6.7-1-x86_64.pkg.tar.xz", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
}

func TestFallbackToDefaultMirror(t *testing.T) {
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`CREATE TABLE repository (id INTEGER PRIMARY KEY, name TEXT NOT NULL, architecture TEXT NOT NULL, testing INTEGER NOT NULL DEFAULT 0, UNIQUE(name, architecture))`,
		`CREATE TABLE package (id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL, name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', url TEXT, build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0, installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT, packager_email TEXT, popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0, popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT, groups TEXT, provides TEXT, UNIQUE(repository_id, name))`,
		`CREATE TABLE country (code TEXT PRIMARY KEY, name TEXT NOT NULL)`,
		`CREATE TABLE mirror (url TEXT PRIMARY KEY, country_code TEXT, last_sync INTEGER, delay INTEGER, duration_avg REAL, duration_stddev REAL, score REAL, completion_pct REAL, ipv4 INTEGER NOT NULL DEFAULT 0, ipv6 INTEGER NOT NULL DEFAULT 0)`,
		`CREATE TABLE release (version TEXT PRIMARY KEY, available INTEGER NOT NULL DEFAULT 1, info TEXT, created INTEGER, release_date INTEGER, kernel_version TEXT, file_name TEXT, file_length INTEGER, sha1_sum TEXT, sha256_sum TEXT, b2_sum TEXT, torrent_url TEXT, magnet_uri TEXT)`,
		`INSERT INTO release (version, available, created) VALUES ('2024.01.01', 1, 1704067200)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatal(err)
		}
	}

	handler := NewHandler(
		releases.NewRepository(db),
		packages.NewRepository(db),
		mirrors.NewRepository(db),
		testManifest(),
		"https://geo.mirror.pkgbuild.com/",
	)
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/download/iso/2024.01.01/archlinux.iso", nil))

	if rr.Code != http.StatusTemporaryRedirect {
		t.Errorf("expected 307, got %d", rr.Code)
	}
	loc := rr.Header().Get("Location")
	if !strings.HasPrefix(loc, "https://geo.mirror.pkgbuild.com/") {
		t.Errorf("expected fallback to default mirror, got %q", loc)
	}
}

func TestClientAddr(t *testing.T) {
	tests := []struct {
		name       string
		remoteAddr string
		forwarded  string
		want       string
	}{
		{"remote addr with port", "192.168.1.1:1234", "", "192.168.1.1"},
		{"remote addr without port", "192.168.1.1", "", "192.168.1.1"},
		{"X-Forwarded-For", "127.0.0.1:1234", "10.0.0.1", "10.0.0.1"},
		{"X-Forwarded-For with chain", "127.0.0.1:1234", "10.0.0.1, 10.0.0.2", "10.0.0.1"},
	}
	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			req := httptest.NewRequest(http.MethodGet, "/", nil)
			req.RemoteAddr = tt.remoteAddr
			if tt.forwarded != "" {
				req.Header.Set("X-Forwarded-For", tt.forwarded)
			}
			got := clientAddr(req)
			if got != tt.want {
				t.Errorf("clientAddr() = %q, want %q", got, tt.want)
			}
		})
	}
}
