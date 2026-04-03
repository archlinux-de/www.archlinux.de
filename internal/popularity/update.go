package popularity

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net/http"
)

const (
	packageStatsURL = "https://pkgstats.archlinux.de/api/packages"
	mirrorStatsURL  = "https://pkgstats.archlinux.de/api/mirrors"
	fetchLimit      = 10000
)

type popularityItem struct {
	Key        string
	Popularity float64
	Count      int
	Samples    int
}

func UpdatePackages(ctx context.Context, db *sql.DB) error {
	return updatePopularities(ctx, db, packageStatsURL, "packagePopularities",
		`UPDATE package SET popularity_recent = 0, popularity_count = 0, popularity_samples = 0
		 WHERE repository_id IN (SELECT id FROM repository WHERE testing = 0)`,
		`UPDATE package SET popularity_recent = ?, popularity_count = ?, popularity_samples = ?
		 WHERE name = ? AND repository_id IN (SELECT id FROM repository WHERE testing = 0)`,
	)
}

func UpdateMirrors(ctx context.Context, db *sql.DB) error {
	return updatePopularities(ctx, db, mirrorStatsURL, "mirrorPopularities",
		`UPDATE mirror SET popularity_recent = 0, popularity_count = 0, popularity_samples = 0`,
		`UPDATE mirror SET popularity_recent = ?, popularity_count = ?, popularity_samples = ? WHERE url = ?`,
	)
}

func updatePopularities(ctx context.Context, db *sql.DB, baseURL, jsonKey, resetSQL, updateSQL string) error {
	items, err := fetchAll(ctx, baseURL, func(data []byte) ([]popularityItem, int, error) {
		var raw map[string]json.RawMessage
		if err := json.Unmarshal(data, &raw); err != nil {
			return nil, 0, err
		}

		var entries []struct {
			Key        string  `json:"name"`
			URL        string  `json:"url"`
			Popularity float64 `json:"popularity"`
			Count      int     `json:"count"`
			Samples    int     `json:"samples"`
		}
		if err := json.Unmarshal(raw[jsonKey], &entries); err != nil {
			return nil, 0, err
		}

		items := make([]popularityItem, len(entries))
		for i, e := range entries {
			key := e.Key
			if key == "" {
				key = e.URL
			}
			items[i] = popularityItem{key, e.Popularity, e.Count, e.Samples}
		}
		return items, len(entries), nil
	})
	if err != nil {
		return err
	}

	slog.Info("fetched popularities", "source", jsonKey, "count", len(items))

	return applyUpdates(ctx, db, resetSQL, updateSQL, items)
}

func fetchAll(ctx context.Context, baseURL string, parse func([]byte) ([]popularityItem, int, error)) ([]popularityItem, error) {
	var all []popularityItem
	offset := 0

	for {
		url := fmt.Sprintf("%s?offset=%d&limit=%d", baseURL, offset, fetchLimit)
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
		if err != nil {
			return nil, err
		}
		req.Header.Set("Accept", "application/json")
		req.Header.Set("User-Agent", "archded/1.0 (+https://www.archlinux.de)")

		resp, err := http.DefaultClient.Do(req)
		if err != nil {
			return nil, err
		}

		if resp.StatusCode != http.StatusOK {
			_ = resp.Body.Close()
			return nil, fmt.Errorf("fetch %s: status %d", baseURL, resp.StatusCode)
		}

		body, err := io.ReadAll(resp.Body)
		_ = resp.Body.Close()
		if err != nil {
			return nil, err
		}

		items, count, err := parse(body)
		if err != nil {
			return nil, err
		}

		all = append(all, items...)
		if count < fetchLimit {
			break
		}
		offset += count
	}

	return all, nil
}

func applyUpdates(ctx context.Context, db *sql.DB, resetSQL, updateSQL string, items []popularityItem) error {
	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, resetSQL); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx, updateSQL)
	if err != nil {
		return err
	}
	defer func() { _ = stmt.Close() }()

	for _, item := range items {
		if _, err := stmt.ExecContext(ctx, item.Popularity, item.Count, item.Samples, item.Key); err != nil {
			return err
		}
	}

	return tx.Commit()
}
