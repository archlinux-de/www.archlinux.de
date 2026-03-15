package countries

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
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

	if _, err := tx.ExecContext(ctx, `DELETE FROM country`); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx, `INSERT INTO country (code, name) VALUES (?, ?)`)
	if err != nil {
		return err
	}
	defer stmt.Close()

	for _, c := range countries {
		if c.CCA2 == "" || c.Name.Common == "" {
			continue
		}
		if _, err := stmt.ExecContext(ctx, c.CCA2, c.Name.Common); err != nil {
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
	defer resp.Body.Close()

	var countries []countryJSON
	if err := json.NewDecoder(resp.Body).Decode(&countries); err != nil {
		return nil, fmt.Errorf("decode countries: %w", err)
	}

	return countries, nil
}
