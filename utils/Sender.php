<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\File;
use Kirby\Data\Data;
use \IndieWeb\MentionClient;
use in_array;
use array_merge;
use Exception;

class Sender
{
    public function __construct(
        private ?array $fieldsToParseUrls = null,
        private ?array $allowedTemplates = null,
        private ?array $blockedTemplates = null,
        private ?bool $activityPubBridge = null,
        private ?array $localHosts = null,
    )
    {
        $this->allowedTemplates = $allowedTemplates ?? option('mauricerenck.indieConnector.allowedTemplates', []);
        $this->blockedTemplates = $blockedTemplates ?? option('mauricerenck.indieConnector.blockedTemplates', []);
        $this->fieldsToParseUrls = $fieldsToParseUrls ?? option('mauricerenck.indieConnector.send-mention-url-fields', ['text:text', 'description:text', 'intro:text']);
        $this->activityPubBridge = $activityPubBridge ?? option('mauricerenck.indieConnector.activityPubBridge', false);
        $this->localHosts = $localHosts ?? option('mauricerenck.indieConnector.debug.localHosts', ['//localhost','//127.0.0.1']);
    }
   
    public function hasAnyEnabledFeature($page) {
        // check for page level toggles for webmentions, mastodon and activitypub (fed.brid.gy)
        return $page->webmentionsStatus()->isTrue() || $page->mastodonStatus()->isTrue() || $page->activityPubStatus()->isTrue();
    }

    public function pageFullfillsCriteria($page)
    {
        if (!$this->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->templateIsBlocked($page->intendedTemplate())) {
            return false;
        }

        if (!$this->templateIsAllowed($page->intendedTemplate())) {
            return false;
        }

        return true;
    }

    public function pageHasNeededStatus($page)
    {
        return !$page->isDraft();
    }

    public function templateIsAllowed($template)
    {
        return (in_array($template, $this->allowedTemplates) || count($this->allowedTemplates) === 0);
    }

    public function templateIsBlocked($template)
    {
        return (in_array($template, $this->blockedTemplates));
    }

    public function skipSameHost($url)
    {
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

    public function getUnprocessedUrls($page): array
    {
        $urls = $this->findUrls($page);
        $processedUrls = $this->getProcessedUrls($page);
        $cleanedUrls = $this->cleanupUrls($urls, $processedUrls);

        return count($cleanedUrls) === 0 ? [] : $cleanedUrls;
    }

    public function getProcessedUrls($page)
    {
        $outboxFile = $this->readOutbox($page);

        if (is_null($outboxFile)) {
            return [];
        }

        try {
            return json_decode($outboxFile->content()->data()[0]);
        } catch (Exception $e) {
            return [];
        }
    }

    public function mergeExistingProcessedUrls($urls, $page)
    {
        $processedUrls = $this->getProcessedUrls($page);
        $mergedUrls = array_merge($processedUrls, $urls);
        return array_unique($mergedUrls);
    }

    public function storeProcessedUrls($processedUrls, $page)
    {
        try {
            $combinedUrls = $this->mergeExistingProcessedUrls($processedUrls, $page);
            $this->writeOutbox($combinedUrls, $page);
        } catch (Exception $e) {
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

    public function urlExists(string $url): bool
    {
        $rejectedStatusCodes = [404, 403, 401, 400, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);

        if (!$result) {
            return false;
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (in_array($statusCode, $rejectedStatusCodes)) {
            return false;
        }

        return true;
    }

    public function isLocalUrl(string $url): bool 
    {
        return !(in_array($url, $this->localHosts));
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
