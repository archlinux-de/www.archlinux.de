package packages

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log/slog"
	"net/http"
	"strings"

	"www/internal/pacmandb"
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

	resp, err := http.Get(url)
	if err != nil {
		return fmt.Errorf("download %s: %w", url, err)
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("download %s: status %d", url, resp.StatusCode)
	}

	packages, err := pacmandb.Parse(resp.Body)
	if err != nil {
		return fmt.Errorf("parse %s: %w", repo.Name, err)
	}

	slog.Info("parsed packages", "repo", repo.Name, "count", len(packages))

	return syncPackages(ctx, db, repo, packages)
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

	// Delete existing packages for this repository (full replace strategy)
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
	defer insertPkg.Close()

	insertRel, err := tx.PrepareContext(ctx,
		`INSERT INTO package_relation (package_id, type, target_name, target_version, version_constraint)
		 VALUES (?, ?, ?, ?, ?)`)
	if err != nil {
		return fmt.Errorf("prepare relation insert: %w", err)
	}
	defer insertRel.Close()

	insertFiles, err := tx.PrepareContext(ctx,
		`INSERT INTO files (package_id, file_list) VALUES (?, ?)`)
	if err != nil {
		return fmt.Errorf("prepare files insert: %w", err)
	}
	defer insertFiles.Close()

	for _, pkg := range packages {
		result, err := insertPkg.ExecContext(ctx,
			repoID, pkg.Name, pkg.Base, pkg.Version, pkg.Description, pkg.URL,
			pkg.BuildDate, pkg.CompressedSize, pkg.InstalledSize,
			pkg.PackagerName, nullString(pkg.PackagerEmail),
			licensesJSON(pkg.Licenses), groupsJSON(pkg.Groups),
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

func nullString(s string) any {
	if s == "" {
		return nil
	}
	return s
}

func licensesJSON(licenses []string) string {
	if len(licenses) == 0 {
		return "[]"
	}
	b, _ := json.Marshal(licenses)
	return string(b)
}

func groupsJSON(groups []string) string {
	if len(groups) == 0 {
		return "[]"
	}
	b, _ := json.Marshal(groups)
	return string(b)
}

// Download downloads the .files database for a repo and returns the body reader.
func Download(mirror, repoName, arch string) (io.ReadCloser, error) {
	url := fmt.Sprintf("%s%s/os/%s/%s.files", mirror, repoName, arch, repoName)
	resp, err := http.Get(url)
	if err != nil {
		return nil, err
	}
	if resp.StatusCode != http.StatusOK {
		resp.Body.Close()
		return nil, fmt.Errorf("download %s: status %d", url, resp.StatusCode)
	}
	return resp.Body, nil
}
