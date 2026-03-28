package packages

import (
	"context"
	"database/sql"
	"testing"

	"archded/internal/pacmandb"

	_ "modernc.org/sqlite"
)

func setupSyncDB(t *testing.T) *sql.DB {
	t.Helper()
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })

	for _, stmt := range []string{
		`CREATE TABLE repository (
			id INTEGER PRIMARY KEY, name TEXT NOT NULL, architecture TEXT NOT NULL,
			testing INTEGER NOT NULL DEFAULT 0, sha256sum TEXT NOT NULL DEFAULT '',
			UNIQUE(name, architecture))`,
		`CREATE TABLE package (
			id INTEGER PRIMARY KEY, repository_id INTEGER NOT NULL REFERENCES repository(id),
			name TEXT NOT NULL, base TEXT NOT NULL, version TEXT NOT NULL,
			description TEXT NOT NULL DEFAULT '', url TEXT NOT NULL DEFAULT '',
			build_date INTEGER NOT NULL DEFAULT 0, compressed_size INTEGER NOT NULL DEFAULT 0,
			installed_size INTEGER NOT NULL DEFAULT 0, packager_name TEXT NOT NULL DEFAULT '',
			packager_email TEXT NOT NULL DEFAULT '',
			popularity_recent REAL NOT NULL DEFAULT 0, popularity_count INTEGER NOT NULL DEFAULT 0,
			popularity_samples INTEGER NOT NULL DEFAULT 0, licenses TEXT NOT NULL DEFAULT '',
			groups TEXT NOT NULL DEFAULT '', provides TEXT NOT NULL DEFAULT '',
			UNIQUE(repository_id, name))`,
		`CREATE VIRTUAL TABLE package_fts USING fts5(
			name, base, description, groups, provides,
			content='package', content_rowid='id')`,
		`CREATE TABLE package_relation (
			id INTEGER PRIMARY KEY,
			package_id INTEGER NOT NULL REFERENCES package(id) ON DELETE CASCADE,
			type TEXT NOT NULL, target_name TEXT NOT NULL,
			target_version TEXT NOT NULL DEFAULT '', version_constraint TEXT NOT NULL DEFAULT '')`,
		`CREATE TABLE files (
			package_id INTEGER PRIMARY KEY REFERENCES package(id) ON DELETE CASCADE,
			file_list TEXT NOT NULL)`,

		`INSERT INTO repository (id, name, architecture) VALUES (1, 'core', 'x86_64')`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %s...: %v", stmt[:40], err)
		}
	}
	return db
}

var coreRepo = repoConfig{Name: "core", Architecture: "x86_64"}

func TestSyncPreservesPopularity(t *testing.T) {
	db := setupSyncDB(t)
	ctx := context.Background()

	initial := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.7-1", Description: "The Linux kernel"},
		{Name: "bash", Base: "bash", Version: "5.2-1", Description: "GNU Bourne Again shell"},
	}
	if err := syncPackages(ctx, db, coreRepo, initial); err != nil {
		t.Fatal(err)
	}

	// Simulate popularity update (as done monthly by update-package-popularities)
	if _, err := db.Exec(`UPDATE package SET popularity_recent = 42.5, popularity_count = 100, popularity_samples = 200 WHERE name = 'linux'`); err != nil {
		t.Fatal(err)
	}
	if _, err := db.Exec(`UPDATE package SET popularity_recent = 30.0, popularity_count = 80, popularity_samples = 200 WHERE name = 'bash'`); err != nil {
		t.Fatal(err)
	}

	// Re-sync with updated version (as done every 5 minutes by update-packages)
	updated := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.8-1", Description: "The Linux kernel"},
		{Name: "bash", Base: "bash", Version: "5.2-1", Description: "GNU Bourne Again shell"},
	}
	if err := syncPackages(ctx, db, coreRepo, updated); err != nil {
		t.Fatal(err)
	}

	var version string
	var popRecent float64
	var popCount, popSamples int
	err := db.QueryRow(`SELECT version, popularity_recent, popularity_count, popularity_samples FROM package WHERE name = 'linux'`).
		Scan(&version, &popRecent, &popCount, &popSamples)
	if err != nil {
		t.Fatal(err)
	}
	if version != "6.6.8-1" {
		t.Errorf("version not updated: got %q", version)
	}
	if popRecent != 42.5 || popCount != 100 || popSamples != 200 {
		t.Errorf("popularity lost: got recent=%v count=%d samples=%d", popRecent, popCount, popSamples)
	}
}

