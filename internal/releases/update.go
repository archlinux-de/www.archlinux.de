package releases

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"time"
)

const relengURL = "https://archlinux.org/releng/releases/json/"

type relengResponse struct {
	Releases []relengRelease `json:"releases"`
}

type relengRelease struct {
	Version       string         `json:"version"`
	Available     bool           `json:"available"`
	Info          string         `json:"info"`
	Created       string         `json:"created"`
	ReleaseDate   string         `json:"release_date"`
	KernelVersion *string        `json:"kernel_version"`
	ISOUrl        *string        `json:"iso_url"`
	SHA1Sum       *string        `json:"sha1_sum"`
	SHA256Sum     *string        `json:"sha256_sum"`
	B2Sum         *string        `json:"b2_sum"`
	TorrentURL    *string        `json:"torrent_url"`
	MagnetURI     *string        `json:"magnet_uri"`
	Torrent       *relengTorrent `json:"torrent"`
}

type relengTorrent struct {
	FileName   string `json:"file_name"`
	FileLength int64  `json:"file_length"`
}

func Update(ctx context.Context, db *sql.DB) error {
	releases, err := fetchReleases(ctx)
	if err != nil {
		return fmt.Errorf("fetch releases: %w", err)
	}

	slog.Info("fetched releases", "count", len(releases))

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `DELETE FROM release`); err != nil {
		return err
	}

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO release (version, available, info, created, release_date, kernel_version, file_name, file_length, sha1_sum, sha256_sum, b2_sum, torrent_url, magnet_uri)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
	if err != nil {
		return err
	}
	defer stmt.Close()

	for _, r := range releases {
		var created, releaseDate *int64
		if t, err := time.Parse(time.RFC3339Nano, r.Created); err == nil {
			unix := t.Unix()
			created = &unix
		}
		if t, err := time.Parse("2006-01-02", r.ReleaseDate); err == nil {
			unix := t.Unix()
			releaseDate = &unix
		}

		var fileName *string
		var fileLength *int64
		if r.Torrent != nil {
			fileName = &r.Torrent.FileName
			fileLength = &r.Torrent.FileLength
		}

		var torrentURL *string
		if r.TorrentURL != nil && *r.TorrentURL != "" {
			full := "https://archlinux.org" + *r.TorrentURL
			torrentURL = &full
		}

		if _, err := stmt.ExecContext(ctx,
			r.Version, r.Available, r.Info, created, releaseDate,
			r.KernelVersion, fileName, fileLength,
			r.SHA1Sum, r.SHA256Sum, r.B2Sum,
			torrentURL, r.MagnetURI,
		); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func fetchReleases(ctx context.Context) ([]relengRelease, error) {
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, relengURL, nil)
	if err != nil {
		return nil, err
	}
	req.Header.Set("Accept", "application/json")

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var data relengResponse
	if err := json.NewDecoder(resp.Body).Decode(&data); err != nil {
		return nil, fmt.Errorf("decode releng response: %w", err)
	}

	return data.Releases, nil
}
