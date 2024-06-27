CREATE TABLE IF NOT EXISTS deleted_pages (
    id VARCHAR UNIQUE,
    slug VARCHAR NOT NULL,
    deletedAt TEXT NOT NULL
);
