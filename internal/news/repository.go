package news

import (
	"context"
	"database/sql"
)

type NewsItem struct {
	ID           int
	Title        string
	Link         string
	Description  string
	AuthorName   string
	AuthorLink   string
	LastModified int64
}

func (n NewsItem) URL() string {
	return newsURL(n.ID, n.Title)
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) Search(ctx context.Context, search string, limit, offset int) ([]NewsItem, int, error) {
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		searchArg := "%" + search + "%"
		countQuery = `SELECT COUNT(*) FROM news_item WHERE title LIKE ? OR description LIKE ?`
		countArgs = []any{searchArg, searchArg}

		dataQuery = `SELECT id, title, link, description, author_name, author_link, last_modified
			FROM news_item WHERE title LIKE ? OR description LIKE ?
			ORDER BY last_modified DESC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, limit, offset}
	} else {
		countQuery = `SELECT COUNT(*) FROM news_item`
		dataQuery = `SELECT id, title, link, description, author_name, author_link, last_modified
			FROM news_item ORDER BY last_modified DESC LIMIT ? OFFSET ?`
		dataArgs = []any{limit, offset}
	}

	var total int
	if err := r.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&total); err != nil {
		return nil, 0, err
	}

	rows, err := r.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		return nil, 0, err
	}
	defer func() { _ = rows.Close() }()

	var items []NewsItem
	for rows.Next() {
		var item NewsItem
		if err := rows.Scan(&item.ID, &item.Title, &item.Link, &item.Description, &item.AuthorName, &item.AuthorLink, &item.LastModified); err != nil {
			return nil, 0, err
		}
		items = append(items, item)
	}

	return items, total, rows.Err()
}

func (r *Repository) FindByID(ctx context.Context, id int) (NewsItem, error) {
	var item NewsItem
	err := r.db.QueryRowContext(ctx,
		`SELECT id, title, link, description, author_name, author_link, last_modified
		 FROM news_item WHERE id = ?`, id).Scan(
		&item.ID, &item.Title, &item.Link, &item.Description,
		&item.AuthorName, &item.AuthorLink, &item.LastModified,
	)
	return item, err
}

func (r *Repository) Latest(ctx context.Context, limit int) ([]NewsItem, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT id, title, link, description, author_name, author_link, last_modified
		 FROM news_item ORDER BY last_modified DESC LIMIT ?`, limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var items []NewsItem
	for rows.Next() {
		var item NewsItem
		if err := rows.Scan(&item.ID, &item.Title, &item.Link, &item.Description, &item.AuthorName, &item.AuthorLink, &item.LastModified); err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, rows.Err()
}

type NewsRef struct {
	ID           int
	Title        string
	LastModified int64
}

func (n NewsRef) URL() string {
	return newsURL(n.ID, n.Title)
}

func (r *Repository) AllRefs(ctx context.Context) ([]NewsRef, error) {
	rows, err := r.db.QueryContext(ctx, `SELECT id, title, last_modified FROM news_item`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var refs []NewsRef
	for rows.Next() {
		var ref NewsRef
		if err := rows.Scan(&ref.ID, &ref.Title, &ref.LastModified); err != nil {
			return nil, err
		}
		refs = append(refs, ref)
	}
	return refs, rows.Err()
}
