package releases

import (
	"context"
	"database/sql"
)

type Release struct {
	Version        string
	Available      bool
	Info           string
	ReleaseDate    int64
	Created        int64
	KernelVersion  string
	FileLength     int64
	FileName       string
	SHA1Sum        string
	SHA256Sum      string
	B2Sum          string
	TorrentURL     string
	MagnetURI      string
	PGPFingerprint string
	WKDEmail       string
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) Search(ctx context.Context, search string, limit, offset int) ([]Release, int, error) {
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		searchArg := "%" + search + "%"
		where := ` WHERE version LIKE ? OR info LIKE ? OR kernel_version LIKE ?`
		countQuery = `SELECT COUNT(*) FROM release` + where
		countArgs = []any{searchArg, searchArg, searchArg}

		dataQuery = `SELECT version, available, info, release_date, kernel_version, file_length, file_name
			FROM release` + where + ` ORDER BY release_date DESC LIMIT ? OFFSET ?`
		dataArgs = []any{searchArg, searchArg, searchArg, limit, offset}
	} else {
		countQuery = `SELECT COUNT(*) FROM release`
		dataQuery = `SELECT version, available, info, release_date, kernel_version, file_length, file_name
			FROM release ORDER BY release_date DESC LIMIT ? OFFSET ?`
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

	var rels []Release
	for rows.Next() {
		var rel Release
		if err := rows.Scan(&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion, &rel.FileLength, &rel.FileName); err != nil {
			return nil, 0, err
		}
		rels = append(rels, rel)
	}

	return rels, total, rows.Err()
}

func (r *Repository) FindByVersion(ctx context.Context, version string) (Release, error) {
	var rel Release
	err := r.db.QueryRowContext(ctx,
		`SELECT version, available, info, release_date, kernel_version,
		        file_length, file_name, sha1_sum, sha256_sum,
		        b2_sum, torrent_url, magnet_uri,
		        pgp_fingerprint, wkd_email
		 FROM release WHERE version = ?`, version).Scan(
		&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion,
		&rel.FileLength, &rel.FileName, &rel.SHA1Sum, &rel.SHA256Sum,
		&rel.B2Sum, &rel.TorrentURL, &rel.MagnetURI,
		&rel.PGPFingerprint, &rel.WKDEmail,
	)
	return rel, err
}

func (r *Repository) LatestAvailable(ctx context.Context) (Release, error) {
	var rel Release
	err := r.db.QueryRowContext(ctx,
		`SELECT version, available, info, release_date, kernel_version,
		        file_length, file_name, sha1_sum, sha256_sum,
		        b2_sum, torrent_url, magnet_uri,
		        pgp_fingerprint, wkd_email
		 FROM release WHERE available = 1 ORDER BY release_date DESC LIMIT 1`).Scan(
		&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion,
		&rel.FileLength, &rel.FileName, &rel.SHA1Sum, &rel.SHA256Sum,
		&rel.B2Sum, &rel.TorrentURL, &rel.MagnetURI,
		&rel.PGPFingerprint, &rel.WKDEmail,
	)
	return rel, err
}

func (r *Repository) AllAvailable(ctx context.Context) ([]Release, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT version, available, info, release_date, kernel_version,
		        file_length, file_name
		 FROM release WHERE available = 1 ORDER BY release_date DESC`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var rels []Release
	for rows.Next() {
		var rel Release
		if err := rows.Scan(&rel.Version, &rel.Available, &rel.Info, &rel.ReleaseDate, &rel.KernelVersion, &rel.FileLength, &rel.FileName); err != nil {
			return nil, err
		}
		rels = append(rels, rel)
	}
	return rels, rows.Err()
}

type ReleaseAvailability struct {
	Available bool
	Created   int64
}

func (r *Repository) Availability(ctx context.Context, version string) (ReleaseAvailability, error) {
	var ra ReleaseAvailability
	err := r.db.QueryRowContext(ctx,
		`SELECT available, created FROM release WHERE version = ?`, version).Scan(&ra.Available, &ra.Created)
	return ra, err
}

type ReleaseRef struct {
	Version string
	Created int64
}

func (r *Repository) AllRefs(ctx context.Context) ([]ReleaseRef, error) {
	rows, err := r.db.QueryContext(ctx, `SELECT version, created FROM release`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var refs []ReleaseRef
	for rows.Next() {
		var ref ReleaseRef
		if err := rows.Scan(&ref.Version, &ref.Created); err != nil {
			return nil, err
		}
		refs = append(refs, ref)
	}
	return refs, rows.Err()
}
