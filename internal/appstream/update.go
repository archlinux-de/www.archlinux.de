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
)

// archlinuxPackageJSON is the official package metadata used to resolve the
// appstream-data snapshot directory name (pkgver). The XML base URL is passed
// into Update as sourcesBase (from config.APPSTREAM_SOURCES_BASE / CLI).
const archlinuxPackageJSON = "https://archlinux.org/packages/extra/any/archlinux-appstream-data/json/"

var componentRepos = []string{"core", "extra", "multilib"}

type pkgJSON struct {
	Pkgver string `json:"pkgver"`
}

// latestRelease returns the snapshot directory name (e.g. "20260326") matching
// the current extra/any archlinux-appstream-data package in the official repos.
func latestRelease(ctx context.Context, client *http.Client) (string, error) {
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
// sourcesBase (see config.go), merges keywords and categories
// by package name, writes both columns, and rebuilds the FTS index.
func Update(ctx context.Context, db *sql.DB, sourcesBase string) error {
	client := &http.Client{}
	sourcesBase = strings.TrimSuffix(sourcesBase, "/") + "/"

	version, err := latestRelease(ctx, client)
	if err != nil {
		return err
	}
	slog.Info("appstream snapshot", "version", version)

	type terms struct{ kw, cat []string }
	acc := make(map[string]*terms)
	for _, repo := range componentRepos {
		var components int
		err := fetchRepoComponents(ctx, client, sourcesBase, version, repo, func(name string, t IndexTerms) error {
			components++
			e, ok := acc[name]
			if !ok {
				e = &terms{}
				acc[name] = e
			}
			e.kw = append(e.kw, t.Keywords...)
			e.cat = append(e.cat, t.Categories...)
			return nil
		})
		if err != nil {
			return fmt.Errorf("repo %s: %w", repo, err)
		}
		slog.Info("appstream components parsed", "repo", repo, "components", components)
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
	for name, e := range acc {
		kw := dedupeWords(e.kw)
		cat := dedupeWords(e.cat)
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

	slog.Info("appstream fields applied", "distinct_names", len(acc), "package_rows", updated)

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
