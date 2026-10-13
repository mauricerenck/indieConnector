<?php

namespace mauricerenck\IndieConnector;

use Kirby\Uuid\Uuid;
use Kirby\Toolkit\Str;

class ResponseCollector
{
    private IndieConnectorDatabase $indieDb;

    public function __construct(
        private ?bool $enabled = null,
        private ?int $limit = null,
        private ?int $ttl = null,
        private ?int $queueLimit = null,
        private ?IndieConnectorDatabase $indieDatabase = null,
        private ?Mastodon $mastodon = null,

        private ?Bluesky $bluesky = null,
        private ?UrlHandler $urlHandler = null,
    ) {
        $this->mastodon = $mastodon ?? new Mastodon();
        $this->bluesky = $bluesky ?? new Bluesky();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->urlHandler = $urlHandler ?? new UrlHandler();
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.responses.enabled', false);
        $this->limit = $limit ?? option('mauricerenck.indieConnector.responses.limit', 10);
        $this->ttl = $ttl ?? option('mauricerenck.indieConnector.responses.ttl', 60);
        $this->queueLimit = $queueLimit ?? option('mauricerenck.indieConnector.responses.queue.limit', 50);
    }

    public function registerPostUrl(string $pageUuid, string $postUrl, string $postType): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if ($postType === 'bluesky') {
            $postUrl = $this->bluesky->getUrlFromDid($postUrl);
        }

        $existingPostUrls = $this->indieDb->select(
            table: 'external_post_urls',
            fields: ['id'],
            where: 'WHERE page_uuid = "' . $pageUuid . '" AND post_type = "' . $postType . '"'
        );

        if ($existingPostUrls->count() === 0) {
            $id = Uuid::generate();

            $this->indieDb->insert(
                table: 'external_post_urls',
                values: [
                    'id' => $id,
                    'page_uuid' => $pageUuid,
                    'post_url' => $postUrl,
                    'post_type' => $postType,
                    'last_fetched' => '1',
                ]
            );

            return;
        }

