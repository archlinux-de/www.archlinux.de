package releases

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"regexp"
	"time"

	"www/internal/sanitize"
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

var (
	versionRe       = regexp.MustCompile(`^[0-9]+[\.\-\w]+$`)
	kernelVersionRe = regexp.MustCompile(`^[\d\.]{5,10}$`)
	sha1Re          = regexp.MustCompile(`^[0-9a-f]{40}$`)
	sha256Re        = regexp.MustCompile(`^[0-9a-f]{64}$`)
	b2Re            = regexp.MustCompile(`^[0-9a-f]{128}$`)
)

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
		if !versionRe.MatchString(r.Version) {
			slog.Warn("skipping release with invalid version", "version", r.Version)
			continue
		}

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

		info := sanitize.HTML(r.Info)
		kernelVersion := matchOrNil(r.KernelVersion, kernelVersionRe)
		sha1Sum := matchOrNil(r.SHA1Sum, sha1Re)
		sha256Sum := matchOrNil(r.SHA256Sum, sha256Re)
		b2Sum := matchOrNil(r.B2Sum, b2Re)

		if _, err := stmt.ExecContext(ctx,
			r.Version, r.Available, info, created, releaseDate,
			kernelVersion, fileName, fileLength,
			sha1Sum, sha256Sum, b2Sum,
			torrentURL, r.MagnetURI,
		); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func matchOrNil(s *string, re *regexp.Regexp) *string {
	if s != nil && re.MatchString(*s) {
		return s
	}
	return nil
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
