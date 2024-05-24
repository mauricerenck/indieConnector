<?php

namespace mauricerenck\IndieConnector;

use IndieWeb\MentionClient;

class Sender
{
    public function __construct(
        private ?array $fieldsToParseUrls = null,
        private ?bool $activityPubBridge = null,
        private ?UrlChecks $urlChecks = null
    ) {
        $this->fieldsToParseUrls =
            $fieldsToParseUrls ??
            option('mauricerenck.indieConnector.send-mention-url-fields', [
                'text:text',
                'description:text',
                'intro:text',
            ]);
        $this->activityPubBridge = $activityPubBridge ?? option('mauricerenck.indieConnector.activityPubBridge', false);
        $this->urlChecks = $urlChecks ?? new UrlChecks();
    }

    public function isValidTarget(string $url)
    {
        if (!$this->urlChecks->urlIsValid($url)) {
            return false;
        }

        if ($this->urlChecks->isLocalUrl($url)) {
            return false;
        }

        if (!$this->urlChecks->urlExists($url)) {
            // TODO Log this in new json format for error reporting in panel and retries
            return false;
        }

        if ($this->urlChecks->isBlockedTarget($url)) {
            return false;
        }

        return true;
    }

    public function findUrls($page)
    {
        $htmlParts = [];

        foreach ($this->fieldsToParseUrls as $field) {
            $fieldInfo = explode(':', $field);
            $content = $page->content()->get($fieldInfo[0]);

            // field is not correctly configured if it couldnt be splitted into two parts
            // -> skip and continue loop
            if (count($fieldInfo) !== 2) {
                continue;
            }

            // field does not exists or is empty
            // -> skip and continue loop
            if (!$content->exists() || $content->isEmpty()) {
                continue;
            }

            // get content of different field types
            switch ($fieldInfo[1]) {
                case 'block':
                    $htmlParts[] = $content->toBlocks();
                    break;
                case 'layout':
                    $htmlParts[] = $this->parseLayoutFields($content);
                    break;
                default:
                    $htmlParts[] = $content->kirbytext();
                    break;
            }
        }

        $html = join('', $htmlParts);
        $client = new MentionClient();
        $detectedUrls = $client->findOutgoingLinks($html);

        if ($this->activityPubBridge) {
            $detectedUrls[] = 'https://fed.brid.gy/';
        }

        return $detectedUrls;
    }

    private function parseLayoutFields($content)
    {
        $htmlParts = [];
        foreach ($content->toLayouts() as $layout) {
            foreach ($layout->columns() as $column) {
                $htmlParts[] = $column->blocks();
            }
        }

        return join('', $htmlParts);
    }
}