func TestSyncRemovesStalePackages(t *testing.T) {
	db := setupSyncDB(t)
	ctx := context.Background()

	initial := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.7-1"},
		{Name: "bash", Base: "bash", Version: "5.2-1"},
		{Name: "removed-pkg", Base: "removed-pkg", Version: "1.0-1"},
	}
	if err := syncPackages(ctx, db, coreRepo, initial); err != nil {
		t.Fatal(err)
	}

	// Sync again without removed-pkg
	updated := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.7-1"},
		{Name: "bash", Base: "bash", Version: "5.2-1"},
	}
	if err := syncPackages(ctx, db, coreRepo, updated); err != nil {
		t.Fatal(err)
	}

	var count int
	if err := db.QueryRow(`SELECT COUNT(*) FROM package WHERE repository_id = 1`).Scan(&count); err != nil {
		t.Fatal(err)
	}
	if count != 2 {
		t.Errorf("expected 2 packages after removal, got %d", count)
	}

	err := db.QueryRow(`SELECT id FROM package WHERE name = 'removed-pkg'`).Scan(new(int))
	if err != sql.ErrNoRows {
		t.Error("removed-pkg should have been deleted")
	}
}

func TestSyncAddsNewPackagesWithZeroPopularity(t *testing.T) {
	db := setupSyncDB(t)
	ctx := context.Background()

	initial := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.7-1"},
	}
	if err := syncPackages(ctx, db, coreRepo, initial); err != nil {
		t.Fatal(err)
	}

	// Sync with an additional new package
	updated := []pacmandb.Package{
		{Name: "linux", Base: "linux", Version: "6.6.7-1"},
		{Name: "new-pkg", Base: "new-pkg", Version: "1.0-1"},
	}
	if err := syncPackages(ctx, db, coreRepo, updated); err != nil {
		t.Fatal(err)
	}

	var popRecent float64
	var popCount, popSamples int
	err := db.QueryRow(`SELECT popularity_recent, popularity_count, popularity_samples FROM package WHERE name = 'new-pkg'`).
		Scan(&popRecent, &popCount, &popSamples)
	if err != nil {
		t.Fatal(err)
	}
	if popRecent != 0 || popCount != 0 || popSamples != 0 {
		t.Errorf("new package should have zero popularity, got recent=%v count=%d samples=%d", popRecent, popCount, popSamples)
	}
}

