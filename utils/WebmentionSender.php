<?php

namespace mauricerenck\IndieConnector;

use IndieWeb\MentionClient;
use Kirby\Data\Json;

class WebmentionSender extends Sender
{
    public function __construct(
        private ?bool $activeWebmentions = null,
        private ?int $maxRetries = null,

        private $mentionClient = null,
        private ?UrlChecks $urlChecks = null,
        private ?PageChecks $pageChecks = null
    ) {
        parent::__construct();

        $this->activeWebmentions = $activeWebmentions ?? option('mauricerenck.indieConnector.sendWebmention', true);
        $this->maxRetries = $maxRetries ?? option('mauricerenck.indieConnector.send.maxRetries', 3);
        $this->mentionClient = new MentionClient();
        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();
    }

    public function sendWebmentions($page)
    {
        // global config
        if (!$this->activeWebmentions) {
            return false;
        }

        if (!$this->pageChecks->pageFullfillsCriteria($page)) {
            return false;
        }

        $urls = $this->getUnprocessedUrls($page);
        $urls = $this->filterDuplicateUrls($urls);

        if (empty($urls)) {
            return;
        }

        $processedUrls = [];
        foreach ($urls as $url) {
            if (!$this->isValidTarget($url)) {
                continue;
            }

            $sent = $this->send($url, $page->url());

            $status = $sent ? 'success' : 'error';
            $processedUrls[] = [
                'url' => $url,
                'date' => date('Y-m-d H:i:s'),
                'status' => $status,
                'retries' => $status === 'error' ? 1 : 0,
            ];
        }

        $this->mergeUrlsWithOutbox($processedUrls, $page);
        $this->writeOutbox($processedUrls, $page);

        if (option('mauricerenck.indieConnector.stats.enabled', false)) {
            $urls = array_map(function ($url) {
                return $url['url'];
            }, $processedUrls);

            $stats = new WebmentionStats();
            $stats->trackOutgoingWebmentions($urls, $page);
        }

        return $processedUrls;
    }

    public function send(string $targetUrl, string $sourceUrl)
    {
        $endpoint = $this->mentionClient->discoverWebmentionEndpoint($targetUrl);

        if (is_null($endpoint)) {
            return false;
        }

        if ($endpoint) {
            $webmentionResult = $this->mentionClient->sendWebmention($sourceUrl, $targetUrl);

            if ($webmentionResult !== false) {
                return true;
            }
        }

        $supportsPingback = $this->mentionClient->discoverPingbackEndpoint($targetUrl);
        if ($supportsPingback) {
            $pingbackResult = $this->mentionClient->sendPingback($sourceUrl, $targetUrl);

            if ($pingbackResult !== false) {
                return true;
            }
        }

        return false;
    }

    public function filterDuplicateUrls($urls)
    {
        return array_unique($urls);
    }

    public function getUnprocessedUrls($page): array
    {
        $urls = $this->findUrls($page);
        $processedUrls = $this->getProcessedUrls($page);
        $cleanedUrls = $this->cleanupUrls($urls, $processedUrls);

        return $cleanedUrls;
    }

    public function urlWasAlreadyProcessed($url, $processedUrls)
    {
        foreach ($processedUrls as $processedUrl) {
            if ($processedUrl['url'] === $url) {
                switch ($processedUrl['status']) {
                    case 'error':
                        if ($processedUrl['retries'] >= $this->maxRetries) {
                            return true;
                        }
                        break;
                }
                return false;
            }
        }

        return false;
    }

    public function getProcessedUrls($page)
    {
        $urls = $this->readOutbox($page);
        return $this->convertProcessedUrlsToV2($urls);
    }

    public function cleanupUrls($urls, $processedUrls)
    {
        if (count($urls) === 0) {
            return [];
        }

        $cleanedUrls = [];
        foreach ($urls as $url) {
            if ($this->urlChecks->skipSameHost($url)) {
                continue;
            }

            if ($this->urlWasAlreadyProcessed($url, $processedUrls)) {
                continue;
            }

            $cleanedUrls[] = $url;
        }

        return $cleanedUrls;
    }

    public function mergeUrlsWithOutbox($newEntries, $page)
    {
        $outbox = $this->getProcessedUrls($page);

        $mergedUrls = [];
        foreach ($newEntries as $newEntry) {
            $existingEntries = array_map(function ($entry) use ($newEntry) {
                if ($entry['url'] === $newEntry) {
                    return $entry;
                }
            }, $outbox);

            $existingEntry =
                !is_null($existingEntries) || empty($existingEntries)
                    ? [
                        'url' => $newEntry,
                        'date' => null,
                        'status' => null,
                        'retries' => 1,
                    ]
                    : $existingEntries[0];

            $mergedEntry = array_merge($existingEntry, $newEntry);
            $mergedEntry['retries'] += $newEntry['retries'];

            $mergedUrls[] = $mergedEntry;
        }

        return $mergedUrls;
    }

    public function readOutbox($page): array
    {
        $outboxFile = $page->file(option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json'));

        if (is_null($outboxFile)) {
            return [];
        }

        if (!$outboxFile->exists()) {
            return [];
        }

        return Json::read($outboxFile->root());
    }

    public function writeOutbox($urls, $page)
    {
        $outboxFile = $page->file(option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json'));

        $filePath = is_null($outboxFile)
            ? $page->root() . '/' . option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json')
            : $outboxFile->root();

        Json::write($filePath, $urls);
    }

    public function convertProcessedUrlsToV2($processedUrls)
    {
        $processedUrlsV1 = [];
        foreach ($processedUrls as $url) {
            $processedUrlsV1[] = !is_array($url)
                ? [
                    'url' => $url,
                    'date' => date('Y-m-d H:i:s'),
                    'status' => 'success',
                    'retries' => 0,
                ]
                : $url;
        }

        return $processedUrlsV1;
    }
}
