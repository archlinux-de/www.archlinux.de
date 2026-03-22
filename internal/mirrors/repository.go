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

	baseSelect := `SELECT url, COALESCE(country_name, ''), COALESCE(duration_avg, 0), COALESCE(delay, 0), COALESCE(last_sync, 0), ipv4, ipv6 FROM mirror`

	if search != "" {
		searchArg := "%" + search + "%"
		where := ` WHERE url LIKE ? OR country_name LIKE ?`
		countQuery = `SELECT COUNT(*) FROM mirror` + where
		countArgs = []any{searchArg, searchArg}

		dataQuery = baseSelect + where + ` ORDER BY score ASC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, limit, offset}
	} else {
		countQuery = `SELECT COUNT(*) FROM mirror`
		dataQuery = baseSelect + ` ORDER BY score ASC LIMIT ? OFFSET ?`
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
