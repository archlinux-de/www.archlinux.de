package countries

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"strings"
)

const countriesURL = "https://restcountries.com/v3.1/all?fields=cca2,name"

type countryJSON struct {
	CCA2 string `json:"cca2"`
	Name struct {
		Common string `json:"common"`
	} `json:"name"`
}

func Update(ctx context.Context, db *sql.DB) error {
	countries, err := fetchCountries(ctx)
	if err != nil {
		return fmt.Errorf("fetch countries: %w", err)
	}

	slog.Info("fetched countries", "count", len(countries))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO country (code, name) VALUES (?, ?)
		 ON CONFLICT (code) DO UPDATE SET name = excluded.name`)
	if err != nil {
		return err
	}
	defer func() { _ = stmt.Close() }()

	var codes []any
	for _, c := range countries {
		if c.CCA2 == "" || c.Name.Common == "" {
			continue
		}
		if _, err := stmt.ExecContext(ctx, c.CCA2, c.Name.Common); err != nil {
			return err
		}
		codes = append(codes, c.CCA2)
	}

	if len(codes) > 0 {
		placeholders := strings.Repeat("?,", len(codes))
		placeholders = placeholders[:len(placeholders)-1]
		q := fmt.Sprintf("NOT IN (%s)", placeholders)
		if _, err := tx.ExecContext(ctx,
			"UPDATE mirror SET country_code = NULL WHERE country_code "+q, //nolint:gosec // q is placeholders
			codes...); err != nil {
			return err
		}
		if _, err := tx.ExecContext(ctx,
			"DELETE FROM country WHERE code "+q, //nolint:gosec // q is placeholders
			codes...); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func fetchCountries(ctx context.Context) ([]countryJSON, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, countriesURL, nil)
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
		return nil, fmt.Errorf("fetch countries: status %d", resp.StatusCode)
	}

	var countries []countryJSON
	if err := json.NewDecoder(resp.Body).Decode(&countries); err != nil {
		return nil, fmt.Errorf("decode countries: %w", err)
	}

	return countries, nil
}
