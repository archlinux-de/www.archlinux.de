package packagedetail

import (
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"

	"www/internal/ui/layout"
)

type Handler struct {
	db       *sql.DB
	manifest *layout.Manifest
}

func NewHandler(db *sql.DB, manifest *layout.Manifest) *Handler {
	return &Handler{db: db, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/{repo}/{arch}/{name}", h.show)
}

type packageDetail struct {
	Name           string
	Base           string
	Version        string
	Description    string
	URL            string
	Repository     string
	Architecture   string
	Testing        bool
	BuildDate      int64
	CompressedSize int64
	InstalledSize  int64
	PackagerName   string
	PackagerEmail  string
	Licenses       []string
	Groups         []string
	Relations      map[string][]relation
	Files          []string
}

type relation struct {
	TargetName        string
	TargetVersion     string
	VersionConstraint string
}

func (h *Handler) show(w http.ResponseWriter, r *http.Request) {
	repoName := r.PathValue("repo")
	arch := r.PathValue("arch")
	pkgName := r.PathValue("name")
	ctx := r.Context()

	var pkg packageDetail
	var licensesJSON, groupsJSON sql.NullString
	var testing int

	err := h.db.QueryRowContext(ctx,
		`SELECT p.name, p.base, p.version, COALESCE(p.description, ''), COALESCE(p.url, ''),
			r.name, r.architecture, r.testing,
			COALESCE(p.build_date, 0), COALESCE(p.compressed_size, 0), COALESCE(p.installed_size, 0),
			COALESCE(p.packager_name, ''), COALESCE(p.packager_email, ''),
			p.licenses, p.groups
		FROM package p
		JOIN repository r ON r.id = p.repository_id
		WHERE r.name = ? AND r.architecture = ? AND p.name = ?`,
		repoName, arch, pkgName,
	).Scan(
		&pkg.Name, &pkg.Base, &pkg.Version, &pkg.Description, &pkg.URL,
		&pkg.Repository, &pkg.Architecture, &testing,
		&pkg.BuildDate, &pkg.CompressedSize, &pkg.InstalledSize,
		&pkg.PackagerName, &pkg.PackagerEmail,
		&licensesJSON, &groupsJSON,
	)
	if err == sql.ErrNoRows {
		http.NotFound(w, r)
		return
	}
	if err != nil {
		layout.ServerError(w, "query package", err)
		return
	}

	pkg.Testing = testing != 0

	if licensesJSON.Valid {
		_ = json.Unmarshal([]byte(licensesJSON.String), &pkg.Licenses)
	}
	if groupsJSON.Valid {
		_ = json.Unmarshal([]byte(groupsJSON.String), &pkg.Groups)
	}

	// Load relations
	pkg.Relations = make(map[string][]relation)
	var pkgID int64
	_ = h.db.QueryRowContext(ctx,
		`SELECT p.id FROM package p JOIN repository r ON r.id = p.repository_id
		 WHERE r.name = ? AND r.architecture = ? AND p.name = ?`,
		repoName, arch, pkgName,
	).Scan(&pkgID)

	relRows, err := h.db.QueryContext(ctx,
		`SELECT type, target_name, COALESCE(target_version, ''), COALESCE(version_constraint, '')
		 FROM package_relation WHERE package_id = ? ORDER BY type, target_name`,
		pkgID,
	)
	if err == nil {
		defer relRows.Close()
		for relRows.Next() {
			var r relation
			var relType string
			if err := relRows.Scan(&relType, &r.TargetName, &r.TargetVersion, &r.VersionConstraint); err == nil {
				pkg.Relations[relType] = append(pkg.Relations[relType], r)
			}
		}
	}

	// Load files
	var fileList sql.NullString
	_ = h.db.QueryRowContext(ctx,
		`SELECT file_list FROM files WHERE package_id = ?`, pkgID,
	).Scan(&fileList)
	if fileList.Valid && fileList.String != "" {
		pkg.Files = strings.Split(fileList.String, "\n")
	}

	// Load inverse relations (packages that depend on this one)
	inverseRels := make(map[string][]relation)
	invRows, err := h.db.QueryContext(ctx,
		`SELECT pr.type, p.name
		 FROM package_relation pr
		 JOIN package p ON p.id = pr.package_id
		 JOIN repository r ON r.id = p.repository_id
		 WHERE pr.target_name = ? AND r.architecture = ?
		 ORDER BY pr.type, p.name`,
		pkgName, arch,
	)
	if err == nil {
		defer invRows.Close()
		for invRows.Next() {
			var relType, name string
			if err := invRows.Scan(&relType, &name); err == nil {
				inverseRels[relType] = append(inverseRels[relType], relation{TargetName: name})
			}
		}
	}

	page := layout.Page{
		Title:       pkg.Name,
		Description: pkg.Description,
		Path:        r.URL.Path,
		Manifest:    h.manifest,
		NoIndex:     pkg.Testing,
	}

	layout.Render(w, r, page, PackageDetail(pkg, inverseRels))
}
