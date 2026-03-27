package planet

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"
	"sync"
	"time"

	"archded/internal/sanitize"
)

const fetchTimeout = 30 * time.Second

type fetchResult struct {
	url  string
	feed ParsedFeed
	err  error
}

func Update(ctx context.Context, db *sql.DB) error {
	results := fetchAllFeeds(ctx, FeedURLs)

	var feeds []fetchResult
	for _, r := range results {
		if r.err != nil {
			slog.Warn("failed to fetch feed", "url", r.url, "error", r.err)
			continue
		}
		slog.Info("fetched feed", "url", r.url, "items", len(r.feed.Items))
		feeds = append(feeds, r)
	}

	if len(feeds) == 0 {
		return errors.New("no feeds fetched successfully")
	}

	return syncToDatabase(ctx, db, feeds)
}

func fetchAllFeeds(ctx context.Context, urls []string) []fetchResult {
	results := make([]fetchResult, len(urls))
	var wg sync.WaitGroup

	for i, u := range urls {
		wg.Add(1)
		go func(idx int, feedURL string) {
			defer wg.Done()
			results[idx] = fetchResult{url: feedURL}

			feed, err := fetchFeed(ctx, feedURL)
			results[idx].feed = feed
			results[idx].err = err
		}(i, u)
	}

	wg.Wait()
	return results
}

const maxFeedSize = 10 << 20 // 10 MB

func fetchFeed(ctx context.Context, feedURL string) (ParsedFeed, error) {
	ctx, cancel := context.WithTimeout(ctx, fetchTimeout)
	defer cancel()

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, feedURL, nil)
	if err != nil {
		return ParsedFeed{}, err
	}
	req.Header.Set("User-Agent", "planet.archlinux.de/1.0")
	req.Header.Set("Accept", "application/atom+xml, application/rss+xml, application/xml, text/xml")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return ParsedFeed{}, err
	}
	defer func() { _ = resp.Body.Close() }()

	if resp.StatusCode != http.StatusOK {
		return ParsedFeed{}, fmt.Errorf("status %d", resp.StatusCode)
	}

	return ParseFeed(io.LimitReader(resp.Body, maxFeedSize))
}

func syncToDatabase(ctx context.Context, db *sql.DB, results []fetchResult) error {
	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	feedStmt, err := tx.PrepareContext(ctx,
		`INSERT INTO planet_feed (url, title, description, link, last_modified)
		 VALUES (?, ?, ?, ?, ?)
		 ON CONFLICT (url) DO UPDATE SET
		   title = excluded.title, description = excluded.description,
		   link = excluded.link, last_modified = excluded.last_modified`)
	if err != nil {
		return err
	}
	defer func() { _ = feedStmt.Close() }()

	itemStmt, err := tx.PrepareContext(ctx,
		`INSERT INTO planet_item (link, feed_url, title, description, author_name, author_uri, last_modified)
		 VALUES (?, ?, ?, ?, ?, ?, ?)
		 ON CONFLICT (link) DO UPDATE SET
		   feed_url = excluded.feed_url, title = excluded.title, description = excluded.description,
		   author_name = excluded.author_name, author_uri = excluded.author_uri,
		   last_modified = excluded.last_modified`)
	if err != nil {
		return err
	}
	defer func() { _ = itemStmt.Close() }()

	var feedURLs []any

	for _, r := range results {
		f := r.feed
		feedURLs = append(feedURLs, r.url)

		var feedLastModified int64
		for _, item := range f.Items {
			if unix := item.LastModified.Unix(); unix > feedLastModified {
				feedLastModified = unix
			}
		}

		if _, err := feedStmt.ExecContext(ctx,
			r.url, f.Title, f.Description, f.Link, feedLastModified,
		); err != nil {
			return err
		}

		// Only delete stale items for feeds that returned items;
		// skip feeds with zero items to avoid wiping data on transient failures.
		if len(f.Items) == 0 {
			continue
		}

		var itemLinks []any
		for _, item := range f.Items {
			itemLinks = append(itemLinks, item.Link)
			description := sanitize.HTML(item.Description)

			authorName := item.AuthorName
			if authorName == "" {
				authorName = f.Title
			}

			if _, err := itemStmt.ExecContext(ctx,
				item.Link, r.url, item.Title, description,
				authorName, item.AuthorURI, item.LastModified.Unix(),
			); err != nil {
				return err
			}
		}

		placeholders := strings.Repeat("?,", len(itemLinks))
		placeholders = placeholders[:len(placeholders)-1]
		args := make([]any, 0, len(itemLinks)+1)
		args = append(args, itemLinks...)
		args = append(args, r.url)
		if _, err := tx.ExecContext(ctx,
			fmt.Sprintf("DELETE FROM planet_item WHERE link NOT IN (%s) AND feed_url = ?", placeholders),
			args...); err != nil {
			return err
		}
	}

	// Delete feeds no longer in the list (CASCADE deletes their items)
	placeholders := strings.Repeat("?,", len(feedURLs))
	placeholders = placeholders[:len(placeholders)-1]
	if _, err := tx.ExecContext(ctx,
		fmt.Sprintf("DELETE FROM planet_feed WHERE url NOT IN (%s)", placeholders),
		feedURLs...); err != nil {
		return err
	}

	return tx.Commit()
}
