package popularity

import (
	"context"
	"testing"

	"archded/internal/database"
)

func TestUpdatePackagesSkipsTesting(t *testing.T) {
	db, err := database.New(":memory:")
	if err != nil {
		t.Fatal(err)
	}
	defer func() { _ = db.Close() }()

	for _, stmt := range []string{
		`INSERT INTO repository (id, name, architecture, testing) VALUES
			(1, 'core', 'x86_64', 0),
			(2, 'core-testing', 'x86_64', 1)`,
		`INSERT INTO package (id, repository_id, name, base, version) VALUES
			(1, 1, 'linux', 'linux', '6.6.7-1'),
			(2, 2, 'linux', 'linux', '6.7-rc1')`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatal(err)
		}
	}

	resetSQL := `UPDATE package SET popularity_recent = 0, popularity_count = 0, popularity_samples = 0
		 WHERE repository_id IN (SELECT id FROM repository WHERE testing = 0)`
	updateSQL := `UPDATE package SET popularity_recent = ?, popularity_count = ?, popularity_samples = ?
		 WHERE name = ? AND repository_id IN (SELECT id FROM repository WHERE testing = 0)`

	items := []popularityItem{
		{Key: "linux", Popularity: 75.0, Count: 500, Samples: 1000},
	}

	if err := applyUpdates(context.Background(), db, resetSQL, updateSQL, items); err != nil {
		t.Fatal(err)
	}

	// Stable package should have popularity
	var stablePop float64
	if err := db.QueryRow(`SELECT popularity_recent FROM package WHERE id = 1`).Scan(&stablePop); err != nil {
		t.Fatal(err)
	}
	if stablePop != 75.0 {
		t.Errorf("stable package: expected popularity 75.0, got %v", stablePop)
	}

	// Testing package should remain at 0
	var testingPop float64
	if err := db.QueryRow(`SELECT popularity_recent FROM package WHERE id = 2`).Scan(&testingPop); err != nil {
		t.Fatal(err)
	}
	if testingPop != 0 {
		t.Errorf("testing package: expected popularity 0, got %v", testingPop)
	}
}
