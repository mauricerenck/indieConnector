CREATE TABLE IF NOT EXISTS webmention_outbox (
    id VARCHAR UNIQUE,
    page_uuid VARCHAR NOT NULL,
    target TEXT NOT NULL,
    sent_date TEXT NOT NULL
);
