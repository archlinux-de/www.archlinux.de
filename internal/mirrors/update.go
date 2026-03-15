package mirrors

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"time"
)

const mirrorStatusURL = "https://archlinux.org/mirrors/status/json/"

type mirrorStatusResponse struct {
	URLs []mirrorJSON `json:"urls"`
}

type mirrorJSON struct {
	URL            string   `json:"url"`
	Protocol       string   `json:"protocol"`
	CountryCode    string   `json:"country_code"`
	LastSync       *string  `json:"last_sync"`
	Delay          *int     `json:"delay"`
	DurationAvg    *float64 `json:"duration_avg"`
	DurationStddev *float64 `json:"duration_stddev"`
	Score          *float64 `json:"score"`
	CompletionPct  *float64 `json:"completion_pct"`
	Active         bool     `json:"active"`
	Isos           bool     `json:"isos"`
	IPv4           bool     `json:"ipv4"`
	IPv6           bool     `json:"ipv6"`
}

func Update(ctx context.Context, db *sql.DB) error {
	mirrors, err := fetchMirrors(ctx)
	if err != nil {
		return fmt.Errorf("fetch mirrors: %w", err)
	}

	slog.Info("fetched mirrors", "count", len(mirrors))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `DELETE FROM mirror`); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO mirror (url, country_code, last_sync, delay, duration_avg, duration_stddev, score, completion_pct, ipv4, ipv6)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
	if err != nil {
		return err
	}
	defer stmt.Close()

	for _, m := range mirrors {
		var lastSync *int64
		if m.LastSync != nil {
			if t, err := time.Parse(time.RFC3339, *m.LastSync); err == nil {
				unix := t.Unix()
				lastSync = &unix
			}
		}

		var countryCode *string
		if m.CountryCode != "" {
			countryCode = &m.CountryCode
		}

		if _, err := stmt.ExecContext(ctx,
			m.URL, countryCode, lastSync, m.Delay,
			m.DurationAvg, m.DurationStddev, m.Score, m.CompletionPct,
			m.IPv4, m.IPv6,
		); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func fetchMirrors(ctx context.Context) ([]mirrorJSON, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, mirrorStatusURL, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Accept", "application/json")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var status mirrorStatusResponse
	if err := json.NewDecoder(resp.Body).Decode(&status); err != nil {
		return nil, fmt.Errorf("decode mirror status: %w", err)
	}

	var filtered []mirrorJSON
	for _, m := range status.URLs {
		if !m.Active || m.Protocol != "https" || !m.Isos {
			continue
		}
		if m.Score == nil || m.Delay == nil || m.DurationAvg == nil || m.DurationStddev == nil || m.LastSync == nil {
			continue
		}
		filtered = append(filtered, m)
	}

	return filtered, nil
}
