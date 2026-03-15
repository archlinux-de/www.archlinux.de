package mirrors

import (
	"context"
	"database/sql"
)

type Mirror struct {
	URL         string
	CountryName string
	DurationAvg float64
	Delay       int
	LastSync    int64
	Score       float64
	IPv4        bool
	IPv6        bool
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) Search(ctx context.Context, search string, limit, offset int) ([]Mirror, int, error) {
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	baseFrom := `FROM mirror m LEFT JOIN country c ON c.code = m.country_code`

	if search != "" {
		searchArg := "%" + search + "%"
		where := ` WHERE m.url LIKE ? OR c.name LIKE ?`
		countQuery = `SELECT COUNT(*) ` + baseFrom + where
		countArgs = []any{searchArg, searchArg}

		dataQuery = `SELECT m.url, COALESCE(c.name, ''), COALESCE(m.duration_avg, 0), COALESCE(m.delay, 0), COALESCE(m.last_sync, 0), m.ipv4, m.ipv6 ` +
			baseFrom + where + ` ORDER BY m.score ASC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, limit, offset}
	} else {
		countQuery = `SELECT COUNT(*) ` + baseFrom
		dataQuery = `SELECT m.url, COALESCE(c.name, ''), COALESCE(m.duration_avg, 0), COALESCE(m.delay, 0), COALESCE(m.last_sync, 0), m.ipv4, m.ipv6 ` +
			baseFrom + ` ORDER BY m.score ASC LIMIT ? OFFSET ?`
		dataArgs = []any{limit, offset}
	}

	var total int
	if err := r.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&total); err != nil {
		return nil, 0, err
	}

	rows, err := r.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		return nil, 0, err
	}
	defer func() { _ = rows.Close() }()

	var mirrors []Mirror
	for rows.Next() {
		var m Mirror
		if err := rows.Scan(&m.URL, &m.CountryName, &m.DurationAvg, &m.Delay, &m.LastSync, &m.IPv4, &m.IPv6); err != nil {
			return nil, 0, err
		}
		mirrors = append(mirrors, m)
	}

	return mirrors, total, rows.Err()
}

type MirrorSummary struct {
	URL         string
	CountryName string
}

func (r *Repository) TopByScore(ctx context.Context, limit int) ([]MirrorSummary, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT m.url, COALESCE(c.name, '')
		 FROM mirror m LEFT JOIN country c ON c.code = m.country_code
		 ORDER BY m.score ASC LIMIT ?`, limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var mirrors []MirrorSummary
	for rows.Next() {
		var m MirrorSummary
		if err := rows.Scan(&m.URL, &m.CountryName); err != nil {
			return nil, err
		}
		mirrors = append(mirrors, m)
	}
	return mirrors, rows.Err()
}

func (r *Repository) SelectByCountry(ctx context.Context, countryCode string, lastSync *int64, limit int) ([]string, error) {
	var query string
	var args []any

	if lastSync != nil {
		query = `SELECT m.url FROM mirror m
			WHERE m.url LIKE 'https%'
			ORDER BY
				(CASE WHEN m.country_code = ? THEN 1 ELSE 0 END) DESC,
				(CASE WHEN m.last_sync >= ? THEN 1 ELSE 0 END) DESC,
				m.score ASC
			LIMIT ?`
		args = []any{countryCode, *lastSync, limit}
	} else {
		query = `SELECT m.url FROM mirror m
			WHERE m.url LIKE 'https%'
			ORDER BY
				(CASE WHEN m.country_code = ? THEN 1 ELSE 0 END) DESC,
				m.score ASC
			LIMIT ?`
		args = []any{countryCode, limit}
	}

	rows, err := r.db.QueryContext(ctx, query, args...)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var urls []string
	for rows.Next() {
		var url string
		if err := rows.Scan(&url); err != nil {
			continue
		}
		urls = append(urls, url)
	}
	return urls, rows.Err()
}
