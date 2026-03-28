package releases

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"regexp"
	"strings"
	"time"

	"archded/internal/sanitize"
)

const relengURL = "https://archlinux.org/releng/releases/json/"

type relengResponse struct {
	Releases []relengRelease `json:"releases"`
}

type relengRelease struct {
	Version        string         `json:"version"`
	Available      bool           `json:"available"`
	Info           string         `json:"info"`
	Created        string         `json:"created"`
	ReleaseDate    string         `json:"release_date"`
	KernelVersion  *string        `json:"kernel_version"`
	ISOUrl         *string        `json:"iso_url"`
	SHA1Sum        *string        `json:"sha1_sum"`
	SHA256Sum      *string        `json:"sha256_sum"`
	B2Sum          *string        `json:"b2_sum"`
	TorrentURL     *string        `json:"torrent_url"`
	MagnetURI      *string        `json:"magnet_uri"`
	PGPFingerprint *string        `json:"pgp_fingerprint"`
	WKDEmail       *string        `json:"wkd_email"`
	Torrent        *relengTorrent `json:"torrent"`
}

type relengTorrent struct {
	FileName   string `json:"file_name"`
	FileLength int64  `json:"file_length"`
}

var (
	versionRe        = regexp.MustCompile(`^[0-9]+[\.\-\w]+$`)
	kernelVersionRe  = regexp.MustCompile(`^[\d\.]{5,10}$`)
	sha1Re           = regexp.MustCompile(`^[0-9a-f]{40}$`)
	sha256Re         = regexp.MustCompile(`^[0-9a-f]{64}$`)
	b2Re             = regexp.MustCompile(`^[0-9a-f]{128}$`)
	pgpFingerprintRe = regexp.MustCompile(`^[0-9A-F]{40}$`)
	emailRe          = regexp.MustCompile(`^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$`)
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

	stmt, err := tx.PrepareContext(ctx,
		`INSERT INTO release (version, available, info, created, release_date, kernel_version, file_name, file_length, sha1_sum, sha256_sum, b2_sum, torrent_url, magnet_uri, pgp_fingerprint, wkd_email)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		 ON CONFLICT (version) DO UPDATE SET
		   available = excluded.available, info = excluded.info, created = excluded.created,
		   release_date = excluded.release_date, kernel_version = excluded.kernel_version,
		   file_name = excluded.file_name, file_length = excluded.file_length,
		   sha1_sum = excluded.sha1_sum, sha256_sum = excluded.sha256_sum, b2_sum = excluded.b2_sum,
		   torrent_url = excluded.torrent_url, magnet_uri = excluded.magnet_uri,
		   pgp_fingerprint = excluded.pgp_fingerprint, wkd_email = excluded.wkd_email`)
	if err != nil {
		return err
	}
	defer func() { _ = stmt.Close() }()

	var versions []any
	for _, r := range releases {
		if !versionRe.MatchString(r.Version) {
			slog.Warn("skipping release with invalid version", "version", r.Version)
			continue
		}

		var created, releaseDate int64
		if t, err := time.Parse(time.RFC3339Nano, r.Created); err == nil {
			created = t.Unix()
		}
		if t, err := time.Parse("2006-01-02", r.ReleaseDate); err == nil {
			releaseDate = t.Unix()
		}

		var fileName string
		var fileLength int64
		if r.Torrent != nil {
			fileName = r.Torrent.FileName
			fileLength = r.Torrent.FileLength
		}

		var torrentURL string
		if r.TorrentURL != nil && *r.TorrentURL != "" {
			torrentURL = "https://archlinux.org" + *r.TorrentURL
		}

		info := sanitize.HTML(r.Info)
		kernelVersion := matchOrEmpty(r.KernelVersion, kernelVersionRe)
		sha1Sum := matchOrEmpty(r.SHA1Sum, sha1Re)
		sha256Sum := matchOrEmpty(r.SHA256Sum, sha256Re)
		b2Sum := matchOrEmpty(r.B2Sum, b2Re)
		pgpFingerprint := matchOrEmpty(r.PGPFingerprint, pgpFingerprintRe)
		wkdEmail := matchOrEmpty(r.WKDEmail, emailRe)

		if _, err := stmt.ExecContext(ctx,
			r.Version, r.Available, info, created, releaseDate,
			kernelVersion, fileName, fileLength,
			sha1Sum, sha256Sum, b2Sum,
			torrentURL, derefOrEmpty(r.MagnetURI),
			pgpFingerprint, wkdEmail,
		); err != nil {
			return err
		}
		versions = append(versions, r.Version)
	}

	if len(versions) > 0 {
		placeholders := strings.Repeat("?,", len(versions))
		placeholders = placeholders[:len(placeholders)-1]
		if _, err := tx.ExecContext(ctx,
			fmt.Sprintf("DELETE FROM release WHERE version NOT IN (%s)", placeholders),
			versions...); err != nil {
			return err
		}
	}

	return tx.Commit()
}

func matchOrEmpty(s *string, re *regexp.Regexp) string {
	if s != nil && re.MatchString(*s) {
		return *s
	}
	return ""
}

func derefOrEmpty(s *string) string {
	if s != nil {
		return *s
	}
	return ""
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
	defer func() { _ = resp.Body.Close() }()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("fetch releases: status %d", resp.StatusCode)
	}

	var data relengResponse
	if err := json.NewDecoder(resp.Body).Decode(&data); err != nil {
		return nil, fmt.Errorf("decode releng response: %w", err)
	}

	return data.Releases, nil
}
