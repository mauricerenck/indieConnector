<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Toolkit\Collection;
use Kirby\Data\Json;
use Kirby\Uuid\Uuid;
use Kirby\Http\Url;

use Throwable;

class Outbox
{
    private $outboxVersion = 3;
    private IndieConnectorDatabase $indieDb;
    protected OptionHandler $options;

    public function __construct(
        private ?string $outboxFilename = null,
        ?IndieConnectorDatabase $indieDatabase = null,
        ?OptionHandler $options = null,
    ) {
        $this->options = $options ?? new OptionHandler();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();

        $this->outboxFilename =
            $outboxFilename ?? option('mauricerenck.indieConnector.send.outboxFilename', 'indieConnector.json');
    }

    /**
     * Adds one URL as target to the outbox
     *
     * The URL is written and its status will be pending
     * @param string $pageUuid the uuid->id() of the page
     * @param string $pageUrl the full URL of the page
     * @param string $targetUrl the full URL of the target
     * @return void
     */
    public function addWebmentionTarget(string $pageUuid, string $pageUrl, string $targetUrl): void
    {
        FlowDebug::step('sendWebmentions', $pageUuid, 'addWebmentionTarget', 'info', '', $targetUrl);

        $uuid = $this->createUuid();
        $this->indieDb->insert(
            table: 'webmentions_sent',
            values: [
                'id' => $uuid,
                'page_uuid' => $pageUuid,
                'target_url' => $targetUrl,
                'source_url' => $pageUrl,
                'updates' => 0,
                'status' => WebmentionOutgoingStatus::Pending->value,
            ]
        );
    }

    /**
     * Get an array of all registered webmention target URLs for a single page
     *
     * @param string $pageUuid the uuid->id() of the page
     * @return array
     */
    public function getRegisteredWebmentionTargets(string $pageUuid, ?WebmentionOutgoingStatus $statusFilter = null): array
    {
        try {
            FlowDebug::step('sendWebmentions', $pageUuid, 'getRegisteredWebmentionTargets', 'info', '', $statusFilter);

            $registeredUrls = $this->indieDb->select(
                table: 'webmentions_sent',
                fields: ['target_url', 'status'],
                where: 'page_uuid = :uuid',
                bindings: ['uuid' => $pageUuid]
            );

            if (!$registeredUrls) {
                return [];
            }

            if (isset($statusFilter)) {
                $registeredUrls = $registeredUrls->filterBy('status', $statusFilter->value);
            }

            $registeredUrlsArray = array_column($registeredUrls->toArray(), 'target_url');

            return $registeredUrlsArray;
        } catch (Throwable $_e) {
            return [];
        }
    }

    /**
     * Get an array of all registered and pending/failed webmention target URLs for a single page
     *
     * @param string $pageUuid the uuid->id() of the page
     * @return array
     */
    public function getPendingWebmentionTargets(string $pageUuid): array
    {
        try {
            FlowDebug::step('sendWebmentions', $pageUuid, 'getPendingWebmentionTargets', 'info', '');
            $opt_maxRetries = $this->options->get('send.maxRetries', 3);

            [$in, $statusBindings] = $this->indieDb->inClause('status', [
                WebmentionOutgoingStatus::Pending,
                WebmentionOutgoingStatus::Failed,
            ]);

            $registeredUrls = $this->indieDb->select(
                table: 'webmentions_sent',
                fields: ['target_url', 'status'],
                where: "page_uuid = :uuid AND status IN ({$in}) AND updates < :updates",
                bindings: ['uuid' => $pageUuid, 'updates' => $opt_maxRetries] + $statusBindings,
            );

            if (!$registeredUrls) {
                FlowDebug::step('sendWebmentions', $pageUuid, 'getPendingWebmentionTargets', 'info', 'No registered URLs');
                return [];
            }

            $registeredUrlsArray = array_column($registeredUrls->toArray(), 'target_url');
            FlowDebug::step('sendWebmentions', $pageUuid, 'getPendingWebmentionTargets', 'info', 'Found registered URLs', $registeredUrlsArray);

            return $registeredUrlsArray;
        } catch (Throwable $_e) {
            return [];
        }
    }

    /**
     * Updates a webmention target status
     *
     * Set a status, each change will automatically increase the update count
     * @param string $pageUuid the uuid->id() of the page
     * @param string $targetUrl the full URL of the target
     * @param WebmentionOutgoingStatus $newStatus
     * @return void
     */
    public function updateWebmentionTargetStatus(string $pageUuid, string $targetUrl, WebmentionOutgoingStatus $newStatus): void
    {
        FlowDebug::step('sendWebmentions', $pageUuid, 'updateWebmentionTargetStatus', 'info', '', [$pageUuid, $targetUrl, $newStatus]);

        $this->indieDb->update(
            table: 'webmentions_sent',
            values: [
                'status' => $newStatus->value,
            ],
            where: 'page_uuid = :pageUuid AND target_url = :targetUrl',
            bindings: [
                'pageUuid' => $pageUuid,
                'targetUrl' => $targetUrl
            ]
        );
    }

