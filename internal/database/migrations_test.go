package database

import (
	"database/sql"
	"path/filepath"
	"testing"
)

func TestPackageArchitectureMigration(t *testing.T) {
	dbPath := filepath.Join(t.TempDir(), "migration.db")
	db, err := sql.Open("sqlite", dbPath+"?_pragma=foreign_keys(ON)")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	initial, err := embedMigrations.ReadFile("migrations/000003_initial.up.sql")
	if err != nil {
		t.Fatal(err)
	}
	setup := []string{
		string(initial),
		`CREATE TABLE schema_migrations (version uint64, dirty bool)`,
		`CREATE UNIQUE INDEX version_unique ON schema_migrations (version)`,
		`INSERT INTO schema_migrations (version, dirty) VALUES (3, 0)`,
		`INSERT INTO repository (id, name, architecture, etag) VALUES (1, 'core', 'aarch64', 'old-etag')`,
		`INSERT INTO package (id, repository_id, name, base, version) VALUES (1, 1, 'demo', 'demo', '1-1')`,
		`INSERT INTO package_relation (id, package_id, type, target_name) VALUES (1, 1, 'depends', 'glibc')`,
		`INSERT INTO files (package_id, file_list) VALUES (1, 'usr/bin/demo')`,
		`INSERT INTO package_fts (rowid, name, base, description, groups, provides)
		 SELECT id, name, base, description, groups, provides FROM package`,
	}
	for _, stmt := range setup {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatal(err)
		}
	}

	if err := runMigrations(db); err != nil {
		t.Fatal(err)
	}

	var architecture string
	if err := db.QueryRow(`SELECT architecture FROM package WHERE id = 1`).Scan(&architecture); err != nil {
		t.Fatal(err)
	}
	if architecture != "x86_64" {
		t.Errorf("architecture = %q, want x86_64", architecture)
	}
	assertPackageDataPreserved(t, db)

	var notNull int
	var defaultValue string
	if err := db.QueryRow(`SELECT "notnull", dflt_value FROM pragma_table_info('package') WHERE name = 'architecture'`).Scan(&notNull, &defaultValue); err != nil {
		t.Fatal(err)
	}
	if notNull != 1 {
		t.Error("architecture column is nullable")
	}
	if defaultValue != "'x86_64'" {
		t.Errorf("architecture default = %q, want 'x86_64'", defaultValue)
	}
	var etag string
	if err := db.QueryRow(`SELECT etag FROM repository WHERE id = 1`).Scan(&etag); err != nil {
		t.Fatal(err)
	}
	if etag != "" {
		t.Errorf("repository etag = %q, want empty to force refresh", etag)
	}

	down, err := embedMigrations.ReadFile("migrations/000004_package_architecture.down.sql")
	if err != nil {
		t.Fatal(err)
	}
	tx, err := db.Begin()
	if err != nil {
		t.Fatal(err)
	}
	if _, err := tx.Exec(string(down)); err != nil {
		_ = tx.Rollback()
		t.Fatal(err)
	}
	if err := tx.Commit(); err != nil {
		t.Fatal(err)
	}

	var architectureColumns int
	if err := db.QueryRow(`SELECT count(*) FROM pragma_table_info('package') WHERE name = 'architecture'`).Scan(&architectureColumns); err != nil {
		t.Fatal(err)
	}
	if architectureColumns != 0 {
		t.Error("down migration retained architecture column")
	}
	assertPackageDataPreserved(t, db)
}

func assertPackageDataPreserved(t *testing.T, db *sql.DB) {
	t.Helper()
	for name, query := range map[string]string{
		"relations": `SELECT count(*) FROM package_relation`,
		"files":     `SELECT count(*) FROM files`,
		"search":    `SELECT count(*) FROM package_fts WHERE package_fts MATCH 'demo'`,
	} {
		var count int
		if err := db.QueryRow(query).Scan(&count); err != nil {
			t.Fatalf("%s: %v", name, err)
		}
		if count != 1 {
			t.Errorf("%s count = %d, want 1", name, count)
		}
	}

	var violations int
	if err := db.QueryRow(`SELECT count(*) FROM pragma_foreign_key_check`).Scan(&violations); err != nil {
		t.Fatal(err)
	}
	if violations != 0 {
		t.Errorf("foreign key violations = %d", violations)
	}
}
