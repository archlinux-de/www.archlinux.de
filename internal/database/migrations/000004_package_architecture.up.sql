ALTER TABLE package ADD COLUMN architecture TEXT NOT NULL DEFAULT 'x86_64';

UPDATE repository SET etag = '';
