package news

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"strings"
	"time"

	"www/internal/sanitize"
)

const (
	flarumURL = "https://forum.archlinux.de"
	flarumTag = "neuigkeiten"
)

type flarumResponse struct {
	Data     []flarumDiscussion `json:"data"`
	Included []flarumIncluded   `json:"included"`
}

type flarumDiscussion struct {
	ID         string `json:"id"`
	Attributes struct {
		Title     string `json:"title"`
		Slug      string `json:"slug"`
		CreatedAt string `json:"createdAt"`
	} `json:"attributes"`
	Relationships struct {
		FirstPost struct {
			Data struct {
				ID string `json:"id"`
			} `json:"data"`
		} `json:"firstPost"`
		User struct {
			Data *struct {
				ID string `json:"id"`
			} `json:"data"`
		} `json:"user"`
	} `json:"relationships"`
}

type flarumIncluded struct {
	Type       string `json:"type"`
	ID         string `json:"id"`
	Attributes struct {
		ContentHTML string `json:"contentHtml"`
		DisplayName string `json:"displayName"`
		Slug        string `json:"slug"`
	} `json:"attributes"`
}

type newsItem struct {
	ID           int
	Title        string
	Link         string
	Description  string
	AuthorName   string
	AuthorLink   string
	LastModified int64
}

func Update(ctx context.Context, db *sql.DB) error {
	items, err := fetchNews(ctx)
	if err != nil {
		return fmt.Errorf("fetch news: %w", err)
	}

	slog.Info("fetched news items", "count", len(items))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO news_item (id, title, link, description, author_name, author_link, last_modified)
		 VALUES (?, ?, ?, ?, ?, ?, ?)
		 ON CONFLICT (id) DO UPDATE SET
		   title = excluded.title, link = excluded.link, description = excluded.description,
		   author_name = excluded.author_name, author_link = excluded.author_link,
		   last_modified = excluded.last_modified`)
	if err != nil {
		return err
	}
	defer func() { _ = stmt.Close() }()

	var ids []any
	for _, item := range items {
		item.Description = sanitize.HTML(item.Description)
		if _, err := stmt.ExecContext(ctx,
			item.ID, item.Title, item.Link, item.Description,
			item.AuthorName, item.AuthorLink, item.LastModified,
		); err != nil {
			return err
		}
		ids = append(ids, item.ID)
	}

	if len(ids) > 0 {
		placeholders := strings.Repeat("?,", len(ids))
		placeholders = placeholders[:len(placeholders)-1]
		if _, err := tx.ExecContext(ctx,
			fmt.Sprintf("DELETE FROM news_item WHERE id NOT IN (%s)", placeholders),
			ids...); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func fetchNews(ctx context.Context) ([]newsItem, error) {
	var items []newsItem
	offset := 0
	limit := 50

	for {
		url := fmt.Sprintf("%s/api/discussions?include=user,firstPost&filter[tag]=%s&page[offset]=%d&page[limit]=%d",
			flarumURL, flarumTag, offset, limit)

		req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
		if err != nil {
			return nil, err
		}
		req.Header.Set("Accept", "application/json")

		resp, err := http.DefaultClient.Do(req)
		if err != nil {
			return nil, err
		}
		defer func() { _ = resp.Body.Close() }()

		if resp.StatusCode != http.StatusOK {
			return nil, fmt.Errorf("fetch news: status %d", resp.StatusCode)
		}

		var flarum flarumResponse
		if err := json.NewDecoder(resp.Body).Decode(&flarum); err != nil {
			return nil, fmt.Errorf("decode flarum response: %w", err)
		}

		for _, d := range flarum.Data {
			item := newsItem{
				Title: d.Attributes.Title,
				Link:  fmt.Sprintf("%s/d/%s", flarumURL, d.Attributes.Slug),
			}

			if _, err := fmt.Sscanf(d.ID, "%d", &item.ID); err != nil {
				continue
			}

			if post := findIncluded(flarum.Included, "posts", d.Relationships.FirstPost.Data.ID); post != nil {
				item.Description = post.Attributes.ContentHTML
			}

			if d.Relationships.User.Data != nil {
				if user := findIncluded(flarum.Included, "users", d.Relationships.User.Data.ID); user != nil {
					item.AuthorName = user.Attributes.DisplayName
					if user.Attributes.Slug != "" {
						item.AuthorLink = fmt.Sprintf("%s/u/%s", flarumURL, user.Attributes.Slug)
					}
				}
			} else {
				item.AuthorName = "[gelöscht]"
			}

			if t, err := time.Parse(time.RFC3339, d.Attributes.CreatedAt); err == nil {
				item.LastModified = t.Unix()
			}

			items = append(items, item)
		}

		if len(flarum.Data) < limit {
			break
		}
		offset += len(flarum.Data)
	}

	return items, nil
}

func findIncluded(included []flarumIncluded, typ, id string) *flarumIncluded {
	for i := range included {
		if included[i].Type == typ && included[i].ID == id {
			return &included[i]
		}
	}
	return nil
}
