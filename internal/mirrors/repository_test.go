package mirrors

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
		`CREATE TABLE country (code TEXT PRIMARY KEY, name TEXT NOT NULL)`,
		`CREATE TABLE mirror (
			url TEXT PRIMARY KEY, country_code TEXT REFERENCES country(code),
			last_sync INTEGER, delay INTEGER, duration_avg REAL, duration_stddev REAL,
			score REAL, completion_pct REAL, ipv4 INTEGER NOT NULL DEFAULT 0,
			ipv6 INTEGER NOT NULL DEFAULT 0)`,
		`INSERT INTO country (code, name) VALUES ('DE', 'Germany'), ('US', 'United States')`,
		`INSERT INTO mirror (url, country_code, last_sync, delay, duration_avg, score, ipv4, ipv6) VALUES
			('https://mirror.de/archlinux/', 'DE', 1700000000, 100, 0.5, 1.0, 1, 1),
			('https://mirror.us/archlinux/', 'US', 1700000000, 200, 1.0, 2.0, 1, 0),
			('http://insecure.mirror/archlinux/', 'DE', 1700000000, 300, 2.0, 3.0, 1, 0)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestSearch_NoFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	mirrors, total, err := repo.Search(context.Background(), "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 {
		t.Errorf("expected 3 total, got %d", total)
	}
	if len(mirrors) != 3 {
		t.Errorf("expected 3 mirrors, got %d", len(mirrors))
	}
	// Ordered by score ASC
	if mirrors[0].URL != "https://mirror.de/archlinux/" {
		t.Errorf("expected best score first, got %q", mirrors[0].URL)
	}
}

func TestSearch_ByURL(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	mirrors, total, err := repo.Search(context.Background(), "mirror.de", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1, got %d", total)
	}
	if mirrors[0].CountryName != "Germany" {
		t.Errorf("expected Germany, got %q", mirrors[0].CountryName)
	}
}

func TestSearch_ByCountry(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	mirrors, total, err := repo.Search(context.Background(), "United States", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1, got %d", total)
	}
	if mirrors[0].URL != "https://mirror.us/archlinux/" {
		t.Errorf("expected US mirror, got %q", mirrors[0].URL)
	}
}

func TestSearch_Pagination(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	mirrors, total, err := repo.Search(context.Background(), "", 2, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 {
		t.Errorf("expected total 3, got %d", total)
	}
	if len(mirrors) != 2 {
		t.Errorf("expected 2, got %d", len(mirrors))
	}
}

func TestSearch_NoResults(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	mirrors, total, err := repo.Search(context.Background(), "nonexistent", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 0 || len(mirrors) != 0 {
		t.Errorf("expected 0, got %d total, %d mirrors", total, len(mirrors))
	}
}
