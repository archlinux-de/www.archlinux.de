package packagedetail

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
			(3, 'extra-testing', 'x86_64', 1)`,

		// Packages
		`INSERT INTO package (id, repository_id, name, base, version) VALUES
			(1, 1, 'bash', 'bash', '5.2-1'),
			(2, 2, 'firefox', 'firefox', '125.0-1'),
			(3, 3, 'firefox', 'firefox', '126.0-1'),
			(4, 2, 'jdk21-openjdk', 'java21-openjdk', '21.0.3-1'),
			(5, 2, 'jdk17-openjdk', 'java17-openjdk', '17.0.11-1'),
			(6, 2, 'jdk-openjdk', 'java-openjdk', '22.0.1-1'),
			(7, 2, 'python', 'python', '3.12.3-1'),
			(8, 2, 'python2', 'python2', '2.7.18-1')`,

		// Provides: java-environment with versioned provides
		`INSERT INTO package_relation (package_id, type, target_name, target_version, version_constraint) VALUES
			(4, 'provides', 'java-environment', '21', 'EQ'),
			(5, 'provides', 'java-environment', '17', 'EQ'),
			(6, 'provides', 'java-environment', '22', 'EQ')`,

		// Provides: python with unversioned provide
		`INSERT INTO package_relation (package_id, type, target_name) VALUES
			(7, 'provides', 'python3')`,

		// Provides: python2 also provides python (versioned)
		`INSERT INTO package_relation (package_id, type, target_name, target_version, version_constraint) VALUES
			(8, 'provides', 'python', '2.7.18', 'EQ')`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %s: %v", stmt[:40], err)
		}
	}

	return db
}

func TestResolve_DirectNameMatch(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	results, err := repo.Resolve(ctx, "x86_64", "bash", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 1 {
		t.Fatalf("expected 1 result, got %d", len(results))
	}
	if results[0].Name != "bash" || results[0].Repository != "core" {
		t.Errorf("got %+v", results[0])
	}
}

func TestResolve_DirectNameMultipleRepos(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// firefox exists in extra and extra-testing
	results, err := repo.Resolve(ctx, "x86_64", "firefox", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 2 {
		t.Fatalf("expected 2 results, got %d", len(results))
	}
	// non-testing should come first (ORDER BY r.testing ASC)
	if results[0].Repository != "extra" {
		t.Errorf("expected extra first, got %s", results[0].Repository)
	}
	if results[1].Repository != "extra-testing" {
		t.Errorf("expected extra-testing second, got %s", results[1].Repository)
	}
}

func TestResolve_ProviderNoVersion(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// python3 is provided by python (unversioned)
	results, err := repo.Resolve(ctx, "x86_64", "python3", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 1 {
		t.Fatalf("expected 1 result, got %d", len(results))
	}
	if results[0].Name != "python" {
		t.Errorf("expected python, got %s", results[0].Name)
	}
}

func TestResolve_ProviderExactVersion(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// java-environment=21 should match jdk21-openjdk
	results, err := repo.Resolve(ctx, "x86_64", "java-environment", "21", "EQ")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 1 {
		t.Fatalf("expected 1 result, got %d", len(results))
	}
	if results[0].Name != "jdk21-openjdk" {
		t.Errorf("expected jdk21-openjdk, got %s", results[0].Name)
	}
}

func TestResolve_ProviderGE(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// java-environment>=20 should match jdk21 (21) and jdk-openjdk (22)
	results, err := repo.Resolve(ctx, "x86_64", "java-environment", "20", "GE")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 2 {
		t.Fatalf("expected 2 results, got %d", len(results))
	}
	names := map[string]bool{}
	for _, r := range results {
		names[r.Name] = true
	}
	if !names["jdk21-openjdk"] || !names["jdk-openjdk"] {
		t.Errorf("expected jdk21-openjdk and jdk-openjdk, got %v", names)
	}
}

func TestResolve_ProviderLT(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// java-environment<20 should match jdk17-openjdk (17)
	results, err := repo.Resolve(ctx, "x86_64", "java-environment", "20", "LT")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 1 {
		t.Fatalf("expected 1 result, got %d", len(results))
	}
	if results[0].Name != "jdk17-openjdk" {
		t.Errorf("expected jdk17-openjdk, got %s", results[0].Name)
	}
}

func TestResolve_ProviderVersionedRequestUnversionedProvide(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// python3>=3.10 — python provides python3 without a version, so it shouldn't match
	results, err := repo.Resolve(ctx, "x86_64", "python3", "3.10", "GE")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 0 {
		t.Fatalf("expected 0 results (unversioned provide can't satisfy versioned request), got %d", len(results))
	}
}

func TestResolve_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	results, err := repo.Resolve(ctx, "x86_64", "nonexistent", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 0 {
		t.Fatalf("expected 0 results, got %d", len(results))
	}
}

func TestResolve_WrongArch(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	results, err := repo.Resolve(ctx, "aarch64", "bash", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 0 {
		t.Fatalf("expected 0 results for wrong arch, got %d", len(results))
	}
}

func TestResolve_DirectMatchSkipsProviders(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	ctx := context.Background()

	// python2 provides "python" but a direct name match for "python" should return the python package
	results, err := repo.Resolve(ctx, "x86_64", "python", "", "")
	if err != nil {
		t.Fatal(err)
	}
	if len(results) != 1 {
		t.Fatalf("expected 1 result, got %d", len(results))
	}
	if results[0].Name != "python" {
		t.Errorf("expected python, got %s", results[0].Name)
	}
}

func TestSatisfies(t *testing.T) {
	tests := []struct {
		provided, requested, constraint string
		want                            bool
	}{
		// No version requested — always matches
		{"21", "", "", true},
		{"", "", "", true},

		// EQ
		{"21", "21", "EQ", true},
		{"17", "21", "EQ", false},

		// GE
		{"21", "21", "GE", true},
		{"22", "21", "GE", true},
		{"17", "21", "GE", false},

		// LE
		{"21", "21", "LE", true},
		{"17", "21", "LE", true},
		{"22", "21", "LE", false},

		// GT
		{"22", "21", "GT", true},
		{"21", "21", "GT", false},

		// LT
		{"17", "21", "LT", true},
		{"21", "21", "LT", false},

		// Unversioned provide can't satisfy versioned request
		{"", "21", "GE", false},

		// Epoch-aware comparison
		{"1:1.0", "2.0", "GE", true},
		{"2.0", "1:1.0", "GE", false},

		// Unknown constraint — permissive
		{"21", "21", "UNKNOWN", true},
	}

	for _, tt := range tests {
		got := satisfies(tt.provided, tt.requested, tt.constraint)
		if got != tt.want {
			t.Errorf("satisfies(%q, %q, %q) = %v, want %v",
				tt.provided, tt.requested, tt.constraint, got, tt.want)
		}
	}
}
