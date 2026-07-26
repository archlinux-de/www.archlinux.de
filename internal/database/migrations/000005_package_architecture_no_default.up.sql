CREATE TABLE package_new (
    id INTEGER PRIMARY KEY,
    repository_id INTEGER NOT NULL REFERENCES repository(id),
    name TEXT NOT NULL,
    base TEXT NOT NULL,
    version TEXT NOT NULL,
    architecture TEXT NOT NULL CHECK (architecture <> ''),
    description TEXT NOT NULL DEFAULT '',
    url TEXT NOT NULL DEFAULT '',
    build_date INTEGER NOT NULL DEFAULT 0,
    compressed_size INTEGER NOT NULL DEFAULT 0,
    installed_size INTEGER NOT NULL DEFAULT 0,
    packager_name TEXT NOT NULL DEFAULT '',
    packager_email TEXT NOT NULL DEFAULT '',
    popularity_recent REAL NOT NULL DEFAULT 0,
    popularity_count INTEGER NOT NULL DEFAULT 0,
    popularity_samples INTEGER NOT NULL DEFAULT 0,
    licenses TEXT NOT NULL DEFAULT '',
    groups TEXT NOT NULL DEFAULT '',
    provides TEXT NOT NULL DEFAULT '',
    UNIQUE(repository_id, name)
);

INSERT INTO package_new (
    id, repository_id, name, base, version, architecture, description, url,
    build_date, compressed_size, installed_size, packager_name, packager_email,
    popularity_recent, popularity_count, popularity_samples, licenses, groups, provides
)
SELECT
    id, repository_id, name, base, version, architecture, description, url,
    build_date, compressed_size, installed_size, packager_name, packager_email,
    popularity_recent, popularity_count, popularity_samples, licenses, groups, provides
FROM package;

CREATE TABLE package_relation_new (
    id INTEGER PRIMARY KEY,
    package_id INTEGER NOT NULL REFERENCES package_new(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    target_name TEXT NOT NULL,
    target_version TEXT NOT NULL DEFAULT '',
    version_constraint TEXT NOT NULL DEFAULT ''
);

INSERT INTO package_relation_new
SELECT id, package_id, type, target_name, target_version, version_constraint
FROM package_relation;

CREATE TABLE files_new (
    package_id INTEGER PRIMARY KEY REFERENCES package_new(id) ON DELETE CASCADE,
    file_list TEXT NOT NULL
);

INSERT INTO files_new SELECT package_id, file_list FROM files;

DROP TABLE package_relation;
DROP TABLE files;
DROP TABLE package;

ALTER TABLE package_new RENAME TO package;
ALTER TABLE package_relation_new RENAME TO package_relation;
ALTER TABLE files_new RENAME TO files;

CREATE INDEX idx_package_name ON package(name);
CREATE INDEX idx_package_build_date ON package(build_date);
CREATE INDEX idx_package_relation_package ON package_relation(package_id);
CREATE INDEX idx_package_relation_target ON package_relation(target_name);
