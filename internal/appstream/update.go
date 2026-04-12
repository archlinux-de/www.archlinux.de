package appstream

import (
	"compress/gzip"
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"
	"time"
)

// archlinuxPackageJSON is the official package metadata used to resolve the
// appstream-data snapshot directory name (pkgver). The XML base URL is passed
// into Update as sourcesBase (from config.APPSTREAM_SOURCES_BASE / CLI).
const archlinuxPackageJSON = "https://archlinux.org/packages/extra/any/archlinux-appstream-data/json/"

const (
	httpClientTimeoutRelease = 2 * time.Minute
	httpClientTimeoutUpdate  = 15 * time.Minute
)

var componentRepos = []string{"core", "extra", "multilib"}

type pkgJSON struct {
	Pkgver string `json:"pkgver"`
}

// LatestRelease returns the snapshot directory name (e.g. "20260326") matching
// the current extra/any archlinux-appstream-data package in the official repos.
func LatestRelease(ctx context.Context, client *http.Client) (string, error) {
	if client == nil {
		client = &http.Client{Timeout: httpClientTimeoutRelease}
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
		return "", errors.New("empty pkgver in package json")
	}
	return p.Pkgver, nil
}

// Update downloads AppStream component XML for core, extra, and multilib from
// sourcesBase (e.g. config.AppStreamSourcesBase), merges keywords and categories
// by package name, writes both columns, and rebuilds the FTS index.
func Update(ctx context.Context, db *sql.DB, client *http.Client, sourcesBase string) error {
	if client == nil {
		client = &http.Client{Timeout: httpClientTimeoutUpdate}
	}
	sourcesBase = strings.TrimSuffix(sourcesBase, "/") + "/"

	version, err := LatestRelease(ctx, client)
	if err != nil {
		return err
	}
	slog.Info("appstream snapshot", "version", version)

	accKW := make(map[string][]string)
	accCat := make(map[string][]string)
	for _, repo := range componentRepos {
		var components int
		err := fetchRepoComponents(ctx, client, sourcesBase, version, repo, func(name string, terms IndexTerms) error {
			components++
			accKW[name] = append(accKW[name], terms.Keywords...)
			accCat[name] = append(accCat[name], terms.Categories...)
			return nil
		})
		if err != nil {
			return fmt.Errorf("repo %s: %w", repo, err)
		}
		slog.Info("appstream components parsed", "repo", repo, "components", components)
	}

	names := make(map[string]struct{})
	for k := range accKW {
		names[k] = struct{}{}
	}
	for k := range accCat {
		names[k] = struct{}{}
	}

	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	if _, err := tx.ExecContext(ctx, `UPDATE package SET keywords = '', categories = ''`); err != nil {
		return fmt.Errorf("clear appstream columns: %w", err)
	}

	stmt, err := tx.PrepareContext(ctx, `UPDATE package SET keywords = ?, categories = ? WHERE name = ?`)
	if err != nil {
		return fmt.Errorf("prepare appstream update: %w", err)
	}
	defer func() { _ = stmt.Close() }()

	var updated int64
	for name := range names {
		kw := dedupeWords(accKW[name])
		cat := dedupeWords(accCat[name])
		if kw == "" && cat == "" {
			continue
		}
		res, err := stmt.ExecContext(ctx, kw, cat, name)
		if err != nil {
			return fmt.Errorf("update appstream fields for %q: %w", name, err)
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

	slog.Info("appstream fields applied", "distinct_names", len(names), "package_rows", updated)

	if _, err := db.ExecContext(ctx, `INSERT INTO package_fts(package_fts) VALUES('rebuild')`); err != nil {
		return fmt.Errorf("rebuild fts: %w", err)
	}

	return nil
}

func fetchRepoComponents(ctx context.Context, client *http.Client, base, version, repo string, fn func(string, IndexTerms) error) error {
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
