package popularity

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
)

const (
	packageStatsURL = "https://pkgstats.archlinux.de/api/packages"
	mirrorStatsURL  = "https://pkgstats.archlinux.de/api/mirrors"
	fetchLimit      = 10000
)

type packagePopularityResponse struct {
	PackagePopularities []packagePopularity `json:"packagePopularities"`
}

type packagePopularity struct {
	Name       string  `json:"name"`
	Popularity float64 `json:"popularity"`
	Count      int     `json:"count"`
}

type mirrorPopularityResponse struct {
	MirrorPopularities []mirrorPopularity `json:"mirrorPopularities"`
}

type mirrorPopularity struct {
	URL        string  `json:"url"`
	Popularity float64 `json:"popularity"`
	Count      int     `json:"count"`
}

func UpdatePackages(ctx context.Context, db *sql.DB) error {
	var all []packagePopularity
	offset := 0

	for {
		url := fmt.Sprintf("%s?offset=%d&limit=%d", packageStatsURL, offset, fetchLimit)
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
		if err != nil {
			return err
		}
		req.Header.Set("Accept", "application/json")

		resp, err := http.DefaultClient.Do(req)
		if err != nil {
			return err
		}

		var data packagePopularityResponse
		err = json.NewDecoder(resp.Body).Decode(&data)
		resp.Body.Close()
		if err != nil {
			return fmt.Errorf("decode package popularity: %w", err)
		}

		all = append(all, data.PackagePopularities...)
		if len(data.PackagePopularities) < fetchLimit {
			break
		}
		offset += len(data.PackagePopularities)
	}

	slog.Info("fetched package popularities", "count", len(all))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `UPDATE package SET popularity_recent = 0, popularity_total = 0`); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx,
		`UPDATE package SET popularity_recent = ?, popularity_total = ? WHERE name = ?`)
	if err != nil {
		return err
	}
	defer stmt.Close()

	for _, p := range all {
		if _, err := stmt.ExecContext(ctx, p.Popularity, p.Count, p.Name); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func UpdateMirrors(ctx context.Context, db *sql.DB) error {
	var all []mirrorPopularity
	offset := 0

	for {
		url := fmt.Sprintf("%s?offset=%d&limit=%d", mirrorStatsURL, offset, fetchLimit)
		req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
		if err != nil {
			return err
		}
		req.Header.Set("Accept", "application/json")

		resp, err := http.DefaultClient.Do(req)
		if err != nil {
			return err
		}

		var data mirrorPopularityResponse
		err = json.NewDecoder(resp.Body).Decode(&data)
		resp.Body.Close()
		if err != nil {
			return fmt.Errorf("decode mirror popularity: %w", err)
		}

		all = append(all, data.MirrorPopularities...)
		if len(data.MirrorPopularities) < fetchLimit {
			break
		}
		offset += len(data.MirrorPopularities)
	}

	slog.Info("fetched mirror popularities", "count", len(all))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `UPDATE mirror SET popularity_recent = 0, popularity_total = 0`); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx,
		`UPDATE mirror SET popularity_recent = ?, popularity_total = ? WHERE url = ?`)
	if err != nil {
		return err
	}
	defer stmt.Close()

	for _, m := range all {
		if _, err := stmt.ExecContext(ctx, m.Popularity, m.Count, m.URL); err != nil {
			return err
		}
	}

	return tx.Commit()
}
