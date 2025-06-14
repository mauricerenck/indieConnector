ALTER TABLE queue RENAME COLUMN sourceUrl TO source_url;
ALTER TABLE queue RENAME COLUMN targetUrl TO target_url;
ALTER TABLE queue RENAME COLUMN queueStatus TO queue_status;
ALTER TABLE queue RENAME COLUMN processLog TO process_log;
