CREATE TABLE repository (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    architecture TEXT NOT NULL,
    testing INTEGER NOT NULL DEFAULT 0,
    sha256sum TEXT,
    UNIQUE(name, architecture)
);

CREATE TABLE package (
    id INTEGER PRIMARY KEY,
    repository_id INTEGER NOT NULL REFERENCES repository(id),
    name TEXT NOT NULL,
    base TEXT NOT NULL,
    version TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    url TEXT,
    build_date INTEGER NOT NULL DEFAULT 0,
    compressed_size INTEGER NOT NULL DEFAULT 0,
    installed_size INTEGER NOT NULL DEFAULT 0,
    packager_name TEXT,
    packager_email TEXT,
    popularity_recent REAL NOT NULL DEFAULT 0,
    popularity_count INTEGER NOT NULL DEFAULT 0,
    popularity_samples INTEGER NOT NULL DEFAULT 0,
    licenses TEXT,
    groups TEXT,
    provides TEXT,
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
    target_version TEXT,
    version_constraint TEXT
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
    author_link TEXT,
    last_modified INTEGER NOT NULL
);
CREATE INDEX idx_news_last_modified ON news_item(last_modified);

CREATE TABLE release (
    version TEXT PRIMARY KEY,
    available INTEGER NOT NULL DEFAULT 1,
    info TEXT,
    created INTEGER,
    release_date INTEGER,
    kernel_version TEXT,
    file_name TEXT,
    file_length INTEGER,
    sha1_sum TEXT,
    sha256_sum TEXT,
    b2_sum TEXT,
    torrent_url TEXT,
    magnet_uri TEXT
);
CREATE INDEX idx_release_available_date ON release(available, release_date);

CREATE TABLE mirror (
    url TEXT PRIMARY KEY,
    country_code TEXT,
    country_name TEXT,
    last_sync INTEGER,
    delay INTEGER,
    duration_avg REAL,
    duration_stddev REAL,
    score REAL,
    completion_pct REAL,
    ipv4 INTEGER NOT NULL DEFAULT 0,
    ipv6 INTEGER NOT NULL DEFAULT 0,
    popularity_recent REAL DEFAULT 0,
    popularity_count INTEGER DEFAULT 0,
    popularity_samples INTEGER DEFAULT 0
);
CREATE INDEX idx_mirror_last_sync ON mirror(last_sync);
