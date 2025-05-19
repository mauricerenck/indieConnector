<?php

namespace mauricerenck\IndieConnector;

use Kirby\Uuid\Uuid;
use Kirby\Toolkit\Str;

class ResponseCollector
{
    private $indieDb;

    public function __construct(
        private ?bool $enabled = null,
        private ?int $limit = null,
        private ?int $ttl = null,
        private ?IndieConnectorDatabase $indieDatabase = null,
    ) {
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.responses.enabled', false);
        $this->limit = $limit ?? option('mauricerenck.indieConnector.responses.limit', 10);
        $this->ttl = $ttl ?? option('mauricerenck.indieConnector.responses.ttl', 60);
    }

    public function registerPostUrl(string $pageUuid, string $postUrl, string $postType): void
    {

        if (!$this->isEnabled()) {
            return;
        }

        $existingPostUrls = $this->indieDb->select(
            'external_post_urls',
            ['id'],
            'WHERE page_uuid = "' . $pageUuid . '" AND post_type = "' . $postType . '"'
        );

        if ($existingPostUrls->count() === 0) {
            $id = Uuid::generate();
            $fields = ['id', 'page_uuid', 'post_url', 'post_type'];
            $values = [$id, $pageUuid, $postUrl, $postType];

            $this->indieDb->insert('external_post_urls', $fields, $values);

            return;
        }

        foreach ($existingPostUrls->toArray() as $existingPostUrl) {
            $this->indieDb->update(
                'external_post_urls',
                ['post_url'],
                [$postUrl],
                'WHERE id = "' . $existingPostUrl->id . '" AND page_uuid = "' . $pageUuid . '" AND post_type = "' . $postType . '"'
            );
        }
    }

    public function getDuePostUrls()
    {
        $currentTimestamp = time();
        $timeToFetchAfter = $currentTimestamp - $this->ttl * 60;
        $limitQuery = $this->limit > 0 ? ' LIMIT ' . $this->limit : '';
        $query = 'SELECT GROUP_CONCAT(post_url, ",") AS post_urls, post_type FROM external_post_urls WHERE active = TRUE AND UNIXEPOCH(last_fetched) < ' . $timeToFetchAfter . ' GROUP BY post_type ' . $limitQuery;

        $postUrls = $this->indieDb->query($query);

        $this->parseMastodonResponses($postUrls->filterBy('post_type', 'mastodon')->first()->post_urls); // we only get one resultset here, so we use first()
        $this->parseBlueskyResponses($postUrls->filterBy('post_type', 'bluesky')->first()->post_urls); // we only get one resultset here, so we use first()
    }

    public function parseMastodonResponses(string $postUrls)
    {
        // get known responses
        $postUrls = explode(',', $postUrls);
        $lastResponses = $this->indieDb->query(
            'SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("' . implode('", "', $postUrls) . '") GROUP BY post_type;'
        );

        $this->fetchMastodonLikes($postUrls, $lastResponses);
        $this->fetchMastodonReblogs($postUrls, $lastResponses);
        $this->fetchMastodonReplies($postUrls, $lastResponses);
    }

    public function parseBlueskyResponses(string $postUrls)
    {
        // get known responses
        $postUrls = explode(',', $postUrls);
        $lastResponses = $this->indieDb->query(
            'SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("' . implode('", "', $postUrls) . '") GROUP BY post_type;'
        );

        $this->fetchBlueskyLikes($postUrls, $lastResponses);
        $this->fetchBlueskyReposts($postUrls, $lastResponses);
        $this->fetchBlueskyQuotes($postUrls, $lastResponses);
        $this->fetchBlueskyReplies($postUrls, $lastResponses);
    }

    public function fetchMastodonLikes(array $postUrls, $lastResponses)
    {
        $mastodonReceiver = new MastodonReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'like-of');

