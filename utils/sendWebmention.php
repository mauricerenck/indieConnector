<?php

namespace mauricerenck\IndieConnector;

use \IndieWeb\MentionClient;

class WebmentionSender
{
    private $mentionClient;

    public function __construct()
    {
        $this->mentionClient = new MentionClient();
    }

    public function send(string $targetUrl, string $sourceUrl)
    {

        $senderUtils = new SenderUtils();
        if (!$senderUtils->urlExists($targetUrl)) {
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
