CREATE TABLE repository (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    architecture TEXT NOT NULL,
    testing INTEGER NOT NULL DEFAULT 0,
    etag TEXT NOT NULL DEFAULT '',
    UNIQUE(name, architecture)
);

CREATE TABLE package (
    id INTEGER PRIMARY KEY,
    repository_id INTEGER NOT NULL REFERENCES repository(id),
    name TEXT NOT NULL,
    base TEXT NOT NULL,
    version TEXT NOT NULL,
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

CREATE VIRTUAL TABLE package_fts USING fts5(
    name, base, description, groups, provides,
    content='package', content_rowid='id'
);

CREATE INDEX idx_package_name ON package(name);
CREATE INDEX idx_package_build_date ON package(build_date);

CREATE TABLE package_relation (
    id INTEGER PRIMARY KEY,
    package_id INTEGER NOT NULL REFERENCES package(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    target_name TEXT NOT NULL,
    target_version TEXT NOT NULL DEFAULT '',
    version_constraint TEXT NOT NULL DEFAULT ''
);
CREATE INDEX idx_package_relation_package ON package_relation(package_id);
CREATE INDEX idx_package_relation_target ON package_relation(target_name);

CREATE TABLE files (
    package_id INTEGER PRIMARY KEY REFERENCES package(id) ON DELETE CASCADE,
    file_list TEXT NOT NULL
);

CREATE TABLE news_item (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    link TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    author_name TEXT NOT NULL DEFAULT '',
    author_link TEXT NOT NULL DEFAULT '',
    last_modified INTEGER NOT NULL
);
CREATE INDEX idx_news_last_modified ON news_item(last_modified);

CREATE TABLE release (
    version TEXT PRIMARY KEY,
    available INTEGER NOT NULL DEFAULT 1,
    info TEXT NOT NULL DEFAULT '',
    created INTEGER NOT NULL DEFAULT 0,
    release_date INTEGER NOT NULL DEFAULT 0,
    kernel_version TEXT NOT NULL DEFAULT '',
    file_name TEXT NOT NULL DEFAULT '',
    file_length INTEGER NOT NULL DEFAULT 0,
    sha1_sum TEXT NOT NULL DEFAULT '',
    sha256_sum TEXT NOT NULL DEFAULT '',
    b2_sum TEXT NOT NULL DEFAULT '',
    torrent_url TEXT NOT NULL DEFAULT '',
    magnet_uri TEXT NOT NULL DEFAULT '',
    pgp_fingerprint TEXT NOT NULL DEFAULT '',
    wkd_email TEXT NOT NULL DEFAULT ''
);
CREATE INDEX idx_release_available_date ON release(available, release_date);

CREATE TABLE mirror (
    url TEXT PRIMARY KEY,
    country_code TEXT NOT NULL DEFAULT '',
    country_name TEXT NOT NULL DEFAULT '',
    last_sync INTEGER NOT NULL DEFAULT 0,
    delay INTEGER NOT NULL DEFAULT 0,
    duration_avg REAL NOT NULL DEFAULT 0,
    duration_stddev REAL NOT NULL DEFAULT 0,
    score REAL NOT NULL DEFAULT 0,
    completion_pct REAL NOT NULL DEFAULT 0,
    ipv4 INTEGER NOT NULL DEFAULT 0,
    ipv6 INTEGER NOT NULL DEFAULT 0,
    popularity_recent REAL NOT NULL DEFAULT 0,
    popularity_count INTEGER NOT NULL DEFAULT 0,
    popularity_samples INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_mirror_last_sync ON mirror(last_sync);