    /**
     * Updates a webmention target
     *
     * @param string $pageUuid
     * @param string $targetUrl
     * @param array $updates
     * @return void
     */
    public function updateWebmentionTarget(string $pageUuid, string $targetUrl, array $updates): void
    {
        FlowDebug::step('outbox', $pageUuid, 'updateWebmentionTarget', 'info', '', [$targetUrl, $updates]);

        $this->indieDb->update(
            table: 'webmentions_sent',
            values: $updates,
            where: 'page_uuid = :pageUuid AND target_url = :targetUrl',
            bindings: [
                'pageUuid' => $pageUuid,
                'targetUrl' => $targetUrl
            ]
        );
    }


    /**
     * Adds a received Webmention to the database
     *
     * Set a status (PENDING) will return the newly generated uuid of the webmention
     * @param string $pageUuid the uuid->id() of the page
     * @param string $pageUrl the full URL of the target
     * @param string $sourceUrl the full URL of the source
     * @return string|bool UUID of the webmention or false
     */
    public function addWebmentionSource(string $pageUuid, string $pageUrl, string $sourceUrl): string|bool
    {
        FlowDebug::step('receiveWebmentions', $pageUuid, 'outbox:addWebmentionSource', 'info', '', $sourceUrl);

        $uuid = $this->createUuid();

        $result = $this->indieDb->insert(
            table: 'webmentions_received',
            values: [
                'id' => $uuid,
                'page_uuid' => $pageUuid,
                'target_url' => $pageUrl,
                'source_url' => $sourceUrl,
                'status' => WebmentionIncomingStatus::Pending->value
            ]
        );

        if ($result === false) {
            return false;
        }

        return $uuid;
    }

    /**
     * Updates a webmention source status
     *
     * @param string $webmentionId
     * @param WebmentionIncomingStatus $newStatus
     * @return void
     */
    public function updateWebmentionSourceStatus(string $webmentionId, WebmentionIncomingStatus $newStatus): void
    {
        FlowDebug::step('outbox', $webmentionId, 'updateWebmentionSourceStatus', 'info', '', [$webmentionId, $newStatus]);

        $this->indieDb->update(
            table: 'webmentions_received',
            values: [
                'status' => $newStatus->value,
            ],
            where: 'id = :webmentionId',
            bindings: [
                'webmentionId' => $webmentionId
            ]
        );
    }

    /**
     * Updates a webmention source
     *
     * @param string $webmentionId
     * @param array $updates
     * @return void
     */
    public function updateWebmentionSource(string $webmentionId, array $updates): void
    {
        FlowDebug::step('outbox', $webmentionId, 'updateWebmentionSource', 'info', '', [$webmentionId, $updates]);

        $this->indieDb->update(
            table: 'webmentions_received',
            values: $updates,
            where: 'id = :webmentionId',
            bindings: [
                'webmentionId' => $webmentionId
            ]
        );
    }

    /**
     * Get a single webmentions by its id
     *
     * @param string $webmentionId the id of the webmention
     * @return mixed
     */
    public function getIncomingWebmentionById(string $webmentionId): mixed
    {
        try {
            FlowDebug::step('outbox', $webmentionId, 'getIncomingWebmentionById', 'info', '');

            $webmentions = $this->indieDb->select(
                table: 'webmentions_received',
                fields: ['*'],
                where: "id = :uuid",
                bindings: ['uuid' => $webmentionId],
            );

            if (!$webmentions) {
                FlowDebug::step('outbox', $webmentionId, 'getIncomingWebmentionById', 'info', 'Webmention not found');
                return null;
            }

            return $webmentions->first();
        } catch (Throwable $_e) {
            return null;
        }
    }

    /**
     * Gets all webmentions of a page
     *
     * @param string $pageUuid the uuid of the page
     * @return Collection|null
     */
    public function getIncomingWebmentionsByPageUuid(string $pageUuid): Collection|null
    {
        try {
            FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionByPageUuid', 'info', '');

            $webmentions = $this->indieDb->select(
                table: 'webmentions_received',
                fields: ['*'],
                where: "page_uuid = :uuid",
                bindings: ['uuid' => $pageUuid],
            );

            if (!$webmentions) {
                FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionByPageUuid', 'info', 'Webmention not found');
                return null;
            }

            FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionByPageUuid', 'info', 'Found webmentions', $webmentions->count());
            return $webmentions;
        } catch (Throwable $_e) {
            return null;
        }
    }

