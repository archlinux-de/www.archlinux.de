package packagedetail

import (
	"context"
	"database/sql"
	"encoding/json"
	"strings"

	"archded/internal/vercmp"
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
	Name        string
	Repository  string
	Arch        string
	Description string
	Popularity  float64
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

	var pkgID int64
	err := r.db.QueryRowContext(ctx,
		`SELECT p.id, p.name, p.base, p.version, p.description, COALESCE(p.url, ''),
			r.name, r.architecture, r.testing,
			p.build_date, p.compressed_size, p.installed_size,
			COALESCE(p.packager_name, ''), COALESCE(p.packager_email, ''),
			p.popularity_recent, p.popularity_count, p.popularity_samples,
			p.licenses, p.groups
		FROM package p
		JOIN repository r ON r.id = p.repository_id
		WHERE r.name = ? AND r.architecture = ? AND p.name = ?`,
		repo, arch, name,
	).Scan(
		&pkgID,
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

	pkg.Relations = r.loadRelations(ctx, pkgID)

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

func (r *Repository) LoadInverseRelations(ctx context.Context, name, arch string) map[string][]string {
	rels := make(map[string][]string)
	rows, err := r.db.QueryContext(ctx,
		`SELECT pr.type, p.name
		 FROM package_relation pr
		 JOIN package p ON p.id = pr.package_id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE pr.target_name = ? AND r.architecture = ?
		 ORDER BY pr.type, p.popularity_recent DESC, p.name`, name, arch)
	if err != nil {
		return rels
	}
	defer func() { _ = rows.Close() }()

	for rows.Next() {
		var relType, pkgName string
		if err := rows.Scan(&relType, &pkgName); err == nil {
			rels[relType] = append(rels[relType], pkgName)
		}
	}
	return rels
}

// Resolve finds packages matching a name by direct name match, then by
// provides. Results are ordered like pacman: testing repos first when
// testing is true (matching pacman.conf where testing repos precede
// their non-testing counterparts).
func (r *Repository) Resolve(ctx context.Context, arch, name, version, constraint string) []ResolvedPackage {
	// 1. Direct name match
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, r.name, r.architecture, p.description, p.popularity_recent FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE p.name = ? AND r.architecture = ?
		 ORDER BY r.testing ASC, p.popularity_recent DESC`, name, arch)
	if err != nil {
		return nil
	}
	defer func() { _ = rows.Close() }()

	var results []ResolvedPackage
	for rows.Next() {
		var rp ResolvedPackage
		if err := rows.Scan(&rp.Name, &rp.Repository, &rp.Arch, &rp.Description, &rp.Popularity); err == nil {
			results = append(results, rp)
		}
	}
	if len(results) > 0 {
		return results
	}

	// 2. Provider fallback — fetch all providers, filter by version in Go
	query := `SELECT DISTINCT p.name, r.name, r.architecture, p.description, p.popularity_recent, COALESCE(pr.target_version, '') FROM package_relation pr
		 JOIN package p ON p.id = pr.package_id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE pr.type = 'provides' AND pr.target_name = ? AND r.architecture = ?
		 ORDER BY r.testing ASC, p.popularity_recent DESC, p.name`
	rows2, err := r.db.QueryContext(ctx, query, name, arch)
	if err != nil {
		return nil
	}
	defer func() { _ = rows2.Close() }()

	for rows2.Next() {
		var rp ResolvedPackage
		var providedVersion string
		if err := rows2.Scan(&rp.Name, &rp.Repository, &rp.Arch, &rp.Description, &rp.Popularity, &providedVersion); err == nil {
			if satisfies(providedVersion, version, constraint) {
				results = append(results, rp)
			}
		}
	}
	return results
}

func satisfies(provided, requested, constraint string) bool {
	if requested == "" || constraint == "" {
		return true
	}
	if provided == "" {
		return false
	}
	cmp := vercmp.Vercmp(provided, requested)
	switch constraint {
	case "EQ":
		return cmp == 0
	case "GE":
		return cmp >= 0
	case "LE":
		return cmp <= 0
	case "GT":
		return cmp > 0
	case "LT":
		return cmp < 0
	default:
		return true
	}
}

type PackageSuggestion struct {
	Repository   string
	Architecture string
	Name         string
	Description  string
	Popularity   float64
}

func (r *Repository) Suggest(ctx context.Context, name string, limit int) []PackageSuggestion {
	ftsSearch := ftsQuery(name)
	rows, err := r.db.QueryContext(ctx,
		`SELECT r.name, r.architecture, p.name, p.description, p.popularity_recent
		 FROM package p
		 JOIN package_fts fts ON fts.rowid = p.id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE package_fts MATCH ?
		 ORDER BY rank, p.popularity_recent DESC
		 LIMIT ?`, ftsSearch, limit)
	if err != nil {
		return nil
	}
	defer func() { _ = rows.Close() }()

	var suggestions []PackageSuggestion
	for rows.Next() {
		var s PackageSuggestion
		if err := rows.Scan(&s.Repository, &s.Architecture, &s.Name, &s.Description, &s.Popularity); err == nil {
			suggestions = append(suggestions, s)
		}
	}
	return suggestions
}

func ftsQuery(search string) string {
	search = strings.ReplaceAll(search, `"`, `""`)
	terms := strings.Fields(strings.ReplaceAll(search, "-", " "))
	if len(terms) == 0 {
		return `""`
	}
	var b strings.Builder
	for i, t := range terms {
		if i > 0 {
			b.WriteByte(' ')
		}
		b.WriteByte('"')
		b.WriteString(t)
		b.WriteByte('"')
	}
	b.WriteByte('*')
	return b.String()
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
