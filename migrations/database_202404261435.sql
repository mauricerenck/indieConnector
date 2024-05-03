CREATE TABLE IF NOT EXISTS queue (
    id VARCHAR UNIQUE,
    sourceUrl VARCHAR NOT NULL,
    targetUrl VARCHAR NOT NULL,
    queueStatus VARCHAR NOT NULL,
    processLog TEXT,
    retries NUMBER
);
