CREATE TABLE IF NOT EXISTS outbox (
    id TEXT PRIMARY KEY,
    page_uuid TEXT,
    target TEXT,
    updates INTEGER,
    status TEXT DEFAULT 'success',
    created_at TEXT,
    updated_at TEXT
);

INSERT INTO outbox (id, page_uuid, target, created_at, updates)
SELECT
    MIN(id) AS id,  -- Just picking one id for the group
    page_uuid,
    target,
    MIN(sent_date) AS sent_date,  -- Picking the earliest date for the entry
    COUNT(*) AS updates
FROM
    webmention_outbox
GROUP BY
    target;

-- DROP TABLE webmention_outbox;