func TestSyncConsistentWithUpstream(t *testing.T) {
	db := setupSyncDB(t)
	ctx := context.Background()

	v1 := []pacmandb.Package{
		{
			Name: "linux", Base: "linux", Version: "6.6.7-1", Description: "The Linux kernel",
			URL: "https://kernel.org", BuildDate: 1700300000, CompressedSize: 100, InstalledSize: 200,
			PackagerName: "Jan", PackagerEmail: "jan@example.com",
			Licenses: []string{"GPL2"}, Groups: []string{"base"},
			Relations: []pacmandb.Relation{
				{Type: "depends", TargetName: "glibc"},
				{Type: "provides", TargetName: "linux-api-headers"},
			},
			Files: []string{"/boot/vmlinuz"},
		},
		{Name: "bash", Base: "bash", Version: "5.2-1"},
		{Name: "to-be-removed", Base: "to-be-removed", Version: "1.0-1"},
	}
	if err := syncPackages(ctx, db, coreRepo, v1); err != nil {
		t.Fatal(err)
	}

	// Simulate state after popularity update + time passing
	if _, err := db.Exec(`UPDATE package SET popularity_recent = 50.0 WHERE name = 'linux'`); err != nil {
		t.Fatal(err)
	}

	// v2: linux updated, bash unchanged, to-be-removed gone, new-pkg added
	v2 := []pacmandb.Package{
		{
			Name: "linux", Base: "linux", Version: "6.6.8-1", Description: "The Linux kernel",
			URL: "https://kernel.org", BuildDate: 1700400000, CompressedSize: 110, InstalledSize: 210,
			PackagerName: "Jan", PackagerEmail: "jan@example.com",
			Licenses: []string{"GPL2"}, Groups: []string{"base"},
			Relations: []pacmandb.Relation{
				{Type: "depends", TargetName: "glibc"},
				{Type: "depends", TargetName: "kmod"},
			},
			Files: []string{"/boot/vmlinuz", "/boot/initramfs"},
		},
		{Name: "bash", Base: "bash", Version: "5.2-1"},
		{
			Name: "new-pkg", Base: "new-pkg", Version: "1.0-1",
			Relations: []pacmandb.Relation{{Type: "depends", TargetName: "glibc"}},
		},
	}
	if err := syncPackages(ctx, db, coreRepo, v2); err != nil {
		t.Fatal(err)
	}

	scanRow := func(query string, dest ...any) {
		t.Helper()
		if err := db.QueryRow(query).Scan(dest...); err != nil {
			t.Fatalf("query %q: %v", query[:40], err)
		}
	}

	// Verify exact package count
	var count int
	scanRow(`SELECT COUNT(*) FROM package WHERE repository_id = 1`, &count)
	if count != 3 {
		t.Fatalf("expected 3 packages, got %d", count)
	}

	// Verify removed package is gone
	err := db.QueryRow(`SELECT id FROM package WHERE name = 'to-be-removed'`).Scan(new(int))
	if err != sql.ErrNoRows {
		t.Error("to-be-removed should not exist")
	}

	// Verify linux was updated with correct metadata
	var version, desc string
	var buildDate, compSize, instSize int64
	if err := db.QueryRow(`SELECT version, description, build_date, compressed_size, installed_size FROM package WHERE name = 'linux'`).
		Scan(&version, &desc, &buildDate, &compSize, &instSize); err != nil {
		t.Fatal(err)
	}
	if version != "6.6.8-1" || buildDate != 1700400000 || compSize != 110 || instSize != 210 {
		t.Errorf("linux metadata mismatch: version=%s buildDate=%d compSize=%d instSize=%d", version, buildDate, compSize, instSize)
	}

	// Verify popularity preserved
	var popRecent float64
	scanRow(`SELECT popularity_recent FROM package WHERE name = 'linux'`, &popRecent)
	if popRecent != 50.0 {
		t.Errorf("linux popularity lost: got %v", popRecent)
	}

	// Verify relations match v2 exactly
	var linuxRels, bashRels, newPkgRels int
	scanRow(`SELECT COUNT(*) FROM package_relation WHERE package_id = (SELECT id FROM package WHERE name = 'linux')`, &linuxRels)
	scanRow(`SELECT COUNT(*) FROM package_relation WHERE package_id = (SELECT id FROM package WHERE name = 'bash')`, &bashRels)
	scanRow(`SELECT COUNT(*) FROM package_relation WHERE package_id = (SELECT id FROM package WHERE name = 'new-pkg')`, &newPkgRels)
	if linuxRels != 2 {
		t.Errorf("linux should have 2 relations (old provides removed), got %d", linuxRels)
	}
	if bashRels != 0 {
		t.Errorf("bash should have 0 relations, got %d", bashRels)
	}
	if newPkgRels != 1 {
		t.Errorf("new-pkg should have 1 relation, got %d", newPkgRels)
	}

	// Verify no orphaned relations from removed package
	var orphanRels int
	scanRow(`SELECT COUNT(*) FROM package_relation WHERE package_id NOT IN (SELECT id FROM package)`, &orphanRels)
	if orphanRels != 0 {
		t.Errorf("found %d orphaned relations", orphanRels)
	}

	// Verify files match v2 exactly
	var fileList string
	scanRow(`SELECT file_list FROM files WHERE package_id = (SELECT id FROM package WHERE name = 'linux')`, &fileList)
	if fileList != "/boot/vmlinuz\n/boot/initramfs" {
		t.Errorf("linux files mismatch: %q", fileList)
	}
	var fileCount int
	scanRow(`SELECT COUNT(*) FROM files WHERE package_id IN (SELECT id FROM package WHERE repository_id = 1)`, &fileCount)
	if fileCount != 1 {
		t.Errorf("expected 1 files entry (only linux has files), got %d", fileCount)
	}

	// Verify no orphaned files
	var orphanFiles int
	scanRow(`SELECT COUNT(*) FROM files WHERE package_id NOT IN (SELECT id FROM package)`, &orphanFiles)
	if orphanFiles != 0 {
		t.Errorf("found %d orphaned files", orphanFiles)
	}

	// Rebuild FTS (as Update() does after syncPackages) and verify consistency
	if _, err := db.Exec(`INSERT INTO package_fts(package_fts) VALUES('rebuild')`); err != nil {
		t.Fatal(err)
	}

	var ftsCount int
	scanRow(`SELECT COUNT(*) FROM package_fts`, &ftsCount)
	if ftsCount != 3 {
		t.Errorf("expected 3 FTS entries, got %d", ftsCount)
	}

	// Verify FTS finds the updated package
	var ftsName string
	if err := db.QueryRow(`SELECT name FROM package_fts WHERE package_fts MATCH '"linux"'`).Scan(&ftsName); err != nil {
		t.Fatal(err)
	}
	if ftsName != "linux" {
		t.Errorf("FTS match for linux: got %q", ftsName)
	}

	// Verify removed package is not in FTS
	err = db.QueryRow(`SELECT name FROM package_fts WHERE package_fts MATCH '"to-be-removed"'`).Scan(new(string))
	if err != sql.ErrNoRows {
		t.Error("to-be-removed should not be in FTS index")
	}

	// Verify new package is in FTS
	if err := db.QueryRow(`SELECT name FROM package_fts WHERE package_fts MATCH '"new-pkg"'`).Scan(&ftsName); err != nil {
		t.Fatalf("new-pkg not found in FTS: %v", err)
	}
}

