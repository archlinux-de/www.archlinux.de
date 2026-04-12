DROP TABLE package_fts;

CREATE VIRTUAL TABLE package_fts USING fts5(
    name, base, description, groups, provides,
    content='package', content_rowid='id'
);

INSERT INTO package_fts(package_fts) VALUES('rebuild');

ALTER TABLE package DROP COLUMN keywords;
