UPDATE "webmentions" SET mention_type = 'in-reply-to' WHERE mention_type = 'REPLY';
UPDATE "webmentions" SET mention_type = 'like-of' WHERE mention_type = 'LIKE';
UPDATE "webmentions" SET mention_type = 'repost-of' WHERE mention_type = 'REPOST';
UPDATE "webmentions" SET mention_type = 'mention-of' WHERE mention_type = 'MENTION';
