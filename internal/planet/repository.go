package planet

import (
	"context"
	"database/sql"
)

type Feed struct {
	URL          string
	Title        string
	Description  string
	Link         string
	LastModified int64
}

type Item struct {
	Link             string
	FeedURL          string
	Title            string
	Description      string
	AuthorName       string
	AuthorURI        string
	LastModified     int64
	FeedTitle        string
	FeedLink         string
	FeedLastModified int64
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) LatestItems(ctx context.Context, limit int) ([]Item, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT i.link, i.feed_url, i.title, i.description, i.author_name, i.author_uri, i.last_modified,
		        f.title, f.link, f.last_modified
		 FROM planet_item i
		 JOIN planet_feed f ON f.url = i.feed_url
		 ORDER BY i.last_modified DESC
		 LIMIT ?`, limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var items []Item
	for rows.Next() {
		var item Item
		if err := rows.Scan(
			&item.Link, &item.FeedURL, &item.Title, &item.Description,
			&item.AuthorName, &item.AuthorURI, &item.LastModified,
			&item.FeedTitle, &item.FeedLink, &item.FeedLastModified,
		); err != nil {
			return nil, err
		}
		items = append(items, item)
	}
	return items, rows.Err()
}
