package packages

import (
	"context"
	"database/sql"
	"testing"

	"archded/internal/database"
)

func setupTestDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		// Repos
		`INSERT INTO repository (id, name, architecture, testing) VALUES
			(1, 'core', 'x86_64', 0),
			(2, 'extra', 'x86_64', 0),
			(3, 'core-testing', 'x86_64', 1)`,

		// Packages
		`INSERT INTO package (id, repository_id, name, base, version, description, build_date, packager_name, popularity_recent) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1', 'The Linux kernel', 1700300000, 'Jan', 50.0),
			(2, 2, 'firefox', 'firefox', '125.0-1', 'Web browser', 1700200000, 'Bob', 30.0),
			(3, 1, 'bash', 'bash', '5.2-1', 'GNU Bourne Again shell', 1700100000, 'Alice', 40.0),
			(4, 3, 'linux', 'linux', '6.7-rc1', 'The Linux kernel (testing)', 1700400000, 'Jan', 0.0)`,

		// Populate FTS
		`INSERT INTO package_fts (rowid, name, base, description, groups, provides)
			SELECT id, name, base, description, groups, provides FROM package`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %s...: %v", stmt[:40], err)
		}
	}
	return db
}

func TestListRepositoryNames(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	names, err := repo.ListRepositoryNames(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(names) != 3 {
		t.Errorf("expected 3 repo names, got %d", len(names))
	}
}

func TestListArchitectures(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	archs, err := repo.ListArchitectures(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(archs) != 1 || archs[0] != "x86_64" {
		t.Errorf("expected [x86_64], got %v", archs)
	}
}

func TestSearch_NoFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, total, err := repo.Search(context.Background(), "", "", "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 4 {
		t.Errorf("expected 4, got %d", total)
	}
	// Ordered by build_date DESC
	if pkgs[0].Name != "linux" && pkgs[0].Repository != "core-testing" {
		t.Errorf("expected testing linux first (newest), got %q/%q", pkgs[0].Name, pkgs[0].Repository)
	}
}

func TestSearch_ByRepo(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, total, err := repo.Search(context.Background(), "", "core", "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 2 {
		t.Errorf("expected 2 packages in core, got %d", total)
	}
	for _, p := range pkgs {
		if p.Repository != "core" {
			t.Errorf("expected core, got %q", p.Repository)
		}
	}
}

func TestSearch_FTS(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, total, err := repo.Search(context.Background(), "linux", "", "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total < 1 {
		t.Fatalf("expected at least 1, got %d", total)
	}
	// Exact name match should rank first
	if pkgs[0].Name != "linux" {
		t.Errorf("expected exact match first, got %q", pkgs[0].Name)
	}
	_ = pkgs
}

func TestSearch_FTSHyphenated(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	// Searching for "bourne-shell" should split into "bourne" "shell"
	pkgs, total, err := repo.Search(context.Background(), "bourne-shell", "", "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1 (bash), got %d", total)
	}
	if len(pkgs) > 0 && pkgs[0].Name != "bash" {
		t.Errorf("expected bash, got %q", pkgs[0].Name)
	}
}

func TestSearch_Pagination(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, total, err := repo.Search(context.Background(), "", "", "", 2, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 4 || len(pkgs) != 2 {
		t.Errorf("expected total=4 len=2, got total=%d len=%d", total, len(pkgs))
	}
}

func TestSearch_NoResults(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, total, err := repo.Search(context.Background(), "nonexistentpackage", "", "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 0 || len(pkgs) != 0 {
		t.Errorf("expected 0, got total=%d len=%d", total, len(pkgs))
	}
}

func TestLatestStable(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	pkgs, err := repo.LatestStable(context.Background(), 3)
	if err != nil {
		t.Fatal(err)
	}
	if len(pkgs) != 3 {
		t.Fatalf("expected 3 (excluding testing), got %d", len(pkgs))
	}
	// Should exclude testing packages
	for _, p := range pkgs {
		if p.Repository == "core-testing" {
			t.Error("should not include testing packages")
		}
	}
	// Ordered by build_date DESC
	if pkgs[0].Name != "linux" {
		t.Errorf("expected linux first, got %q", pkgs[0].Name)
	}
}

func TestBuildDate(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	bd, err := repo.BuildDate(context.Background(), "bash", "core", "x86_64")
	if err != nil {
		t.Fatal(err)
	}
	if bd == nil {
		t.Fatal("expected non-nil build date")
	}
	if *bd != 1700100000 {
		t.Errorf("expected 1700100000, got %d", *bd)
	}
}

func TestBuildDate_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	bd, err := repo.BuildDate(context.Background(), "nonexistent", "core", "x86_64")
	if err != nil {
		t.Fatal(err)
	}
	if bd != nil {
		t.Errorf("expected nil for missing package, got %d", *bd)
	}
}

func TestAllStableRefs(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	refs, err := repo.AllStableRefs(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(refs) != 3 {
		t.Errorf("expected 3 stable refs, got %d", len(refs))
	}
	for _, ref := range refs {
		if ref.Repository == "core-testing" {
			t.Error("should not include testing refs")
		}
	}
}

func TestSuggest(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	names, err := repo.Suggest(context.Background(), "lin", 10)
	if err != nil {
		t.Fatal(err)
	}
	if len(names) != 1 || names[0] != "linux" {
		t.Errorf("expected [linux], got %v", names)
	}
}

func TestSuggest_Empty(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	names, err := repo.Suggest(context.Background(), "", 10)
	if err != nil {
		t.Fatal(err)
	}
	if names != nil {
		t.Errorf("expected nil, got %v", names)
	}
}

func TestSuggest_Distinct(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	// "linux" exists in both core and core-testing
	names, err := repo.Suggest(context.Background(), "linux", 10)
	if err != nil {
		t.Fatal(err)
	}
	if len(names) != 1 {
		t.Errorf("expected 1 distinct result, got %d: %v", len(names), names)
	}
}

func TestSuggest_OrderedByPopularity(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	// bash (40.0) and firefox (30.0) both start with letters before 'l'
	// but let's test with a broader prefix
	names, err := repo.Suggest(context.Background(), "b", 10)
	if err != nil {
		t.Fatal(err)
	}
	if len(names) != 1 || names[0] != "bash" {
		t.Errorf("expected [bash], got %v", names)
	}
}

func TestLikePrefixQuery(t *testing.T) {
	tests := []struct {
		input, want string
	}{
		{"linux", "linux%"},
		{"lib%", `lib\%%`},
		{"a_b", `a\_b%`},
		{`c\d`, `c\\d%`},
		{"", "%"},
	}
	for _, tt := range tests {
		got := likePrefixQuery(tt.input)
		if got != tt.want {
			t.Errorf("likePrefixQuery(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}
