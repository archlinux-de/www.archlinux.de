package packages

import (
	"context"
	"database/sql"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"
	"sync"

	"archded/internal/pacmandb"
	"archded/internal/sanitize"
)

type repoConfig struct {
	Name         string
	Architecture string
	Testing      bool
}

var repositories = []repoConfig{
	{"core", "x86_64", false},        //nolint:goconst
	{"core-testing", "x86_64", true}, //nolint:goconst
	{"extra", "x86_64", false},
	{"extra-testing", "x86_64", true},
	{"multilib", "x86_64", false},
	{"multilib-testing", "x86_64", true},
}

type fetchedRepo struct {
	repo    repoConfig
	etag    string
	changed bool
	body    io.ReadCloser
}

func Update(ctx context.Context, db *sql.DB, mirror string) error {
	for _, repo := range repositories {
		if err := ensureRepository(ctx, db, repo); err != nil {
			return fmt.Errorf("ensure repository %s: %w", repo.Name, err)
		}
	}

	// Check all repositories concurrently (ETag-based, no body read yet)
	client := &http.Client{}
	fetched := make([]fetchedRepo, len(repositories))
	var mu sync.Mutex
	var firstErr error

	var wg sync.WaitGroup
	for i, repo := range repositories {
		wg.Add(1)
		go func() {
			defer wg.Done()
			f, err := fetchRepository(ctx, db, client, mirror, repo)
			if err != nil {
				mu.Lock()
				if firstErr == nil {
					firstErr = fmt.Errorf("fetch %s: %w", repo.Name, err)
				}
				mu.Unlock()
				return
			}
			fetched[i] = f
		}()
	}
	wg.Wait()

	if firstErr != nil {
		// Close any open response bodies on error
		for _, f := range fetched {
			if f.body != nil {
				_ = f.body.Close()
			}
		}
		return firstErr
	}

	// Stream parse + DB write sequentially for changed repos
	changed := false
	for _, f := range fetched {
		if !f.changed {
			continue
		}
		changed = true
		if err := syncPackages(ctx, db, f.repo, f.body); err != nil {
			// Close remaining bodies
			_ = f.body.Close()
			for _, f2 := range fetched {
				if f2.body != nil && f2.body != f.body {
					_ = f2.body.Close()
				}
			}
			return err
		}
		_ = f.body.Close()
		if _, err := db.ExecContext(ctx,
			`UPDATE repository SET etag = ? WHERE name = ? AND architecture = ?`,
			f.etag, f.repo.Name, f.repo.Architecture); err != nil {
			return fmt.Errorf("update etag %s: %w", f.repo.Name, err)
		}
	}

	if changed {
		if _, err := db.ExecContext(ctx, `INSERT INTO package_fts(package_fts) VALUES('rebuild')`); err != nil {
			return fmt.Errorf("rebuild fts: %w", err)
		}
	}

	return nil
}

func ensureRepository(ctx context.Context, db *sql.DB, repo repoConfig) error {
	_, err := db.ExecContext(ctx,
		`INSERT INTO repository (name, architecture, testing) VALUES (?, ?, ?)
		 ON CONFLICT (name, architecture) DO UPDATE SET testing = excluded.testing`,
		repo.Name, repo.Architecture, repo.Testing,
	)
	return err
}

func fetchRepository(ctx context.Context, db *sql.DB, client *http.Client, mirror string, repo repoConfig) (fetchedRepo, error) {
	url := fmt.Sprintf("%s%s/os/%s/%s.files", mirror, repo.Name, repo.Architecture, repo.Name)

	var storedEtag string
	_ = db.QueryRowContext(ctx,
		`SELECT etag FROM repository WHERE name = ? AND architecture = ?`,
		repo.Name, repo.Architecture).Scan(&storedEtag)

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return fetchedRepo{}, fmt.Errorf("create request %s: %w", url, err)
	}
	req.Header.Set("User-Agent", "archded/1.0 (+https://www.archlinux.de)")
	if storedEtag != "" {
		req.Header.Set("If-None-Match", storedEtag)
	}

	resp, err := client.Do(req)
	if err != nil {
		return fetchedRepo{}, fmt.Errorf("download %s: %w", url, err)
	}

	if resp.StatusCode == http.StatusNotModified {
		_ = resp.Body.Close()
		slog.Info("repository unchanged", "repo", repo.Name)
		return fetchedRepo{repo: repo}, nil
	}

	if resp.StatusCode != http.StatusOK {
		_ = resp.Body.Close()
		return fetchedRepo{}, fmt.Errorf("download %s: status %d", url, resp.StatusCode)
	}

	slog.Info("downloading repository", "repo", repo.Name, "arch", repo.Architecture)

	return fetchedRepo{
		repo:    repo,
		etag:    resp.Header.Get("ETag"),
		changed: true,
		body:    resp.Body,
	}, nil
}

