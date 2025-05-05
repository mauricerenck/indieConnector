<?php

namespace mauricerenck\IndieConnector;

use Kirby\Uuid\Uuid;
use Kirby\Http\Remote;
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
        $this->enabled = $queueEnabled ?? option('mauricerenck.indieConnector.responses.enabled', false);
        $this->limit = $queueEnabled ?? option('mauricerenck.indieConnector.responses.limit', 10);
        $this->ttl = $queueEnabled ?? option('mauricerenck.indieConnector.responses.ttl', 60);
    }

    public function registerPostUrl(string $pageUuid, string $postUrl, string $postType): void
    {
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

    public function fetchMastodonLikes(array $postUrls, $lastResponses)
    {
        foreach ($postUrls as $postUrl) {
            list($urlHost, $postId) = $this->getPostUrlData($postUrl);
            $knownIds = $this->getKnownIds($lastResponses, 'like-of');

            $response = Remote::get($urlHost . '/api/v1/statuses/' . $postId . '/favourited_by');
            $favs = $response->json();
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

            $selector = str_replace('https://', '', $postUrl) . '_like-of';
            $this->indieDb->upsert('known_responses', ['id', 'post_url', 'post_type', 'post_selector'], [$latestId, $postUrl, 'like-of', $selector], 'post_selector', 'id = "' . $latestId . '"');
        }
    }

    public function fetchMastodonReblogs(array $postUrls, $lastResponses)
    {
        foreach ($postUrls as $postUrl) {
            list($urlHost, $postId) = $this->getPostUrlData($postUrl);
            $knownIds = $this->getKnownIds($lastResponses, 'repost-of');

            $response = Remote::get($urlHost . '/api/v1/statuses/' . $postId . '/reblogged_by');
            $reblogs = $response->json();
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

            $selector = str_replace('https://', '', $postUrl) . '_repost-of';
            $this->indieDb->upsert('known_responses', ['id', 'post_url', 'post_type', 'post_selector'], [$latestId, $postUrl, 'repost-of', $selector], 'post_selector', 'id = "' . $latestId . '"');
        }
    }

    public function fetchMastodonReplies(array $postUrls, $lastResponses)
    {
        foreach ($postUrls as $postUrl) {
            list($urlHost, $postId) = $this->getPostUrlData($postUrl);
            $knownIds = $this->getKnownIds($lastResponses, 'in-reply-to');

            $response = Remote::get($urlHost . '/api/v1/statuses/' . $postId . '/context');
            $replies = $response->json();
            $latestId = $replies['descendants'][0]['id'];

            foreach ($replies['descendants'] as $reply) {

                if (!in_array($reply['id'], $knownIds) && $reply['in_reply_to_id'] === $postId && $reply['visibility'] === 'public') {
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
                } else {
                    break;
                }
            }

            $selector = str_replace('https://', '', $postUrl) . '_in-reply-to';
            $this->indieDb->upsert('known_responses', ['id', 'post_url', 'post_type', 'post_selector'], [$latestId, $postUrl, 'in-reply-to', $selector], 'post_selector', 'id = "' . $latestId . '"');
        }
    }

    public function getPostUrlData(string $postUrl): array
    {
        $urlHost = parse_url($postUrl, PHP_URL_HOST);
        $urlPath = parse_url($postUrl, PHP_URL_PATH);
        $pathElements = explode('/', $urlPath);
        $postId = end($pathElements);

        return [
            $urlHost,
            $postId
        ];
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
