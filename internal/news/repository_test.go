package news

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
		`CREATE TABLE news_item (
			id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE,
			description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '',
			author_link TEXT, last_modified INTEGER NOT NULL)`,
		`INSERT INTO news_item (id, title, link, description, author_name, author_link, last_modified) VALUES
			(1, 'First News', 'https://example.com/1', '<p>First</p>', 'Alice', 'https://alice.example.com', 1700000000),
			(2, 'Second News', 'https://example.com/2', '<p>Second</p>', 'Bob', NULL, 1700100000),
			(3, 'Special: Arch Update', 'https://example.com/3', '<p>Update</p>', 'Alice', NULL, 1700200000)`,
	} {
		if _, err := db.Exec(stmt); err != nil {
			t.Fatalf("setup: %v", err)
		}
	}
	return db
}

func TestSearch_NoFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	items, total, err := repo.Search(context.Background(), "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 {
		t.Errorf("expected 3 total, got %d", total)
	}
	if len(items) != 3 {
		t.Errorf("expected 3 items, got %d", len(items))
	}
	// Should be ordered by last_modified DESC
	if items[0].Title != "Special: Arch Update" {
		t.Errorf("expected most recent first, got %q", items[0].Title)
	}
}

func TestSearch_WithFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	items, total, err := repo.Search(context.Background(), "Special", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1, got %d", total)
	}
	if items[0].Title != "Special: Arch Update" {
		t.Errorf("expected matching item, got %q", items[0].Title)
	}
}

func TestSearch_Pagination(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	items, total, err := repo.Search(context.Background(), "", 2, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 3 {
		t.Errorf("expected total 3, got %d", total)
	}
	if len(items) != 2 {
		t.Errorf("expected 2 items, got %d", len(items))
	}

	items2, _, err := repo.Search(context.Background(), "", 2, 2)
	if err != nil {
		t.Fatal(err)
	}
	if len(items2) != 1 {
		t.Errorf("expected 1 item on second page, got %d", len(items2))
	}
}

func TestSearch_EmptyDB(t *testing.T) {
	db, err := sql.Open("sqlite", ":memory:")
	if err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() { _ = db.Close() })
	_, _ = db.Exec(`CREATE TABLE news_item (
		id INTEGER PRIMARY KEY, title TEXT NOT NULL, link TEXT NOT NULL UNIQUE,
		description TEXT NOT NULL DEFAULT '', author_name TEXT NOT NULL DEFAULT '',
		author_link TEXT, last_modified INTEGER NOT NULL)`)

	repo := NewRepository(db)
	items, total, err := repo.Search(context.Background(), "", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 0 || len(items) != 0 {
		t.Errorf("expected 0 items, got %d total, %d items", total, len(items))
	}
}

func TestFindByID(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	item, err := repo.FindByID(context.Background(), 1)
	if err != nil {
		t.Fatal(err)
	}
	if item.Title != "First News" {
		t.Errorf("expected 'First News', got %q", item.Title)
	}
	if item.AuthorLink != "https://alice.example.com" {
		t.Errorf("expected author link, got %q", item.AuthorLink)
	}
}

func TestFindByID_NotFound(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	_, err := repo.FindByID(context.Background(), 999)
	if err == nil {
		t.Error("expected error for missing ID")
	}
}

func TestFindByID_NullAuthorLink(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	item, err := repo.FindByID(context.Background(), 2)
	if err != nil {
		t.Fatal(err)
	}
	if item.AuthorLink != "" {
		t.Errorf("expected empty author link for NULL, got %q", item.AuthorLink)
	}
}

func TestLatest(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	items, err := repo.Latest(context.Background(), 2)
	if err != nil {
		t.Fatal(err)
	}
	if len(items) != 2 {
		t.Fatalf("expected 2 items, got %d", len(items))
	}
	if items[0].Title != "Special: Arch Update" {
		t.Errorf("expected most recent first, got %q", items[0].Title)
	}
}

func TestAllRefs(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	refs, err := repo.AllRefs(context.Background())
	if err != nil {
		t.Fatal(err)
	}
	if len(refs) != 3 {
		t.Errorf("expected 3 refs, got %d", len(refs))
	}
}

func TestSearch_DescriptionFilter(t *testing.T) {
	repo := NewRepository(setupTestDB(t))
	items, total, err := repo.Search(context.Background(), "Update", 10, 0)
	if err != nil {
		t.Fatal(err)
	}
	if total != 1 {
		t.Errorf("expected 1 (match in description), got %d", total)
	}
	if items[0].ID != 3 {
		t.Errorf("expected ID 3, got %d", items[0].ID)
	}
}