func syncPackages(ctx context.Context, db *sql.DB, repo repoConfig, r io.Reader) error {
	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		return err
	}
	defer func() { _ = tx.Rollback() }()

	var repoID int64
	err = tx.QueryRowContext(ctx,
		`SELECT id FROM repository WHERE name = ? AND architecture = ?`,
		repo.Name, repo.Architecture,
	).Scan(&repoID)
	if err != nil {
		return fmt.Errorf("find repository: %w", err)
	}

	// Delete relations and files (will be reinserted below)
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM package_relation WHERE package_id IN (SELECT id FROM package WHERE repository_id = ?)`,
		repoID,
	); err != nil {
		return fmt.Errorf("delete relations: %w", err)
	}
	if _, err := tx.ExecContext(ctx,
		`DELETE FROM files WHERE package_id IN (SELECT id FROM package WHERE repository_id = ?)`,
		repoID,
	); err != nil {
		return fmt.Errorf("delete files: %w", err)
	}

	// Upsert packages, preserving popularity columns
	upsertPkg, err := tx.PrepareContext(ctx,
		`INSERT INTO package (repository_id, name, base, version, description, url, build_date,
		 compressed_size, installed_size, packager_name, packager_email, licenses, groups, provides)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		 ON CONFLICT (repository_id, name) DO UPDATE SET
		   base = excluded.base, version = excluded.version, description = excluded.description,
		   url = excluded.url, build_date = excluded.build_date,
		   compressed_size = excluded.compressed_size, installed_size = excluded.installed_size,
		   packager_name = excluded.packager_name, packager_email = excluded.packager_email,
		   licenses = excluded.licenses, groups = excluded.groups, provides = excluded.provides
		 RETURNING id`)
	if err != nil {
		return fmt.Errorf("prepare package upsert: %w", err)
	}
	defer func() { _ = upsertPkg.Close() }()

	insertRel, err := tx.PrepareContext(ctx,
		`INSERT INTO package_relation (package_id, type, target_name, target_version, version_constraint)
		 VALUES (?, ?, ?, ?, ?)`)
	if err != nil {
		return fmt.Errorf("prepare relation insert: %w", err)
	}
	defer func() { _ = insertRel.Close() }()

	insertFiles, err := tx.PrepareContext(ctx,
		`INSERT INTO files (package_id, file_list) VALUES (?, ?)`)
	if err != nil {
		return fmt.Errorf("prepare files insert: %w", err)
	}
	defer func() { _ = insertFiles.Close() }()

	upsertedIDs := make(map[int64]struct{})
	count := 0

	if err := pacmandb.Parse(r, func(pkg pacmandb.Package) error {
		var pkgURL string
		if pkg.URL != "" && sanitize.IsValidURL(pkg.URL, "http", "https") {
			pkgURL = pkg.URL
		}

		var provides []string
		for _, rel := range pkg.Relations {
			//nolint:goconst // literal is clearer
			if rel.Type == "provides" {
				provides = append(provides, rel.TargetName)
			}
		}

		var pkgID int64
		if err := upsertPkg.QueryRowContext(ctx,
			repoID, pkg.Name, pkg.Base, pkg.Version, pkg.Description, pkgURL,
			pkg.BuildDate, pkg.CompressedSize, pkg.InstalledSize,
			pkg.PackagerName, pkg.PackagerEmail,
			pacmandb.LicensesJSON(pkg.Licenses), pacmandb.GroupsJSON(pkg.Groups),
			strings.Join(provides, " "),
		).Scan(&pkgID); err != nil {
			return fmt.Errorf("upsert package %s: %w", pkg.Name, err)
		}

		upsertedIDs[pkgID] = struct{}{}
		count++

		for _, rel := range pkg.Relations {
			if _, err := insertRel.ExecContext(ctx,
				pkgID, rel.Type, rel.TargetName,
				rel.TargetVersion, rel.VersionConstraint,
			); err != nil {
				return fmt.Errorf("insert relation %s->%s: %w", pkg.Name, rel.TargetName, err)
			}
		}

		if len(pkg.Files) > 0 {
			if _, err := insertFiles.ExecContext(ctx, pkgID, strings.Join(pkg.Files, "\n")); err != nil {
				return fmt.Errorf("insert files for %s: %w", pkg.Name, err)
			}
		}

		return nil
	}); err != nil {
		return fmt.Errorf("parse %s: %w", repo.Name, err)
	}

	slog.Info("parsed packages", "repo", repo.Name, "count", count)

	if err := deleteStalePackages(ctx, tx, repoID, upsertedIDs); err != nil {
		return err
	}

	return tx.Commit()
}

func deleteStalePackages(ctx context.Context, tx *sql.Tx, repoID int64, keepIDs map[int64]struct{}) error {
	rows, err := tx.QueryContext(ctx, `SELECT id FROM package WHERE repository_id = ?`, repoID)
	if err != nil {
		return fmt.Errorf("query existing packages: %w", err)
	}
	var staleIDs []int64
	for rows.Next() {
		var id int64
		if err := rows.Scan(&id); err != nil {
			_ = rows.Close()
			return fmt.Errorf("scan package id: %w", err)
		}
		if _, ok := keepIDs[id]; !ok {
			staleIDs = append(staleIDs, id)
		}
	}
	if err := rows.Close(); err != nil {
		return fmt.Errorf("iterate packages: %w", err)
	}
	for _, id := range staleIDs {
		if _, err := tx.ExecContext(ctx, `DELETE FROM package WHERE id = ?`, id); err != nil {
			return fmt.Errorf("delete stale package %d: %w", id, err)
		}
	}
	return nil
}
