ALTER TABLE package ADD COLUMN categories TEXT NOT NULL DEFAULT '';

DROP TABLE package_fts;

CREATE VIRTUAL TABLE package_fts USING fts5(
    name, base, description, groups, provides, keywords, categories,
    content='package', content_rowid='id'
);

INSERT INTO package_fts(package_fts) VALUES('rebuild');
