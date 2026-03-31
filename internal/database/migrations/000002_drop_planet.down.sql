CREATE TABLE planet_feed (
    url TEXT PRIMARY KEY,
    title TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    link TEXT NOT NULL DEFAULT '',
    last_modified INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE planet_item (
    link TEXT PRIMARY KEY,
    feed_url TEXT NOT NULL REFERENCES planet_feed(url) ON DELETE CASCADE,
    title TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    author_name TEXT NOT NULL DEFAULT '',
    author_uri TEXT NOT NULL DEFAULT '',
    last_modified INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_planet_item_last_modified ON planet_item(last_modified);
