package packages

import (
	"bytes"
	"context"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"

	"www/internal/pacmandb"
	"www/internal/sanitize"
)

const defaultMirror = "https://geo.mirror.pkgbuild.com/"

type repoConfig struct {
	Name         string
	Architecture string
	Testing      bool
}

var repositories = []repoConfig{
	{"core", "x86_64", false},
	{"core-testing", "x86_64", true},
	{"extra", "x86_64", false},
	{"extra-testing", "x86_64", true},
	{"multilib", "x86_64", false},
	{"multilib-testing", "x86_64", true},
}

func Update(ctx context.Context, db *sql.DB) error {
	mirror := defaultMirror

	for _, repo := range repositories {
		if err := ensureRepository(ctx, db, repo); err != nil {
			return fmt.Errorf("ensure repository %s: %w", repo.Name, err)
		}
	}

	for _, repo := range repositories {
		slog.Info("updating packages", "repo", repo.Name, "arch", repo.Architecture)
		if err := updateRepository(ctx, db, mirror, repo); err != nil {
			return fmt.Errorf("update %s: %w", repo.Name, err)
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

func updateRepository(ctx context.Context, db *sql.DB, mirror string, repo repoConfig) error {
	url := fmt.Sprintf("%s%s/os/%s/%s.files", mirror, repo.Name, repo.Architecture, repo.Name)

	req, err := http.NewRequestWithContext(ctx, http.MethodGet, url, nil)
	if err != nil {
		return fmt.Errorf("create request %s: %w", url, err)
	}
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return fmt.Errorf("download %s: %w", url, err)
	}
	defer func() { _ = resp.Body.Close() }()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("download %s: status %d", url, resp.StatusCode)
	}

	data, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("read %s: %w", url, err)
	}

	newHash := sha256hex(data)

	var storedHash sql.NullString
	_ = db.QueryRowContext(ctx,
		`SELECT sha256sum FROM repository WHERE name = ? AND architecture = ?`,
		repo.Name, repo.Architecture).Scan(&storedHash)

	if storedHash.Valid && storedHash.String == newHash {
		slog.Info("repository unchanged, skipping", "repo", repo.Name)
		return nil
	}

	packages, err := pacmandb.Parse(bytes.NewReader(data))
	if err != nil {
		return fmt.Errorf("parse %s: %w", repo.Name, err)
	}

	slog.Info("parsed packages", "repo", repo.Name, "count", len(packages))

	if err := syncPackages(ctx, db, repo, packages); err != nil {
		return err
	}

	_, err = db.ExecContext(ctx,
		`UPDATE repository SET sha256sum = ? WHERE name = ? AND architecture = ?`,
		newHash, repo.Name, repo.Architecture)
	return err
}

func syncPackages(ctx context.Context, db *sql.DB, repo repoConfig, packages []pacmandb.Package) error {
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

	// Delete existing packages for this repository (full replace per repo)
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
	if _, err := tx.ExecContext(ctx, `DELETE FROM package WHERE repository_id = ?`, repoID); err != nil {
		return fmt.Errorf("delete packages: %w", err)
	}

	insertPkg, err := tx.PrepareContext(ctx,
		`INSERT INTO package (repository_id, name, base, version, description, url, build_date,
		 compressed_size, installed_size, packager_name, packager_email, licenses, groups)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`)
	if err != nil {
		return fmt.Errorf("prepare package insert: %w", err)
	}
	defer func() { _ = insertPkg.Close() }()

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

	for _, pkg := range packages {
		var pkgURL *string
		if pkg.URL != "" && sanitize.IsValidURL(pkg.URL, "http", "https") {
			pkgURL = &pkg.URL
		}

		result, err := insertPkg.ExecContext(ctx,
			repoID, pkg.Name, pkg.Base, pkg.Version, pkg.Description, pkgURL,
			pkg.BuildDate, pkg.CompressedSize, pkg.InstalledSize,
			pkg.PackagerName, nullString(pkg.PackagerEmail),
			pacmandb.LicensesJSON(pkg.Licenses), pacmandb.GroupsJSON(pkg.Groups),
		)
		if err != nil {
			return fmt.Errorf("insert package %s: %w", pkg.Name, err)
		}

		pkgID, err := result.LastInsertId()
		if err != nil {
			return fmt.Errorf("get package id: %w", err)
		}

		for _, rel := range pkg.Relations {
			if _, err := insertRel.ExecContext(ctx,
				pkgID, rel.Type, rel.TargetName,
				nullString(rel.TargetVersion), nullString(rel.VersionConstraint),
			); err != nil {
				return fmt.Errorf("insert relation %s->%s: %w", pkg.Name, rel.TargetName, err)
			}
		}

		if len(pkg.Files) > 0 {
			if _, err := insertFiles.ExecContext(ctx, pkgID, strings.Join(pkg.Files, "\n")); err != nil {
				return fmt.Errorf("insert files for %s: %w", pkg.Name, err)
			}
		}
	}

	// Rebuild FTS index from content table
	if _, err := tx.ExecContext(ctx, `INSERT INTO package_fts(package_fts) VALUES('rebuild')`); err != nil {
		return fmt.Errorf("rebuild fts: %w", err)
	}

	return tx.Commit()
}

func sha256hex(data []byte) string {
	h := sha256.Sum256(data)
	return hex.EncodeToString(h[:])
}

func nullString(s string) any {
	if s == "" {
		return nil
	}
	return s
}