func TestSyncPreservesRelationsAndFiles(t *testing.T) {
	db := setupSyncDB(t)
	ctx := context.Background()

	packages := []pacmandb.Package{
		{
			Name: "linux", Base: "linux", Version: "6.6.7-1",
			Relations: []pacmandb.Relation{
				{Type: "depends", TargetName: "glibc"},
				{Type: "provides", TargetName: "linux-lts"},
			},
			Files: []string{"/boot/vmlinuz-linux", "/usr/lib/modules/6.6.7"},
		},
	}
	if err := syncPackages(ctx, db, coreRepo, packages); err != nil {
		t.Fatal(err)
	}

	// Re-sync with updated relations
	updated := []pacmandb.Package{
		{
			Name: "linux", Base: "linux", Version: "6.6.8-1",
			Relations: []pacmandb.Relation{
				{Type: "depends", TargetName: "glibc"},
				{Type: "depends", TargetName: "kmod"},
				{Type: "provides", TargetName: "linux-lts"},
			},
			Files: []string{"/boot/vmlinuz-linux", "/usr/lib/modules/6.6.8"},
		},
	}
	if err := syncPackages(ctx, db, coreRepo, updated); err != nil {
		t.Fatal(err)
	}

	var relCount int
	if err := db.QueryRow(`SELECT COUNT(*) FROM package_relation WHERE package_id = (SELECT id FROM package WHERE name = 'linux')`).Scan(&relCount); err != nil {
		t.Fatal(err)
	}
	if relCount != 3 {
		t.Errorf("expected 3 relations after update, got %d", relCount)
	}

	var fileList string
	if err := db.QueryRow(`SELECT file_list FROM files WHERE package_id = (SELECT id FROM package WHERE name = 'linux')`).Scan(&fileList); err != nil {
		t.Fatal(err)
	}
	if fileList != "/boot/vmlinuz-linux\n/usr/lib/modules/6.6.8" {
		t.Errorf("unexpected file list: %q", fileList)
	}
}
