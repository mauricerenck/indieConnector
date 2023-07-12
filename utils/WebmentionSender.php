<?php

namespace mauricerenck\IndieConnector;

use \IndieWeb\MentionClient;

class WebmentionSender extends Sender
{
    private $mentionClient;

    public function __construct()
    {
        parent::__construct();
        $this->mentionClient = new MentionClient();
    }

    public function sendWebmentions($updatedPage)
    {
        if (!$this->shouldSendWebmention()) {
            return;
        }

        if (!$this->pageFullfillsCriteria($updatedPage)) {
            return;
        }

        $urls = $this->findUrls($updatedPage);
        $processedUrls = $this->getProcessedUrls($updatedPage);
        $cleanedUrls = $this->cleanupUrls($urls, $processedUrls);

        if (count($cleanedUrls) === 0) {
            return;
        }

        $processedUrls = [];
        foreach ($cleanedUrls as $url) {
            $sent = $this->send($url, $updatedPage->url());

            if ($sent) {
                $processedUrls[] = $url;
            }
        }


        $this->storeProcessedUrls($urls, $processedUrls, $updatedPage);
    }

    public function send(string $targetUrl, string $sourceUrl)
    {
        if (!$this->urlExists($targetUrl)) {
            return false;
        }

        $endpoint = $this->mentionClient->discoverWebmentionEndpoint($targetUrl);

        if (strpos($endpoint, '//localhost') === true || strpos($endpoint, '//127.0.0') === true) {
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
