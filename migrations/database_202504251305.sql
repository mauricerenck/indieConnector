CREATE TABLE IF NOT EXISTS external_post_urls (
    id VARCHAR UNIQUE,
    page_uuid VARCHAR NOT NULL,
    post_url VARCHAR NOT NULL,
    post_type VARCHAR NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    last_fetched INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS queue_responses (
    id VARCHAR UNIQUE,
    page_uuid VARCHAR NOT NULL,

    response_id VARCHAR NOT NULL,
    response_type VARCHAR NOT NULL,
    response_source VARCHAR NOT NULL,
    response_date VARCHAR NOT NULL,
    response_text TEXT NOT NULL,
    response_url VARCHAR NOT NULL,

    author_id VARCHAR NOT NULL,
    author_name VARCHAR NOT NULL,
    author_username VARCHAR NOT NULL,
    author_avatar VARCHAR NOT NULL,
    author_url VARCHAR NOT NULL,

    queueStatus VARCHAR NOT NULL,
    processLog TEXT,
    retries NUMBER
);

CREATE TABLE IF NOT EXISTS known_responses (
    id VARCHAR UNIQUE,
    post_selector VARCHAR UNIQUE,
    post_url VARCHAR NOT NULL,
    post_type VARCHAR NOT NULL
);
