<?php

namespace mauricerenck\IndieConnector;

use Kirby\Data\Json;
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
     * @param $pageUuid the uuid->id() of the page
     * @param $pageUrl the full URL of the page
     * @param $targetUrl the full URL of the target
     * @return void
     */
    public function addWebmentionTarget(string $pageUuid, string $pageUrl, string $targetUrl): void
    {
        FlowDebug::step('sendWebmentions', $pageUuid, 'addWebmentionTarget', 'info', '', $targetUrl);
        $this->indieDb->insert(
            table: 'webmentions_sent',
            values: [
                'page_uuid' => $pageUuid,
                'target_url' => $targetUrl,
                'source_url' => $pageUrl,
                'updates' => 0,
                'status' => WebmentionStatus::Pending->value,
            ]
        );
    }

    /**
     * Get an array of all registered webmention target URLs for a single page
     *
     * @param $pageUuid the uuid->id() of the page
     * @return array
     */
    public function getRegisteredWebmentionTargets(string $pageUuid, ?WebmentionStatus $statusFilter = null): array
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
     * @param $pageUuid the uuid->id() of the page
     * @return array
     */
    public function getPendingWebmentionTargets(string $pageUuid): array
    {
        try {
            FlowDebug::step('sendWebmentions', $pageUuid, 'getPendingWebmentionTargets', 'info', '');
            $opt_maxRetries = $this->options->get('send.maxRetries', 3);

            [$in, $statusBindings] = $this->indieDb->inClause('status', [
                WebmentionStatus::Pending,
                WebmentionStatus::Failed,
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
     * @param $pageUuid the uuid->id() of the page
     * @param $targetUrl the full URL of the target
     * @param $newStatus WebmentionStatus
     * @return void
     */
    public function updateWebmentionTargetStatus(string $pageUuid, string $targetUrl, WebmentionStatus $newStatus): void
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
