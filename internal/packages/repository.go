package packages

import (
	"context"
	"database/sql"
	"errors"
	"fmt"
	"strings"

	fts "archded/internal/search"
)

// bm25Weights are per FTS5 column: name, base, description, groups, provides, keywords.
// Long AppStream keyword fields increase BM25 document length; description (short pacman
// text) must stay heavily weighted so queries like "browser" still rank packages that
// only match strongly there.
const (
	bm25Name        = 12
	bm25Base        = 5
	bm25Description = 10
	bm25Groups      = 1
	bm25Provides    = 3
	bm25Keywords    = 0.5
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
	Testing       bool
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
		ftsSearch := fts.FTSQuery(search)
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
		bm25 := fmt.Sprintf(
			"bm25(package_fts, %d, %d, %d, %d, %d, %g)",
			bm25Name, bm25Base, bm25Description, bm25Groups, bm25Provides, bm25Keywords,
		)
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, p.description, p.build_date, p.popularity_recent, r.testing
			` + baseWhere + ` ORDER BY (p.name = ?) DESC, ` + bm25 + ` - ln(1 + p.popularity_recent), p.build_date DESC LIMIT ? OFFSET ?`
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
		dataQuery = `SELECT r.name, r.architecture, p.name, p.version, p.description, p.build_date, p.popularity_recent, r.testing
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
		var testing int
		if err := rows.Scan(&p.Repository, &p.Architecture, &p.Name, &p.Version, &p.Description, &p.BuildDate, &p.Popularity, &testing); err != nil {
			return nil, 0, err
		}
		p.Testing = testing != 0
		pkgs = append(pkgs, p)
	}

	return pkgs, total, rows.Err()
}

func (r *Repository) Latest(ctx context.Context, limit int) ([]PackageSummary, error) {
	rows, err := r.db.QueryContext(ctx,
		`SELECT p.name, p.version, p.description, p.build_date,
		        p.packager_name, p.packager_email,
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
		        p.packager_name, r.name, r.architecture
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

func (r *Repository) BuildDate(ctx context.Context, name, repo, arch string) (*int64, error) {
	var buildDate *int64
	err := r.db.QueryRowContext(ctx,
		`SELECT p.build_date FROM package p
		 JOIN repository r ON r.id = p.repository_id
		 WHERE p.name = ? AND r.name = ? AND r.architecture = ?`,
		name, repo, arch).Scan(&buildDate)
	if errors.Is(err, sql.ErrNoRows) {
		return nil, nil
	}
	return buildDate, err
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
