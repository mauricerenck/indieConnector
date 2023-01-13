CREATE TABLE IF NOT EXISTS settings (
    version VARCHAR NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS webmentions (
    id VARCHAR UNIQUE,
    mention_type VARCHAR NOT NULL,
    mention_date TEXT NOT NULL,
    mention_source TEXT NOT NULL,
    mention_target TEXT NOT NULL,
    mention_image TEXT NOT NULL
);
