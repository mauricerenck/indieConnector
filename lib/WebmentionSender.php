<?php

namespace mauricerenck\IndieConnector;

use IndieWeb\MentionClient;
use Exception;

class WebmentionSender extends Sender
{
    private $indieDb;

    public function __construct(
        private ?bool $activeWebmentions = null,
        private ?int $maxRetries = null,
        private ?int $markDeletedPages = null,

        private $mentionClient = null,
        private ?UrlChecks $urlChecks = null,
        private ?PageChecks $pageChecks = null,
        private ?IndieConnectorDatabase $indieDatabase = null
    ) {
        parent::__construct();

        $this->activeWebmentions = $activeWebmentions ?? option('mauricerenck.indieConnector.send.enabled', true);
        $this->maxRetries = $maxRetries ?? option('mauricerenck.indieConnector.send.maxRetries', 3);
        $this->markDeletedPages = $markDeletedPages ?? option('mauricerenck.indieConnector.send.markDeleted', false);

        $this->mentionClient = new MentionClient();
        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();

        // backwards compatibility
        if (!$activeWebmentions && option('mauricerenck.indieConnector.sendWebmention', false)) {
            $this->activeWebmentions = option('mauricerenck.indieConnector.sendWebmention');
        }
    }

    public function sendWebmentions($page)
    {
        // global config
        if (!$this->activeWebmentions) {
            return false;
        }

        if (!$this->pageChecks->pageHasEnabledWebmentions($page)) {
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
                'retries' => 0,
            ];
        }

        $mergedUrls = $this->mergeUrlsWithOutbox($processedUrls, $page);

        $this->updateWebmentions($mergedUrls, $page);

        if (option('mauricerenck.indieConnector.stats.enabled', false)) {
            $stats = new WebmentionStats();
            $stats->trackOutgoingWebmentions($mergedUrls, $page);
        }

        return $mergedUrls;
    }

    public function sendWebmentionFromHook($page, $targetUrl, $sourceUrl)
    {
        // global config
        if (!$this->activeWebmentions) {
            return false;
        }

        $processedUrls = [];

        $sent = $this->send($targetUrl, $sourceUrl);

        $status = $sent ? 'success' : 'error';
        $processedUrls[] = [
            'url' => $targetUrl,
            'date' => date('Y-m-d H:i:s'),
            'status' => $status,
            'retries' => 0,
        ];

        $mergedUrls = $this->mergeUrlsWithOutbox($processedUrls, $page);

        $this->updateWebmentions($mergedUrls, $page);

        if (option('mauricerenck.indieConnector.stats.enabled', false)) {
            $stats = new WebmentionStats();
            $stats->trackOutgoingWebmentions($mergedUrls, $page);
        }

        return $mergedUrls;
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
        $outbox = $this->readOutbox($page);
        return $outbox['webmentions'] ?? [];
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
            $existingEntries = array_filter($outbox, function ($processedUrl) use ($newEntry) {
                return $processedUrl['url'] === $newEntry['url'];
            });

            $existingEntry =
                count($existingEntries) === 0
                ? $newEntry
                : array_shift($existingEntries);

            $mergedEntry = array_merge($existingEntry, $newEntry);

            if ($mergedEntry['status'] === 'error') {
                $mergedEntry['retries'] = $existingEntry['retries'] + 1;
            }

            $mergedUrls[] = $mergedEntry;
        }

        foreach ($outbox as $processedUrl) {
            $existingEntries = array_filter($mergedUrls, function ($mergedUrl) use ($processedUrl) {
                return $mergedUrl['url'] === $processedUrl['url'];
            });

            if (count($existingEntries) === 0) {
                $mergedUrls[] = $processedUrl;
            }
        }

        return $mergedUrls;
    }

    public function updateWebmentions($urls, $page)
    {
        $outbox = $this->readOutbox($page);
        $outbox['webmentions'] = $urls;

        $this->writeOutbox($outbox, $page);

        return $outbox;
    }

    public function markPageAsDeleted($page)
    {
        if (!$this->markDeletedPages) {
            return false;
        }

        if (!$this->activeWebmentions) {
            return false;
        }

        $outbox = $this->readOutbox($page);
        if (count($outbox) === 0) {
            return false;
        }

        try {
            $deletedDate = $this->indieDb->getFormattedDate();
            $this->indieDb->insert(
                'deleted_pages',
                ['id', 'slug', 'deletedAt'],
                [$page->uuid()->id(), $page->uri(), $deletedDate]
            );
            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function removePageFromDeleted($page)
    {
        if (!$this->markDeletedPages) {
            return false;
        }

        if (!$this->activeWebmentions) {
            return false;
        }

        try {
            $this->indieDb->delete('deleted_pages', 'where slug = "' . $page->uri() . '"');
            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function returnAsDeletedPage($slug)
    {
        if (!$this->markDeletedPages) {
            return false;
        }

        try {
            $result = $this->indieDb->select('deleted_pages', ['slug'], 'where slug = "' . $slug . '"');
            return $result->count() === 0 ? false : true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }
}
