package releases

import (
	"context"
	"database/sql"
	"testing"

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
		`CREATE TABLE release (
			version TEXT PRIMARY KEY, available INTEGER NOT NULL DEFAULT 1,
			info TEXT NOT NULL DEFAULT '', created INTEGER NOT NULL DEFAULT 0,
			release_date INTEGER NOT NULL DEFAULT 0, kernel_version TEXT NOT NULL DEFAULT '',
			file_name TEXT NOT NULL DEFAULT '', file_length INTEGER NOT NULL DEFAULT 0,
			sha1_sum TEXT NOT NULL DEFAULT '', sha256_sum TEXT NOT NULL DEFAULT '',
			b2_sum TEXT NOT NULL DEFAULT '', torrent_url TEXT NOT NULL DEFAULT '',
			magnet_uri TEXT NOT NULL DEFAULT '', pgp_fingerprint TEXT NOT NULL DEFAULT '',
			wkd_email TEXT NOT NULL DEFAULT '')`,
		`INSERT INTO release (version, available, info, created, release_date, kernel_version, file_name, file_length) VALUES
			('2024.01.01', 1, 'January release', 1704067200, 1704067200, '6.6.7', 'archlinux-2024.01.01-x86_64.iso', 900000000),
			('2023.12.01', 1, 'December release', 1701388800, 1701388800, '6.6.4', 'archlinux-2023.12.01-x86_64.iso', 890000000),
			('2023.11.01', 0, 'November release', 1698796800, 1698796800, '6.5.9', 'archlinux-2023.11.01-x86_64.iso', 880000000)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestSearch_NoFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rels, total, err := repo.Search(context.Background(), "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 {
		t.Errorf("expected 3, got %d", total)
	}
	// Ordered by release_date DESC
	if rels[0].Version != "2024.01.01" {
		t.Errorf("expected newest first, got %q", rels[0].Version)
	}
}

func TestSearch_WithFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rels, total, err := repo.Search(context.Background(), "6.6.7", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1, got %d", total)
	}
	if rels[0].Version != "2024.01.01" {
		t.Errorf("expected 2024.01.01, got %q", rels[0].Version)
	}
}

func TestSearch_Pagination(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rels, total, err := repo.Search(context.Background(), "", 2, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 || len(rels) != 2 {
		t.Errorf("expected total=3 len=2, got total=%d len=%d", total, len(rels))
	}
}

func TestSearch_NoResults(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rels, total, err := repo.Search(context.Background(), "nonexistent", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 0 || len(rels) != 0 {
		t.Errorf("expected 0, got %d", total)
	}
}

func TestFindByVersion(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rel, err := repo.FindByVersion(context.Background(), "2024.01.01")
	if err != nil {
		t.Fatal(err)
	}
	if rel.KernelVersion != "6.6.7" {
		t.Errorf("expected kernel 6.6.7, got %q", rel.KernelVersion)
	}
	if rel.FileName != "archlinux-2024.01.01-x86_64.iso" {
		t.Errorf("expected filename, got %q", rel.FileName)
	}
}

func TestFindByVersion_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	_, err := repo.FindByVersion(context.Background(), "9999.99.99")
	if err == nil {
		t.Error("expected error for missing version")
	}
}

func TestLatestAvailable(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rel, err := repo.LatestAvailable(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if rel.Version != "2024.01.01" {
		t.Errorf("expected 2024.01.01, got %q", rel.Version)
	}
}

func TestAllAvailable(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	rels, err := repo.AllAvailable(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(rels) != 2 {
		t.Errorf("expected 2 available, got %d", len(rels))
	}
	for _, rel := range rels {
		if !rel.Available {
			t.Errorf("expected only available releases, got %q", rel.Version)
		}
	}
}

func TestAvailability(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ra, err := repo.Availability(context.Background(), "2024.01.01")
	if err != nil {
		t.Fatal(err)
	}
	if !ra.Available {
		t.Error("expected available")
	}

	ra2, err := repo.Availability(context.Background(), "2023.11.01")
	if err != nil {
		t.Fatal(err)
	}
	if ra2.Available {
		t.Error("expected not available")
	}
}

func TestAvailability_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	_, err := repo.Availability(context.Background(), "9999.99.99")
	if err == nil {
		t.Error("expected error for missing version")
	}
}

func TestAllRefs(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	refs, err := repo.AllRefs(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(refs) != 3 {
		t.Errorf("expected 3, got %d", len(refs))
	}
}

func TestISOUrl(t *testing.T) {
	r := Release{Version: "2024.01.01", FileName: "archlinux-2024.01.01-x86_64.iso"}
	if u := r.ISOUrl(); u != "/download/iso/2024.01.01/archlinux-2024.01.01-x86_64.iso" {
		t.Errorf("unexpected URL: %q", u)
	}
}

func TestISOUrl_Empty(t *testing.T) {
	r := Release{Version: "2024.01.01"}
	if u := r.ISOUrl(); u != "" {
		t.Errorf("expected empty, got %q", u)
	}
}

func TestISOSigUrl_Old(t *testing.T) {
	r := Release{Version: "2012.07.01", FileName: "archlinux.iso", ReleaseDate: 1341100000}
	if u := r.ISOSigUrl(); u != "" {
		t.Errorf("expected empty for pre-2012-07-15, got %q", u)
	}
}

func TestISOSigUrl_Recent(t *testing.T) {
	r := Release{Version: "2024.01.01", FileName: "archlinux.iso", ReleaseDate: 1704067200}
	if u := r.ISOSigUrl(); u != "/download/iso/2024.01.01/archlinux.iso.sig" {
		t.Errorf("unexpected sig URL: %q", u)
	}
}
