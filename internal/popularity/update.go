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
}

func UpdatePackages(ctx context.Context, db *sql.DB) error {
	items, err := fetchAll(ctx, packageStatsURL, func(data []byte) ([]popularityItem, int, error) {
		var resp struct {
			PackagePopularities []struct {
				Name       string  `json:"name"`
				Popularity float64 `json:"popularity"`
				Count      int     `json:"count"`
			} `json:"packagePopularities"`
		}
		if err := json.Unmarshal(data, &resp); err != nil {
			return nil, 0, err
		}
		items := make([]popularityItem, len(resp.PackagePopularities))
		for i, p := range resp.PackagePopularities {
			items[i] = popularityItem{p.Name, p.Popularity, p.Count}
		}
		return items, len(resp.PackagePopularities), nil
	})
	if err != nil {
		return err
	}

	slog.Info("fetched package popularities", "count", len(items))

	return applyUpdates(ctx, db,
		`UPDATE package SET popularity_recent = 0, popularity_total = 0`,
		`UPDATE package SET popularity_recent = ?, popularity_total = ? WHERE name = ?`,
		items,
	)
}

func UpdateMirrors(ctx context.Context, db *sql.DB) error {
	items, err := fetchAll(ctx, mirrorStatsURL, func(data []byte) ([]popularityItem, int, error) {
		var resp struct {
			MirrorPopularities []struct {
				URL        string  `json:"url"`
				Popularity float64 `json:"popularity"`
				Count      int     `json:"count"`
			} `json:"mirrorPopularities"`
		}
		if err := json.Unmarshal(data, &resp); err != nil {
			return nil, 0, err
		}
		items := make([]popularityItem, len(resp.MirrorPopularities))
		for i, m := range resp.MirrorPopularities {
			items[i] = popularityItem{m.URL, m.Popularity, m.Count}
		}
		return items, len(resp.MirrorPopularities), nil
	})
	if err != nil {
		return err
	}

	slog.Info("fetched mirror popularities", "count", len(items))

	return applyUpdates(ctx, db,
		`UPDATE mirror SET popularity_recent = 0, popularity_total = 0`,
		`UPDATE mirror SET popularity_recent = ?, popularity_total = ? WHERE url = ?`,
		items,
	)
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
		if _, err := stmt.ExecContext(ctx, item.Popularity, item.Count, item.Key); err != nil {
			return err
		}
	}

	return tx.Commit()
}
