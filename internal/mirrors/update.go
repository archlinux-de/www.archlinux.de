package mirrors

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"log/slog"
	"net/http"
	"strings"
	"time"

	"archded/internal/sanitize"
)

const mirrorStatusURL = "https://archlinux.org/mirrors/status/json/"

type mirrorStatusResponse struct {
	URLs []mirrorJSON `json:"urls"`
}

type mirrorJSON struct {
	URL            string   `json:"url"`
	Protocol       string   `json:"protocol"`
	Country        string   `json:"country"`
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
	if len(mirrors) == 0 {
		return errors.New("fetch mirrors: empty response, aborting to prevent data loss")
	}

	slog.Info("fetched mirrors", "count", len(mirrors))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO mirror (url, country_code, country_name, last_sync, delay, duration_avg, duration_stddev, score, completion_pct, ipv4, ipv6)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		 ON CONFLICT (url) DO UPDATE SET
		   country_code = excluded.country_code, country_name = excluded.country_name,
		   last_sync = excluded.last_sync, delay = excluded.delay,
		   duration_avg = excluded.duration_avg, duration_stddev = excluded.duration_stddev,
		   score = excluded.score, completion_pct = excluded.completion_pct,
		   ipv4 = excluded.ipv4, ipv6 = excluded.ipv6`)
	if err != nil {
		return err
	}
	defer func() { _ = stmt.Close() }()

	var urls []any
	for _, m := range mirrors {
		if !sanitize.IsValidURL(m.URL, "https") {
			slog.Warn("skipping mirror with invalid URL", "url", m.URL)
			continue
		}

		var lastSync int64
		if m.LastSync != nil {
			if t, err := time.Parse(time.RFC3339, *m.LastSync); err == nil {
				lastSync = t.Unix()
			}
		}

		if _, err := stmt.ExecContext(ctx,
			m.URL, m.CountryCode, m.Country,
			lastSync, derefOrZero(m.Delay),
			derefOrZero(m.DurationAvg), derefOrZero(m.DurationStddev), derefOrZero(m.Score), derefOrZero(m.CompletionPct),
			m.IPv4, m.IPv6,
		); err != nil {
			return err
		}
		urls = append(urls, m.URL)
	}

	if len(urls) > 0 {
		placeholders := strings.Repeat("?,", len(urls))
		placeholders = placeholders[:len(placeholders)-1]
		if _, err := tx.ExecContext(ctx,
			fmt.Sprintf("DELETE FROM mirror WHERE url NOT IN (%s)", placeholders),
			urls...); err != nil {
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
	defer func() { _ = resp.Body.Close() }()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("fetch mirror status: status %d", resp.StatusCode)
	}

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

func derefOrZero[T any](p *T) T {
	if p != nil {
		return *p
	}
	var zero T
	return zero
}
