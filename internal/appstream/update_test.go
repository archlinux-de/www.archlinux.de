package appstream

import (
	"context"
	"database/sql"
	"testing"

	"archded/internal/database"
)

func setupPackageDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`INSERT INTO repository (id, name, architecture, testing) VALUES (1, 'extra', 'x86_64', 0)`,
		`INSERT INTO package (id, repository_id, name, base, version, description) VALUES
			(1, 1, 'firefox', 'firefox', '120.0-1', 'Standalone web browser'),
			(2, 1, 'konsole', 'konsole', '23.08-1', 'KDE terminal emulator'),
			(3, 1, 'linux', 'linux', '6.7-1', 'The Linux kernel')`,
		`INSERT INTO package_fts (rowid, name, base, description, groups, provides, keywords, categories)
			SELECT id, name, base, description, groups, provides, keywords, categories FROM package`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestApplyTerms_WritesAndRebuildsFTS(t *testing.T) {
	db := setupPackageDB(t)
	ctx := context.Background()

	acc := map[string]*pkgTerms{
		"firefox": {
			keywords:   []string{"internet browser", "www"},
			categories: []string{"Network", "WebBrowser"},
		},
		"konsole": {
			keywords:   []string{"shell"},
			categories: []string{"System", "TerminalEmulator"},
		},
		// "linux" not in accumulator — its columns should stay empty.
	}

	updated, err := applyTerms(ctx, db, acc)
	if err != nil {
		t.Fatal(err)
	}
	if updated != 2 {
		t.Errorf("updated rows = %d, want 2", updated)
	}

	rows := map[string]struct{ kw, cat string }{}
	r, err := db.Query(`SELECT name, keywords, categories FROM package`)
	if err != nil {
		t.Fatal(err)
	}
	defer func() { _ = r.Close() }()
	for r.Next() {
		var name, kw, cat string
		if err := r.Scan(&name, &kw, &cat); err != nil {
			t.Fatal(err)
		}
		rows[name] = struct{ kw, cat string }{kw, cat}
	}

	if rows["firefox"].kw != "internet browser www" {
		t.Errorf("firefox keywords = %q", rows["firefox"].kw)
	}
	if rows["firefox"].cat != "Network WebBrowser" {
		t.Errorf("firefox categories = %q", rows["firefox"].cat)
	}
	if rows["konsole"].cat != "System TerminalEmulator" {
		t.Errorf("konsole categories = %q", rows["konsole"].cat)
	}
	if rows["linux"].kw != "" || rows["linux"].cat != "" {
		t.Errorf("linux should have empty appstream columns, got kw=%q cat=%q",
			rows["linux"].kw, rows["linux"].cat)
	}

	// FTS must match on the new keyword/category content.
	var name string
	if err := db.QueryRow(
		`SELECT name FROM package_fts WHERE package_fts MATCH 'WebBrowser'`).Scan(&name); err != nil {
		t.Fatalf("expected firefox via category match: %v", err)
	}
	if name != "firefox" {
		t.Errorf("category match name = %q, want firefox", name)
	}

	if err := db.QueryRow(
		`SELECT name FROM package_fts WHERE package_fts MATCH 'TerminalEmulator'`).Scan(&name); err != nil {
		t.Fatalf("expected konsole via category match: %v", err)
	}
	if name != "konsole" {
		t.Errorf("category match name = %q, want konsole", name)
	}
}

func TestApplyTerms_ClearsStalePriorData(t *testing.T) {
	db := setupPackageDB(t)
	ctx := context.Background()

	// Populate firefox with prior-run AppStream data.
	first := map[string]*pkgTerms{
		"firefox": {keywords: []string{"obsolete"}, categories: []string{"OldCategory"}},
	}
	if _, err := applyTerms(ctx, db, first); err != nil {
		t.Fatal(err)
	}

	// Second run no longer mentions firefox (upstream dropped the component).
	second := map[string]*pkgTerms{
		"konsole": {keywords: []string{"shell"}, categories: []string{"System"}},
	}
	if _, err := applyTerms(ctx, db, second); err != nil {
		t.Fatal(err)
	}

	var kw, cat string
	if err := db.QueryRow(`SELECT keywords, categories FROM package WHERE name = 'firefox'`).
		Scan(&kw, &cat); err != nil {
		t.Fatal(err)
	}
	if kw != "" || cat != "" {
		t.Errorf("firefox should be cleared on second run, got kw=%q cat=%q", kw, cat)
	}

	// And FTS should no longer match the stale term.
	err := db.QueryRow(
		`SELECT name FROM package_fts WHERE package_fts MATCH 'OldCategory'`).Scan(new(string))
	if err != sql.ErrNoRows {
		t.Errorf("stale category still matches in FTS: err=%v", err)
	}
}

func TestApplyTerms_DedupesAndStripsStopwords(t *testing.T) {
	db := setupPackageDB(t)
	ctx := context.Background()

	// Duplicate tokens across multiple "components" + a stopword mixed in.
	acc := map[string]*pkgTerms{
		"firefox": {
			keywords:   []string{"internet and www", "www browser"},
			categories: []string{"Network", "Network"},
		},
	}
	if _, err := applyTerms(ctx, db, acc); err != nil {
		t.Fatal(err)
	}

	var kw, cat string
	if err := db.QueryRow(`SELECT keywords, categories FROM package WHERE name = 'firefox'`).
		Scan(&kw, &cat); err != nil {
		t.Fatal(err)
	}
	if kw != "internet www browser" {
		t.Errorf("keywords = %q, want %q", kw, "internet www browser")
	}
	if cat != "Network" {
		t.Errorf("categories = %q, want %q", cat, "Network")
	}
}

func TestApplyTerms_SkipsEmptyAfterDedupe(t *testing.T) {
	db := setupPackageDB(t)
	ctx := context.Background()

	// All-stopword keywords → dedupeWords returns ""; no row should be updated.
	acc := map[string]*pkgTerms{
		"firefox": {keywords: []string{"the and or"}, categories: nil},
	}
	updated, err := applyTerms(ctx, db, acc)
	if err != nil {
		t.Fatal(err)
	}
	if updated != 0 {
		t.Errorf("updated = %d, want 0 (all-stopword input)", updated)
	}
}