        foreach ($existingPostUrls->toArray() as $existingPostUrl) {
            $this->indieDb->update(
                table: 'external_post_urls',
                values: ['post_url' => $postUrl, 'last_fetched' => '1'],
                where: ['id' => $existingPostUrl->id, 'page_uuid' => $pageUuid, 'post_type' => $postType]
            );
        }
    }

    public function getDuePostUrls()
    {
        $postUrls = $this->getDuePostUrlsDbResult();

        if (!$postUrls || $postUrls->count() === 0) {
            return [
                'urls' => 0,
                'responses' => 0
            ];
        }

        $countPostUrls = 0;
        $countResponses = 0;

        $services = ['mastodon', 'bluesky'];
        foreach ($services as $service) {
            $servicePostUrls = $postUrls->filterBy('post_type', $service)->first(); // we only get one resultset here, so we use first()
            if (!is_null($servicePostUrls)) {
                $result = $this->parseResponses($servicePostUrls->post_urls, $service);
                if (!is_null($result)) {
                    $countResponses += $result['responses'];
                    $countPostUrls += $result['urls'];
                }
            }
        }

        return [
            'urls' => $countPostUrls,
            'responses' => $countResponses
        ];
    }

    public function getPostUrlMetrics()
    {
        $postUrls = $this->getPostUrlMetricsDbResult();

        if (!$postUrls || $postUrls->count() === 0) {
            return [
                'total' => 0,
                'mastodon' => 0,
                'bluesky' => 0,
                'due' => 0
            ];
        }

        $mastodonUrls = $postUrls->filterBy('post_type', 'mastodon')->first(); // we only get one resultset here, so we use first()
        $blueskyUrls = $postUrls->filterBy('post_type', 'bluesky')->first(); // we only get one resultset here, so we use first()

        $mastodonUrlCount = $mastodonUrls ? $mastodonUrls->urls : 0;
        $blueskyUrlCount = $blueskyUrls ? $blueskyUrls->urls : 0;

        $dueUrls = $this->getPostUrlMetricsDueUrlDbResult();

        $dueUrls = $dueUrls->first();
        $dueUrlsCount = $dueUrls ? $dueUrls->urls : 0;

        return [
            'total' => $mastodonUrlCount + $blueskyUrlCount,
            'mastodon' => $mastodonUrlCount,
            'bluesky' => $blueskyUrlCount,
            'due' => $dueUrlsCount,
        ];
    }

    public function parseResponses(string $postUrls, string $service)
    {
        if (empty($postUrls)) {
            return [
                'urls' => 0,
                'responses' => 0
            ];
        }

        // get known responses
        $postUrls = explode(',', $postUrls);
        $lastResponses = $this->getLastResponsesFromDb($postUrls);

        switch ($service) {
            case 'bluesky':
                $serviceClass = $this->bluesky;
                break;
            case 'mastodon':
                $serviceClass = $this->mastodon;
                break;
            default:
                return [
                    'urls' => 0,
                    'responses' => 0
                ];
        }

        $cleanedPostUrls = $this->cleanPostUrls($postUrls, $serviceClass);
        if (count($cleanedPostUrls['invalid']) > 0) {
            $this->disablePostUrls($cleanedPostUrls['invalid']);
        }

        $count = 0;
        $responseTypes = $service === 'mastodon' ? ['like-of', 'repost-of', 'in-reply-to'] : ['like-of', 'repost-of', 'mention-of', 'in-reply-to'];
        foreach ($responseTypes as $type) {
            $count += $this->fetchResponseByType(postUrls: $cleanedPostUrls['valid'], lastResponses: $lastResponses, type: $type, serviceClass: $serviceClass);
        }

        $this->updateLastFetched($postUrls);

        return [
            'urls' => count($postUrls),
            'responses' => $count
        ];
    }

    public function fetchResponseByType(array $postUrls, $lastResponses, string $type, $serviceClass)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, $type);

        foreach ($postUrls as $postUrl) {
            $responses = $serviceClass->fetchResponseByType(postUrl: $postUrl, knownIds: $knownIds, type: $type);

            if (count($responses) === 0) {
                continue;
            }

            foreach ($responses['data'] as $response) {
                if ($this->urlHandler->isBlockedSource($response['authorUrl'])) {
                    continue;
                }

                $this->addToQueue(
                    postUrl: $response['postUrl'],
                    responseId: $response['responseId'],
                    responseType: $response['responseType'],
                    responseSource: $response['responseSource'],
                    responseDate: $response['responseDate'],
                    responseText: $response['responseText'],
                    responseUrl: $response['responseUrl'],
                    authorId: $response['authorId'],
                    authorName: $response['authorName'],
                    authorUsername: $response['authorUsername'],
                    authorAvatar: $response['authorAvatar'],
                    authorUrl: $response['authorUrl']
                );
                $count++;
            }

            $this->updateKnownReponses($postUrl, $responses['latestId'], $type);
        }

        return $count;
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

        $pageData = $this->indieDb->select(
            table: 'external_post_urls',
            fields: ['page_uuid'],
            where: 'post_url = "' . $postUrl . '"'
        )->first();

        $id = Uuid::generate();
        $content = Str::unhtml($responseText);

        $this->indieDb->insert(
            table: 'queue_responses',
            values: [
                'id' => $id,
                'page_uuid' => $pageData->page_uuid,
                'response_id' => $responseId,
                'response_type' => $responseType,
                'response_source' => $responseSource,
                'response_date' => $responseDate,
                'response_text' => $content,
                'response_url' => $responseUrl,
                'author_id' => $authorId,
                'author_name' => $authorName,
                'author_username' => $authorUsername,
                'author_avatar' => $authorAvatar,
                'author_url' => $authorUrl,
                'queueStatus' => $queueStatus,
                'retries' => $retries
            ]
        );
    }

    public function processResponses()
    {

        $responses = $this->indieDb->select(
            table: 'queue_responses',
            fields: ['*'],
            where: 'queueStatus = "pending"',
            limit: $this->queueLimit
        );

        if (is_null($responses)) {
            return [];
        }

        return $this->convertToWebmentionHookData($responses);
    }

    public function convertToWebmentionHookData($responses)
    {
        $sourceBaseUrl = $this->getSourceBaseUrl();

        $data = [];
        foreach ($responses as $response) {
            $targetPage = $this->getPageByUuid($response->page_uuid);

            if (is_null($targetPage)) {
                continue;
            }

            $data[] = [
                'id' => $response->id,
                'page_uuid' => 'page://' . $response->page_uuid,
                'type' => $response->response_type,
                'target' => $targetPage->url(),
                'source' => $sourceBaseUrl . $response->id,
                'published' => $response->response_date,
                'title' => $response->response_type,
                'content' => $response->response_text,
                'service' => $response->response_source,
                'author' => [
                    'type' => 'card',
                    'name' => $response->author_name,
                    'avatar' => $response->author_avatar,
                    'url' => $response->author_url,
                ],
            ];
        }

        return $data;
    }

    public function updateLastFetched(array $postUrls)
    {
        $currentTimestamp = time();
        $this->indieDb->update(
            table: 'external_post_urls',
            values: ['last_fetched' => $currentTimestamp],
            where: ['post_url IN ("' . implode('","', $postUrls) . '")']
        );
    }

    public function disablePostUrls(array $postUrls)
    {
        $this->indieDb->update(
            table: 'external_post_urls',
            values: ['active' => false],
            where: ['post_url IN ("' . implode('","', $postUrls) . '")']
        );
    }

    public function markProcessed($responseIds)
    {
        $this->indieDb->update(
            table: 'queue_responses',
            values: ['queueStatus' => 'redirecting'],
            where: ['id IN ("' . implode('","', $responseIds) . '")']
        );
    }

    public function updateKnownReponses($postUrl, $latestId, $verb)
    {
        $selector = str_replace(['https://', 'at://'], ['', ''], $postUrl) . '_' . $verb;

        $this->indieDb->upsert(
            table: 'known_responses',
            values: ['id' => $latestId, 'post_url' => $postUrl, 'post_type' => $verb, 'post_selector' => $selector],
            uniqueField: 'post_selector'
        );
    }

    public function getSingleResponse($responseId)
    {
        $response = $this->indieDb->select(
            table: 'queue_responses',
            fields: ['*'],
            where: 'id = "' . $responseId . '"'
        )->first();
        return $response;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }


    public function cleanPostUrls(array $postUrls, $plattformClass): array
    {
        $validUrls = [];
        $invalidUrls = [];

        foreach ($postUrls as $url) {
            if (!$plattformClass->postExists($url)) {
                $invalidUrls[] = $url;
                continue;
            }

            $validUrls[] =  $url;
        }

        return [
            'valid' => $validUrls,
            'invalid' => $invalidUrls
        ];
    }


    public function getDuePostUrlsDbResult()
    {
        $currentTimestamp = time();
        $timeToFetchAfter = $currentTimestamp - $this->ttl * 60;

        $result = $this->indieDb->select(
            table: 'external_post_urls',
            fields: ['GROUP_CONCAT(post_url, ",") AS post_urls', 'post_type'],
            where: 'active = TRUE AND last_fetched < :lastFetch GROUP BY post_type',
            bindings: ['lastFetch' => $timeToFetchAfter],
            limit: $this->limit
        );
        return $result;
    }

    public function getPostUrlMetricsDbResult()
    {
        $result = $this->indieDb->select(
            table: 'external_post_urls',
            fields: ['COUNT(post_url) AS urls', 'post_type'],
            where: 'active = TRUE GROUP BY post_type'
        );
        return $result;
    }

    public function getPostUrlMetricsDueUrlDbResult()
    {
        $currentTimestamp = time();
        $timeToFetchAfter = $currentTimestamp - $this->ttl * 60;

        $result = $this->indieDb->select(
            table: 'external_post_urls',
            fields: ['COUNT(post_url) AS urls'],
            where: 'active = TRUE AND last_fetched < :lastFetch',
            bindings: ['lastFetch' => $timeToFetchAfter],
            limit: $this->limit
        );
        return $result;
    }

    public function getLastResponsesFromDb(array $postUrls)
    {
        return $this->indieDb->query(
            'SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("' . implode('", "', $postUrls) . '") GROUP BY post_type;'
        );
    }

    protected function getSourceBaseUrl(): string
    {
        return kirby()->url() . '/indieconnector/response/';
    }

    protected function getPageByUuid(string $uuid): ?object
    {
        return page('page://' . $uuid);
    }
}