        foreach ($postUrls as $postUrl) {
            $favs = $mastodonReceiver->getResponses($postUrl, 'likes', $knownIds);

            if (count($favs) === 0) {
                continue;
            }

            $latestId = $favs[0]['id'];

            foreach ($favs as $fav) {
                if (!in_array($fav['id'], $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $fav['id'], // 'response_id' likes dont have ids use author id instead
                        responseType: 'like-of',
                        responseSource: 'mastodon',
                        responseDate: $fav['created_at'],
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $fav['id'],
                        authorName: $fav['display_name'],
                        authorUsername: $fav['username'],
                        authorAvatar: $fav['avatar_static'],
                        authorUrl: $fav['url']
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'like-of');
        }
    }

    public function fetchMastodonReblogs(array $postUrls, $lastResponses)
    {
        $mastodonReceiver = new MastodonReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'repost-of');

        foreach ($postUrls as $postUrl) {
            $reblogs = $mastodonReceiver->getResponses($postUrl, 'reposts', $knownIds);

            if (count($reblogs) === 0) {
                continue;
            }

            $latestId = $reblogs[0]['id'];

            foreach ($reblogs as $repost) {
                if (!in_array($repost['id'], $knownIds)) {

                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $repost['id'], // 'response_id' likes dont have ids use author id instead
                        responseType: 'repost-of',
                        responseSource: 'mastodon',
                        responseDate: $repost['created_at'],
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $repost['id'],
                        authorName: $repost['display_name'],
                        authorUsername: $repost['username'],
                        authorAvatar: $repost['avatar_static'],
                        authorUrl: $repost['url']
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'repost-of');
        }
    }

    public function fetchMastodonReplies(array $postUrls, $lastResponses)
    {
        $mastodonReceiver = new MastodonReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'in-reply-to');

        foreach ($postUrls as $postUrl) {
            $replies = $mastodonReceiver->getResponses($postUrl, 'replies', $knownIds);
            list($_urlHost, $postId) = $mastodonReceiver->getPostUrlData($postUrl);

            if (count($replies) === 0) {
                continue;
            }

            $latestId = $replies[0]['id'];

            foreach ($replies as $reply) {
                if (!in_array($reply['id'], $knownIds)) {

                    if ($reply['in_reply_to_id'] === $postId && $reply['visibility'] === 'public') {
                        $this->addToQueue(
                            postUrl: $postUrl,
                            responseId: $reply['id'],
                            responseType: 'in-reply-to',
                            responseSource: 'mastodon',
                            responseDate: $reply['created_at'],
                            responseText: $reply['content'],
                            responseUrl: $reply['url'],
                            authorId: $reply['account']['id'],
                            authorName: $reply['account']['display_name'],
                            authorUsername: $reply['account']['username'],
                            authorAvatar: $reply['account']['avatar_static'],
                            authorUrl: $reply['account']['url']
                        );
                    }
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'in-reply-to');
        }
    }

    public function fetchBlueskyLikes(array $postUrls, $lastResponses)
    {
        $bskReceiver = new BlueskyReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'like-of');

        foreach ($postUrls as $postUrl) {
            $likes = $bskReceiver->getResponses($postUrl, 'likes', $knownIds);

            if (count($likes) === 0) {
                continue;
            }

            $latestId = $likes[0]->indieConnectorId;

            foreach ($likes as $like) {
                $displayName = (!empty($like->actor->displayName)) ? $like->actor->displayName : $like->actor->handle;

                if (!in_array($like->indieConnectorId, $knownIds)) {
                    $avatar = isset($like->actor->avatar) ? $like->actor->avatar : '';

                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $like->indieConnectorId,
                        responseType: 'like-of',
                        responseSource: 'bluesky',
                        responseDate: $like->createdAt,
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $like->actor->did,
                        authorName: $displayName,
                        authorUsername: $like->actor->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $like->actor->handle
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'like-of');
        }
    }

    public function fetchBlueskyReposts(array $postUrls, $lastResponses)
    {
        $bskReceiver = new BlueskyReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'repost-of');

        foreach ($postUrls as $postUrl) {
            $reposts = $bskReceiver->getResponses($postUrl, 'reposts', $knownIds);
            $latestId = $reposts[0]->indieConnectorId;

            if (count($reposts) === 0) {
                continue;
            }

            foreach ($reposts as $repost) {
                $displayName = (!empty($repost->displayName)) ? $repost->displayName : $repost->handle;
                $avatar = isset($repost->avatar) ? $repost->avatar : '';

                if (!in_array($repost->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $repost->indieConnectorId,
                        responseType: 'repost-of',
                        responseSource: 'bluesky',
                        responseDate: $repost->createdAt,
                        responseUrl: $postUrl,
                        authorId: $repost->did,
                        authorName: $displayName,
                        authorUsername: $repost->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $repost->handle
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'repost-of');
        }
    }

    public function fetchBlueskyQuotes(array $postUrls, $lastResponses)
    {
        $bskReceiver = new BlueskyReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'mention-of');

        foreach ($postUrls as $postUrl) {
            $quotes = $bskReceiver->getResponses($postUrl, 'quotes', $knownIds);
            $latestId = $quotes[0]->indieConnectorId;

            if (count($quotes) === 0) {
                continue;
            }

            foreach ($quotes as $quote) {
                $displayName = (!empty($quote->author->displayName)) ? $quote->author->displayName : $quote->author->handle;
                $avatar = isset($quote->author->avatar) ? $quote->author->avatar : '';

                if (!in_array($quote->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $quote->indieConnectorId, // 'response_id' likes dont have ids
                        responseType: 'mention-of',
                        responseSource: 'bluesky',
                        responseDate: $quote->record->createdAt,
                        responseText: $quote->record->text ?? '',
                        responseUrl: $quote->uri,
                        authorId: $quote->author->did,
                        authorName: $displayName,
                        authorUsername: $quote->author->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $quote->author->handle
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'mention-of');
        }
    }

    public function fetchBlueskyReplies(array $postUrls, $lastResponses)
    {
        $bskReceiver = new BlueskyReceiver();
        $knownIds = $this->getKnownIds($lastResponses, 'in-reply-to');

        foreach ($postUrls as $postUrl) {
            $replies = $bskReceiver->getResponses($postUrl, 'replies', $knownIds);
            $latestId = $replies[0]->indieConnectorId;

            if (count($replies) === 0) {
                continue;
            }

            foreach ($replies as $reply) {
                $displayName = (!empty($reply->post->author->displayName)) ? $reply->post->author->displayName : $reply->post->author->handle;
                $avatar = isset($reply->post->author->avatar) ? $reply->post->author->avatar : '';

                if (!in_array($reply->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $reply->indieConnectorId, // 'response_id' likes dont have ids
                        responseType: 'in-reply-to',
                        responseSource: 'bluesky',
                        responseDate: $reply->post->record->createdAt,
                        responseText: $reply->post->record->text ?? '',
                        responseUrl: $reply->post->uri,
                        authorId: $reply->post->author->did,
                        authorName: $displayName,
                        authorUsername: $reply->post->author->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $reply->post->author->handle
                    );
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'in-reply-to');
        }
    }


    public function getKnownIds($lastResponses, $verb): array
    {
        $idList = $lastResponses->filterBy('post_type', $verb)->first();
        return !is_null($idList) ? explode(',', $idList->ids) : [];
    }

    public function addToQueue(
        string $postUrl,
        string $responseId,
        string $responseType,
        string $responseSource,
        string $responseDate,
        string $authorId,
        string $authorName,
        string $authorUsername,
        string $authorAvatar,
        string $authorUrl,
        string $responseText = '',
        string $responseUrl = '',
        string $queueStatus = 'pending',
        int $retries = 0
    ) {

        $pageData = $this->indieDb->select('external_post_urls', ['page_uuid'], 'WHERE post_url = "' . $postUrl . '"')->first();

        $fields = ['id', 'page_uuid', 'response_id', 'response_type', 'response_source', 'response_date', 'response_text', 'response_url', 'author_id', 'author_name', 'author_username', 'author_avatar', 'author_url', 'queueStatus', 'retries'];
        $id = Uuid::generate();
        $content = Str::unhtml($responseText);

        $values = [
            $id,
            $pageData->page_uuid,
            $responseId,
            $responseType,
            $responseSource,
            $responseDate,
            $content,
            $responseUrl,
            $authorId,
            $authorName,
            $authorUsername,
            $authorAvatar,
            $authorUrl,
            $queueStatus,
            $retries
        ];

        $this->indieDb->insert('queue_responses', $fields, $values);
    }

    public function processResponses($limit = 100)
    {
        $responses = $this->indieDb->select('queue_responses', ['*'], 'WHERE queueStatus = "pending" LIMIT ' . $limit);
        return $responses;
    }

    public function markProcessed($responseIds)
    {
        $this->indieDb->update('queue_responses', ['queueStatus'], ['success'], 'WHERE id IN ("' . implode('","', $responseIds) . '")');
    }

    public function updateKnownReponses($postUrl, $latestId, $verb)
    {
        $selector = str_replace(['https://', 'at://'], ['', ''], $postUrl) . '_' . $verb;
        $this->indieDb->upsert('known_responses', ['id', 'post_url', 'post_type', 'post_selector'], [$latestId, $postUrl, $verb, $selector], 'post_selector', 'id = "' . $latestId . '"');
    }

    public function removeFromQueue($responseId)
    {
        $this->indieDb->delete('queue_responses', 'WHERE id = "' . $responseId . '" AND queueStatus = "success"');
    }

    public function getSingleResponse($responseId)
    {
        $response = $this->indieDb->select('queue_responses', ['*'], 'WHERE id = "' . $responseId . '"')->first();
        return $response;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }
}
