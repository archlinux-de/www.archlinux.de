package packagedetail

import (
	"context"
	"database/sql"
	"encoding/json"
	"strings"
)

type PackageDetail struct {
	Name              string
	Base              string
	Version           string
	Description       string
	URL               string
	Repository        string
	Architecture      string
	Testing           bool
	BuildDate         int64
	CompressedSize    int64
	InstalledSize     int64
	PackagerName      string
	PackagerEmail     string
	Licenses          []string
	Groups            []string
	Popularity        float64
	PopularityCount   int
	PopularitySamples int
	Relations         map[string][]Relation
	InverseRels       map[string][]Relation
}

func (p PackageDetail) FileName() string {
	return p.Name + "-" + p.Version + "-" + p.Architecture + ".pkg.tar.zst"
}

type Relation struct {
	TargetName        string
	TargetVersion     string
	VersionConstraint string
}

type ResolvedPackage struct {
	Name       string
	Repository string
	Arch       string
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) FindByRepoArchName(ctx context.Context, repo, arch, name string) (PackageDetail, error) {
	var pkg PackageDetail
	var licensesJSON, groupsJSON sql.NullString
	var testing int

	err := r.db.QueryRowContext(ctx,
		`SELECT p.name, p.base, p.version, COALESCE(p.description, ''), COALESCE(p.url, ''),
			r.name, r.architecture, r.testing,
			COALESCE(p.build_date, 0), COALESCE(p.compressed_size, 0), COALESCE(p.installed_size, 0),
			COALESCE(p.packager_name, ''), COALESCE(p.packager_email, ''),
			COALESCE(p.popularity_recent, 0), COALESCE(p.popularity_count, 0), COALESCE(p.popularity_samples, 0),
			p.licenses, p.groups
		FROM package p
		JOIN repository r ON r.id = p.repository_id
		WHERE r.name = ? AND r.architecture = ? AND p.name = ?`,
		repo, arch, name,
	).Scan(
		&pkg.Name, &pkg.Base, &pkg.Version, &pkg.Description, &pkg.URL,
		&pkg.Repository, &pkg.Architecture, &testing,
		&pkg.BuildDate, &pkg.CompressedSize, &pkg.InstalledSize,
		&pkg.PackagerName, &pkg.PackagerEmail,
		&pkg.Popularity, &pkg.PopularityCount, &pkg.PopularitySamples,
		&licensesJSON, &groupsJSON,
	)
	if err != nil {
		return PackageDetail{}, err
	}

	pkg.Testing = testing != 0
	if licensesJSON.Valid {
		_ = json.Unmarshal([]byte(licensesJSON.String), &pkg.Licenses)
	}
	if groupsJSON.Valid {
		_ = json.Unmarshal([]byte(groupsJSON.String), &pkg.Groups)
	}

	pkgID := r.packageID(ctx, repo, arch, name)
	if pkgID > 0 {
		pkg.Relations = r.loadRelations(ctx, pkgID)
		pkg.InverseRels = r.loadInverseRelations(ctx, name, arch)
	}

	return pkg, nil
}

func (r *Repository) packageID(ctx context.Context, repo, arch, name string) int64 {
	var id int64
	_ = r.db.QueryRowContext(ctx,
		`SELECT p.id FROM package p JOIN repository r ON r.id = p.repository_id
		 WHERE r.name = ? AND r.architecture = ? AND p.name = ?`,
		repo, arch, name).Scan(&id)
	return id
}

func (r *Repository) loadRelations(ctx context.Context, pkgID int64) map[string][]Relation {
	rels := make(map[string][]Relation)
	rows, err := r.db.QueryContext(ctx,
		`SELECT type, target_name, COALESCE(target_version, ''), COALESCE(version_constraint, '')
		 FROM package_relation WHERE package_id = ? ORDER BY type, target_name`, pkgID)
	if err != nil {
		return rels
	}
	defer func() { _ = rows.Close() }()

	for rows.Next() {
		var rel Relation
		var relType string
		if err := rows.Scan(&relType, &rel.TargetName, &rel.TargetVersion, &rel.VersionConstraint); err == nil {
			rels[relType] = append(rels[relType], rel)
		}
	}
	return rels
}

func (r *Repository) loadInverseRelations(ctx context.Context, name, arch string) map[string][]Relation {
	rels := make(map[string][]Relation)
	rows, err := r.db.QueryContext(ctx,
		`SELECT pr.type, p.name
		 FROM package_relation pr
		 JOIN package p ON p.id = pr.package_id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE pr.target_name = ? AND r.architecture = ?
		 ORDER BY pr.type, p.name`, name, arch)
	if err != nil {
		return rels
	}
	defer func() { _ = rows.Close() }()

	for rows.Next() {
		var relType, pkgName string
		if err := rows.Scan(&relType, &pkgName); err == nil {
			rels[relType] = append(rels[relType], Relation{TargetName: pkgName})
		}
	}
	return rels
}

// Resolve finds packages matching a name by direct name match, then by
// provides. Results are ordered like pacman: testing repos first when
// testing is true (matching pacman.conf where testing repos precede
// their non-testing counterparts).
func (r *Repository) Resolve(ctx context.Context, arch, name, version, constraint string, testing bool) []ResolvedPackage {
	order := "r.testing ASC"
	if testing {
		order = "r.testing DESC"
	}

	// 1. Direct name match
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, r.name, r.architecture FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE p.name = ? AND r.architecture = ?
		 ORDER BY `+order, name, arch)
	if err != nil {
		return nil
	}
	defer func() { _ = rows.Close() }()

	var results []ResolvedPackage
	for rows.Next() {
		var rp ResolvedPackage
		if err := rows.Scan(&rp.Name, &rp.Repository, &rp.Arch); err == nil {
			results = append(results, rp)
		}
	}
	if len(results) > 0 {
		return results
	}

	// 2. Provider fallback — filter by version if specified
	query := `SELECT DISTINCT p.name, r.name, r.architecture FROM package_relation pr
		 JOIN package p ON p.id = pr.package_id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE pr.type = 'provides' AND pr.target_name = ? AND r.architecture = ?`
	args := []any{name, arch}
	if version != "" && constraint != "" {
		query += ` AND pr.target_version = ? AND pr.version_constraint = ?`
		args = append(args, version, constraint)
	}
	query += ` ORDER BY ` + order + `, p.name`

	rows2, err := r.db.QueryContext(ctx, query, args...)
	if err != nil {
		return nil
	}
	defer func() { _ = rows2.Close() }()

	for rows2.Next() {
		var rp ResolvedPackage
		if err := rows2.Scan(&rp.Name, &rp.Repository, &rp.Arch); err == nil {
			results = append(results, rp)
		}
	}
	return results
}

func (r *Repository) LoadFiles(ctx context.Context, repo, arch, name string) []string {
	pkgID := r.packageID(ctx, repo, arch, name)
	if pkgID == 0 {
		return nil
	}
	return r.loadFiles(ctx, pkgID)
}

func (r *Repository) loadFiles(ctx context.Context, pkgID int64) []string {
	var fileList sql.NullString
	_ = r.db.QueryRowContext(ctx,
		`SELECT file_list FROM files WHERE package_id = ?`, pkgID).Scan(&fileList)
	if fileList.Valid && fileList.String != "" {
		return strings.Split(fileList.String, "\n")
	}
	return nil
}