    /**
     * Gets all source urls of all webmentions of a page
     *
     * @param string $pageUuid the uuid of the page
     * @return array
     */
    public function getIncomingWebmentionSourceUrlsByPageUuid(string $pageUuid): array
    {
        try {
            FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionSourceUrlsByPageUuid', 'info', '');

            $webmentions = $this->indieDb->select(
                table: 'webmentions_received',
                fields: ['*'],
                where: "page_uuid = :uuid",
                bindings: ['uuid' => $pageUuid],
            );

            if (!$webmentions) {
                FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionSourceUrlsByPageUuid', 'info', 'Webmention not found');
                return [];
            }

            $registeredUrlsArray = array_column($webmentions->toArray(), 'source_url');
            FlowDebug::step('outbox', $pageUuid, 'getIncomingWebmentionSourceUrlsByPageUuid', 'info', 'Found URLs', $registeredUrlsArray);

            return $registeredUrlsArray;
        } catch (Throwable $_e) {
            return [];
        }
    }

    /**
     * Deletes a single webmentions by its id
     *
     * @param string $webmentionId the id of the webmention
     * @return bool
     */
    public function deleteIncomingWebmentionById(string $webmentionId): bool
    {
        try {
            FlowDebug::step('outbox', $webmentionId, 'deleteIncomingWebmentionById', 'info', '');

            $deleted = $this->indieDb->delete(
                table: 'webmentions_received',
                where: ['id' => $webmentionId]
            );

            if (!$deleted) {
                FlowDebug::step('outbox', $webmentionId, 'deleteIncomingWebmentionById', 'error', 'Could not delete webmention');
            }

            return $deleted;
        } catch (Throwable $_e) {
            return false;
        }
    }

