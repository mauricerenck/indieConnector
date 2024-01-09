<?php

namespace mauricerenck\IndieConnector;

use \IndieWeb\MentionClient;

class WebmentionSender extends Sender
{
    

    public function __construct(
        private ?bool $activeWebmentions = null,
        private $mentionClient = null
    ) {
        parent::__construct();

        $this->mentionClient = new MentionClient();
        $this->activeWebmentions = $activeWebmentions ?? option('mauricerenck.indieConnector.sendWebmention', true);
    }

    public function sendWebmentions($page, array $urls)
    {
        // global config
        if (!$this->activeWebmentions) {
            return false;
        }

        // page level toggle
        if ($page->webmentionsStatus()->isFalse()) {
            return false;
        }

        if (!$this->pageFullfillsCriteria($page)) {
            return false;
        }

        $processedUrls = [];
        foreach ($urls as $url) {
            if (!$this->shouldSendWebmentionToTarget($url)) {
                continue;
            }

            $sent = $this->send($url, $page->url());

            if ($sent) {
                $processedUrls[] = $url;
            }
        }

        $this->storeProcessedUrls($processedUrls, $page);

        // TODO Check if its better to move elsewhere
        if (option('mauricerenck.indieConnector.stats', false)) {
            $stats = new WebmentionStats();
            $stats->trackOutgoingWebmentions($processedUrls, $page);
        }

        return $processedUrls;
    }

    public function shouldSendWebmentionToTarget(string $url): bool
    {
        if ($this->isLocalUrl($url)) {
            return false;
        }

        if (!$this->urlExists($url)) {
            // TODO Log this in new json format for error reporting in panel and retries
            return false;
        }


        // TODO FEATURE: Check if url is blocked
        // if (!$this->isBlocked($url)) {
        //     // TODO Log this in new json format for error reporting in panel and retries
        //     return false;
        // }

        return true;
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
}
