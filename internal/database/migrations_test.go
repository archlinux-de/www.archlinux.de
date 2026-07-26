package database

import (
	"database/sql"
	"testing"
)

func TestInitialSchemaRequiresPackageArchitecture(t *testing.T) {
	db, err := New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	var notNull int
	var defaultValue sql.NullString
	if err := db.QueryRow(`SELECT "notnull", dflt_value FROM pragma_table_info('package') WHERE name = 'architecture'`).Scan(&notNull, &defaultValue); err != nil {
		t.Fatal(err)
	}
	if notNull != 1 || defaultValue.Valid {
		t.Errorf("architecture schema = notnull:%d default:%q, want NOT NULL with no default", notNull, defaultValue.String)
	}

	if _, err := db.Exec(`INSERT INTO repository (id, name, architecture) VALUES (1, 'core', 'x86_64')`); err != nil {
		t.Fatal(err)
	}
	if _, err := db.Exec(`INSERT INTO package (id, repository_id, name, base, version) VALUES (1, 1, 'archiso', 'archiso', '78-1')`); err == nil {
		t.Error("package insert without architecture succeeded")
	}
	if _, err := db.Exec(`INSERT INTO package (id, repository_id, name, base, version, architecture) VALUES (1, 1, 'archiso', 'archiso', '78-1', 'any')`); err != nil {
		t.Fatal(err)
	}
}
