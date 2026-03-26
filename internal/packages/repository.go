package packages

import (
	"context"
	"database/sql"
	"strings"
)

type PackageSummary struct {
	Repository    string
	Architecture  string
	Name          string
	Version       string
	Description   string
	BuildDate     int64
	PackagerName  string
	PackagerEmail string
	Popularity    float64
}

type Repository struct {
	db *sql.DB
}

func NewRepository(db *sql.DB) *Repository {
	return &Repository{db: db}
}

func (r *Repository) ListRepositoryNames(ctx context.Context) ([]string, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT DISTINCT name FROM repository ORDER BY name`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var repos []string
	for rows.Next() {
		var name string
		if err := rows.Scan(&name); err != nil {
			return nil, err
		}
		repos = append(repos, name)
	}
	return repos, rows.Err()
}

func (r *Repository) ListArchitectures(ctx context.Context) ([]string, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT DISTINCT architecture FROM repository ORDER BY architecture`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var archs []string
	for rows.Next() {
		var arch string
		if err := rows.Scan(&arch); err != nil {
			return nil, err
		}
		archs = append(archs, arch)
	}
	return archs, rows.Err()
}

func (r *Repository) Search(ctx context.Context, search, repo, arch string, limit, offset int) ([]PackageSummary, int, error) {
	var countQuery, dataQuery string
	var countArgs, dataArgs []any

	if search != "" {
		ftsSearch := ftsQuery(search)
		baseWhere := `FROM package p
			JOIN package_fts fts ON fts.rowid = p.id
			JOIN repository r ON r.id = p.repository_id
			WHERE package_fts MATCH ?`
		countArgs = []any{ftsSearch}
		dataArgs = []any{ftsSearch}

		if repo != "" {
			baseWhere += ` AND r.name = ?`
			countArgs = append(countArgs, repo)
			dataArgs = append(dataArgs, repo)
		}
		if arch != "" {
			baseWhere += ` AND r.architecture = ?`
			countArgs = append(countArgs, arch)
			dataArgs = append(dataArgs, arch)
		}

		countQuery = `SELECT COUNT(*) ` + baseWhere
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, p.description, p.build_date, p.popularity_recent
			` + baseWhere + ` ORDER BY (p.name = ?) DESC, bm25(package_fts, 10, 5, 1, 1, 3) - ln(1 + p.popularity_recent), p.build_date DESC LIMIT ? OFFSET ?`
		dataArgs = append(dataArgs, search, limit, offset)
	} else {
		baseWhere := `FROM package p
			JOIN repository r ON r.id = p.repository_id
			WHERE 1=1`
		if repo != "" {
			baseWhere += ` AND r.name = ?`
			countArgs = append(countArgs, repo)
			dataArgs = append(dataArgs, repo)
		}
		if arch != "" {
			baseWhere += ` AND r.architecture = ?`
			countArgs = append(countArgs, arch)
			dataArgs = append(dataArgs, arch)
		}

		countQuery = `SELECT COUNT(*) ` + baseWhere
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, p.description, p.build_date, p.popularity_recent
			` + baseWhere + ` ORDER BY p.build_date DESC LIMIT ? OFFSET ?`
		dataArgs = append(dataArgs, limit, offset)
	}

	var total int
	if err := r.db.QueryRowContext(ctx, countQuery, countArgs...).Scan(&total); err != nil {
		return nil, 0, err
	}

	rows, err := r.db.QueryContext(ctx, dataQuery, dataArgs...)
	if err != nil {
		return nil, 0, err
	}
	defer func() { _ = rows.Close() }()

	var pkgs []PackageSummary
	for rows.Next() {
		var p PackageSummary
		if err := rows.Scan(&p.Repository, &p.Architecture, &p.Name, &p.Version, &p.Description, &p.BuildDate, &p.Popularity); err != nil {
			return nil, 0, err
		}
		pkgs = append(pkgs, p)
	}

	return pkgs, total, rows.Err()
}

func (r *Repository) Latest(ctx context.Context, limit int) ([]PackageSummary, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, p.version, p.description, p.build_date,
		        COALESCE(p.packager_name, ''), COALESCE(p.packager_email, ''),
		        r.name, r.architecture
		 FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 ORDER BY p.build_date DESC LIMIT ?`, limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var pkgs []PackageSummary
	for rows.Next() {
		var p PackageSummary
		if err := rows.Scan(&p.Name, &p.Version, &p.Description, &p.BuildDate, &p.PackagerName, &p.PackagerEmail, &p.Repository, &p.Architecture); err != nil {
			return nil, err
		}
		pkgs = append(pkgs, p)
	}
	return pkgs, rows.Err()
}

func (r *Repository) LatestStable(ctx context.Context, limit int) ([]PackageSummary, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, p.version, p.description, p.build_date,
		        COALESCE(p.packager_name, ''), r.name, r.architecture
		 FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE r.testing = 0
		 ORDER BY p.build_date DESC LIMIT ?`, limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var pkgs []PackageSummary
	for rows.Next() {
		var p PackageSummary
		if err := rows.Scan(&p.Name, &p.Version, &p.Description, &p.BuildDate, &p.PackagerName, &p.Repository, &p.Architecture); err != nil {
			return nil, err
		}
		pkgs = append(pkgs, p)
	}
	return pkgs, rows.Err()
}

func (r *Repository) BuildDate(ctx context.Context, name, repo, arch string) *int64 {
	var buildDate *int64
	_ = r.db.QueryRowContext(ctx,
		`SELECT p.build_date FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE p.name = ? AND r.name = ? AND r.architecture = ?`,
		name, repo, arch).Scan(&buildDate)
	return buildDate
}

type PackageRef struct {
	Name         string
	Repository   string
	Architecture string
	BuildDate    int64
}

func (r *Repository) AllStableRefs(ctx context.Context) ([]PackageRef, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, r.name, r.architecture, p.build_date
		 FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE r.testing = 0`)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var refs []PackageRef
	for rows.Next() {
		var ref PackageRef
		if err := rows.Scan(&ref.Name, &ref.Repository, &ref.Architecture, &ref.BuildDate); err != nil {
			return nil, err
		}
		refs = append(refs, ref)
	}
	return refs, rows.Err()
}

func (r *Repository) Suggest(ctx context.Context, term string, limit int) ([]string, error) {
	if term == "" {
		return nil, nil
	}

	rows, err := r.db.QueryContext(ctx,
		`SELECT DISTINCT p.name
		 FROM package p
		 WHERE p.name LIKE ? ESCAPE '\'
		 ORDER BY p.popularity_recent DESC
		 LIMIT ?`, likePrefixQuery(term), limit)
	if err != nil {
		return nil, err
	}
	defer func() { _ = rows.Close() }()

	var names []string
	for rows.Next() {
		var name string
		if err := rows.Scan(&name); err != nil {
			return nil, err
		}
		names = append(names, name)
	}
	return names, rows.Err()
}

func likePrefixQuery(term string) string {
	term = strings.ReplaceAll(term, `\`, `\\`)
	term = strings.ReplaceAll(term, `%`, `\%`)
	term = strings.ReplaceAll(term, `_`, `\_`)
	return term + "%"
}

// ftsQuery builds an FTS5 MATCH expression from a user search string.
// Splits on hyphens, quotes each term, and adds a prefix wildcard to the last term.
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
