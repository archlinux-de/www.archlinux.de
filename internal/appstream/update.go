package appstream

import (
	"compress/gzip"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"
	"time"
)

// DefaultSourcesBase is the directory listing published by Arch that contains
// versioned snapshots (e.g. …/20260326/{core,extra,multilib}/Components-x86_64.xml.gz).
const DefaultSourcesBase = "https://sources.archlinux.org/other/packages/archlinux-appstream-data/"

const archlinuxPackageJSON = "https://archlinux.org/packages/extra/any/archlinux-appstream-data/json/"

var componentRepos = []string{"core", "extra", "multilib"}

type pkgJSON struct {
	Pkgver string `json:"pkgver"`
}

// LatestRelease returns the snapshot directory name (e.g. "20260326") matching
// the current extra/any archlinux-appstream-data package in the official repos.
func LatestRelease(ctx context.Context, client *http.Client) (string, error) {
	if client == nil {
		client = &http.Client{Timeout: 2 * time.Minute}
	}
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, archlinuxPackageJSON, nil)
	if err != nil {
		return "", err
	}
	req.Header.Set("User-Agent", "archded/1.0 (+https://www.archlinux.de)")

	resp, err := client.Do(req)
	if err != nil {
		return "", fmt.Errorf("fetch package json: %w", err)
	}
	defer func() { _ = resp.Body.Close() }()
	if resp.StatusCode != http.StatusOK {
		return "", fmt.Errorf("fetch package json: status %d", resp.StatusCode)
	}
	var p pkgJSON
	if err := json.NewDecoder(resp.Body).Decode(&p); err != nil {
		return "", fmt.Errorf("decode package json: %w", err)
	}
	if p.Pkgver == "" {
		return "", fmt.Errorf("empty pkgver in package json")
	}
	return p.Pkgver, nil
}

// Update downloads AppStream component XML for core, extra, and multilib from
// sourcesBase, merges keywords by package name, writes the keywords column,
// and rebuilds the FTS index.
func Update(ctx context.Context, db *sql.DB, client *http.Client, sourcesBase string) error {
	if client == nil {
		client = &http.Client{Timeout: 15 * time.Minute}
	}
	sourcesBase = strings.TrimSuffix(sourcesBase, "/") + "/"

	version, err := LatestRelease(ctx, client)
	if err != nil {
		return err
	}
	slog.Info("appstream snapshot", "version", version)

	acc := make(map[string][]string)
	for _, repo := range componentRepos {
		var components int
		err := fetchRepoComponents(ctx, client, sourcesBase, version, repo, func(name string, parts []string) error {
			components++
			acc[name] = append(acc[name], parts...)
			return nil
		})
		if err != nil {
			return fmt.Errorf("repo %s: %w", repo, err)
		}
		slog.Info("appstream components parsed", "repo", repo, "components", components)
	}

	merged := make(map[string]string, len(acc))
	for name, parts := range acc {
		merged[name] = dedupeWords(parts)
	}

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `UPDATE package SET keywords = ''`); err != nil {
		return fmt.Errorf("clear keywords: %w", err)
	}

	stmt, err := tx.PrepareContext(ctx, `UPDATE package SET keywords = ? WHERE name = ?`)
	if err != nil {
		return fmt.Errorf("prepare keyword update: %w", err)
	}
	defer func() { _ = stmt.Close() }()

	var updated int64
	for name, kw := range merged {
		if kw == "" {
			continue
		}
		res, err := stmt.ExecContext(ctx, kw, name)
		if err != nil {
			return fmt.Errorf("update keywords for %q: %w", name, err)
		}
		n, err := res.RowsAffected()
		if err != nil {
			return err
		}
		updated += n
	}

	if err := tx.Commit(); err != nil {
		return err
	}

	slog.Info("appstream keywords applied", "distinct_names", len(merged), "package_rows", updated)

	if _, err := db.ExecContext(ctx, `INSERT INTO package_fts(package_fts) VALUES('rebuild')`); err != nil {
		return fmt.Errorf("rebuild fts: %w", err)
	}

	return nil
}

func fetchRepoComponents(ctx context.Context, client *http.Client, base, version, repo string, fn func(string, []string) error) error {
	u := fmt.Sprintf("%s%s/%s/Components-x86_64.xml.gz", base, version, repo)
	req, err := http.NewRequestWithContext(ctx, http.MethodGet, u, nil)
	if err != nil {
		return err
	}
	req.Header.Set("User-Agent", "archded/1.0 (+https://www.archlinux.de)")

	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer func() { _ = resp.Body.Close() }()
	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("GET %s: status %d", u, resp.StatusCode)
	}

	gz, err := gzip.NewReader(resp.Body)
	if err != nil {
		return fmt.Errorf("gzip %s: %w", u, err)
	}
	defer func() { _ = gz.Close() }()

	return ParseComponentsXML(io.Reader(gz), fn)
}
