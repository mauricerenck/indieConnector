PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

ALTER TABLE known_responses RENAME TO known_responses_old;

CREATE TABLE known_responses (
    id            VARCHAR,
    post_selector VARCHAR UNIQUE,
    post_url      VARCHAR NOT NULL,
    post_type     VARCHAR NOT NULL
);

INSERT INTO known_responses (id, post_selector, post_url, post_type)
SELECT id, post_selector, post_url, post_type
FROM   known_responses_old;

DROP TABLE known_responses_old;

COMMIT;
PRAGMA foreign_keys = ON;
