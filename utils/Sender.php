<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\File;
use Kirby\Data\Data;
use \IndieWeb\MentionClient;
use file_exists;
use implode;
use in_array;
use array_merge;
use Exception;

class Sender
{
    private $fieldsToParseUrls;

    public function __construct()
    {
        $this->fieldsToParseUrls = option('mauricerenck.indieConnector.send-mention-url-fields', ['text:text', 'description:text', 'intro:text']);
    }

    public function pageFullfillsCriteria($page)
    {
        // check page status
        if (!$this->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->templateIsBlocked($page->intendedTemplate())) {
            return false;
        }

        if (!$this->templateIsAllowed($page->intendedTemplate())) {
            return false;
        }

        if ($page->webmentionsStatus()->isFalse()) {
            return false;
        }

        return true;
    }

    public function shouldSendWebmention()
    {
        return option('mauricerenck.indieConnector.sendWebmention', true);
    }

    public function pageHasNeededStatus($page)
    {
        return !$page->isDraft();
    }

    public function templateIsAllowed($template)
    {
        $allowList = option('mauricerenck.indieConnector.allowedTemplates', []);
        return (in_array($template, $allowList) || count($allowList) === 0);
    }

    public function templateIsBlocked($template)
    {
        $blockList = option('mauricerenck.indieConnector.blockedTemplates', []);
        return (in_array($template, $blockList));
    }

    public function skipSameHost($url) {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $host = kirby()->environment()->host();

        return (option('mauricerenck.indieConnector.skipSameHost', true) && $urlHost === $host);
    }

    public function cleanupUrls($urls, $processedUrls)
    {

        if (count($urls) === 0) {
            return [];
        }

        $cleanedUrls = [];
        foreach ($urls as $url) {
            if ($this->skipSameHost($url)) {
                continue;
            }

            if (!in_array($url, $processedUrls)) {
                $cleanedUrls[] = $url;
            }
        }

        return $cleanedUrls;
    }

    public function getProcessedUrls($page)
    {
        $outboxFile = $this->readOutbox($page);

        if (is_null($outboxFile)) {
            return [];
        }

        try {
            return json_decode($outboxFile->content()->toArray()[0]);
        } catch (Exception $e) {
            return [];
        }
    }

    public function storeProcessedUrls($urls, $processedUrls, $page)
    {

        try {
            $combinedUrls = array_merge($processedUrls, $urls);
            $this->writeOutbox($combinedUrls, $page);
        } catch (Exception $e) {
            return false;
        }

        if (option('mauricerenck.indieConnector.stats', false)) {
            $stats = new WebmentionStats();
            foreach ($processedUrls as $url) {
                $stats->updateOutbox($page->uuid()->id(), $url);
            }

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

        if (option('mauricerenck.indieConnector.activityPubBridge', false)) {
            $detectedUrls[] = 'https://fed.brid.gy/';
        }

        return $detectedUrls;
    }

    public function urlExists(string $url): bool
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);

        if (!$result) {
            return false;
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($statusCode == 404) {
            return false;
        }

        return true;

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

    public function readOutbox($page): File|null
    {
        $outboxFile = $page->file(option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json'));

        if (is_null($outboxFile)) {
            return null;
        }

        if (!$outboxFile->exists()) {
            return null;
        }

        return $outboxFile;
    }

    public function writeOutbox($urls, $page)
    {
        $outboxFilePath = $page->root() . '/' . option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json');
        Data::write($outboxFilePath, $urls);
    }

}