    /**
     * Marks all webmentions matching the URL as blocked
     *
     * @param string $url the full URL
     * @param string $direction incoming / outgoing
     * @param bool $hostOnly only the host or the URL with path
     * @return bool
     */
    public function markWebmentionAsBlocked(string $url, string $direction, bool $hostOnly): bool
    {
        try {
            FlowDebug::step('outbox', $url, 'markWebmentionAsBlocked', 'info', '');
            $table = $direction === 'incoming' ? 'webmentions_received' : 'webmentions_sent';
            $urlField = $direction === 'incoming' ? 'source_url' : 'target_url';

            if ($hostOnly) {
                $base = Url::base($url);
                if (empty($base)) {
                    return false;
                }

                // LIKE-Wildcards escapen, '!' als Escape-Zeichen
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $base);

                $where = "({$urlField} = :base"
                    . " OR {$urlField} LIKE :path ESCAPE '!'"
                    . " OR {$urlField} LIKE :query ESCAPE '!'"
                    . " OR {$urlField} LIKE :hash ESCAPE '!')";

                $bindings = [
                    'base'  => $base,
                    'path'  => $escaped . '/%',
                    'query' => $escaped . '?%',
                    'hash'  => $escaped . '#%',
                ];
            } else {
                $where    = "{$urlField} = :url";
                $bindings = ['url' => $url];
            }

            return $this->indieDb->update(
                table: $table,
                values: ['blocked' => 1],
                where: $where,
                bindings: $bindings
            );
        } catch (Throwable $_e) {
            return false;
        }
    }

    /**
     * Marks all webmentions matching the URL as blocked
     *
     * @param string $url the full URL
     * @param string $direction incoming / outgoing
     * @param bool $hostOnly only the host or the URL with path
     * @return bool
     */
    public function markWebmentionAsUnblocked(string $url, string $direction, bool $hostOnly): bool
    {
        try {
            FlowDebug::step('outbox', $url, 'markWebmentionAsUnblocked', 'info', '');
            $table = $direction === 'incoming' ? 'webmentions_received' : 'webmentions_sent';
            $urlField = $direction === 'incoming' ? 'source_url' : 'target_url';

            if ($hostOnly) {
                $base = Url::base($url);
                if (empty($base)) {
                    return false;
                }

                // LIKE-Wildcards escapen, '!' als Escape-Zeichen
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $base);

                $where = "({$urlField} = :base"
                    . " OR {$urlField} LIKE :path ESCAPE '!'"
                    . " OR {$urlField} LIKE :query ESCAPE '!'"
                    . " OR {$urlField} LIKE :hash ESCAPE '!')";

                $bindings = [
                    'base'  => $base,
                    'path'  => $escaped . '/%',
                    'query' => $escaped . '?%',
                    'hash'  => $escaped . '#%',
                ];
            } else {
                $where    = "{$urlField} = :url";
                $bindings = ['url' => $url];
            }

            return $this->indieDb->update(
                table: $table,
                values: ['blocked' => 0],
                where: $where,
                bindings: $bindings
            );
        } catch (Throwable $_e) {
            return false;
        }
    }


    /**
     * Adds a post to the outbox
     *
     * @param string $pageUuid the uuid->id() of the page
     * @param string $postContent the text of the post
     * @param string $postUrl the full URL of the post
     * @param string $postService mastodon or bluesky
     * @param PostOutgoingStatus $status
     * @return void
     */
    public function addPost(string $pageUuid, string $postContent, string $postUrl, string $postService, PostOutgoingStatus $status): void
    {
        FlowDebug::step('posts', $pageUuid, 'addPost', 'info', '', $postUrl);

        $uuid = $this->createUuid();
        $this->indieDb->insert(
            table: 'posts_sent',
            values: [
                'id' => $uuid,
                'page_uuid' => $pageUuid,
                'post_content' => $postContent,
                'post_service' => $postService,
                'post_url' => $postUrl,
                'active' => true,
                'status' => $status->value,
            ]
        );
    }

    /**
     * Gets all posts of a page
     *
     * @param string $pageUuid the uuid->id() of the page
     * @return mixed
     */
    public function getPosts(string $pageUuid): mixed
    {
        try {
            FlowDebug::step('posts', $pageUuid, 'getPosts', 'info');

            $posts = $this->indieDb->select(
                table: 'posts_sent',
                fields: ['*'],
                where: 'page_uuid = :pageUuid',
                bindings: [
                    'pageUuid' => $pageUuid
                ]
            );

            FlowDebug::step('outbox', $pageUuid, 'getPosts', 'info', 'posts', $posts);
            return $posts;
        } catch (Exception $e) {
            FlowDebug::step('outbox', $pageUuid, 'getPosts', 'error', '', $e);
            return null;
        }
    }

    /**
     * Creates a UUID
     *
     * This is a wrapper for easier testing
     * @return string UUID of the webmention or false
     */
    protected function createUuid(): string
    {
        $id = Uuid::generate();
        FlowDebug::step('outbox', '', 'outbox:createUuid', 'info', 'created new uuid', $id);

        return $id;
    }

    /* OLD VERSION */



    public function getVersion($outbox)
    {
        if (!isset($outbox['version'])) {
            return 1;
        }

        return $outbox['version'];
    }

    public function create($page): array
    {
        $data = [
            'version' => $this->outboxVersion,
            'webmentions' => [],
            'posts' => [],
        ];

        $this->write($data, $page);
        return $data;
    }

    public function convertProcessedUrlsToV2($processedUrls)
    {
        $processedUrlsV2 = [];
        foreach ($processedUrls as $url) {
            $processedUrlsV2[] = !is_array($url)
                ? [
                    'url' => $url,
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'success',
                    'retries' => 0,
                ]
                : $url;
        }

        return $processedUrlsV2;
    }

    public function convertToV2($outbox)
    {
        $convertedOutbox = [
            'version' => $this->outboxVersion,
            'webmentions' => $this->convertProcessedUrlsToV2($outbox),
            'posts' => [],
        ];
        return $convertedOutbox;
    }

    public function read($page): array
    {
        $outboxFile = $page->file($this->outboxFilename);

        if (is_null($outboxFile)) {
            return $this->create($page);
        }

        if (!$outboxFile->exists()) {
            return $this->create($page);
        }

        $outbox = Json::read($outboxFile->root());

        if ($this->getVersion($outbox) === 1) {
            return $this->convertToV2($outbox);
        }

        // fix for outbox format mismatch
        if (count($outbox['webmentions']) > 0 && !isset($outbox['webmentions'][0]['url'])) {
            $outbox['webmentions'] = $this->convertProcessedUrlsToV2($outbox['webmentions']);
        }

        return $outbox;
    }

    public function write($outboxData, $page)
    {
        $outboxFile = $page->file($this->outboxFilename);
        $filePath = is_null($outboxFile) ? $page->root() . '/' . $this->outboxFilename : $outboxFile->root();

        Json::write($filePath, $outboxData);
    }

    public function getExternalPostByNetwork($page, string $network)
    {
        $outbox = $this->read($page);
        $posts = $outbox['posts'];

        $posts = array_filter($outbox['posts'], function ($post) use ($network) {
            return $post['target'] === $network;
        });

        $firstEntry = reset($posts);
        return $firstEntry ?? null;
    }
}
