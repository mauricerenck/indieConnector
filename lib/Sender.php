<?php

namespace mauricerenck\IndieConnector;

use IndieWeb\MentionClient;
use Kirby\Data\Json;

class Sender
{
    private $outboxVersion = 2;

    public function __construct(
        private ?array $fieldsToParseUrls = null,
        private ?bool $activityPubBridge = null,
        private ?string $outboxFilename = null,

        private ?UrlChecks $urlChecks = null
    ) {
        $this->fieldsToParseUrls =
            $fieldsToParseUrls ??
            option('mauricerenck.indieConnector.send.url-fields', ['text:text', 'description:text', 'intro:text']);
        $this->activityPubBridge = $activityPubBridge ?? option('mauricerenck.indieConnector.activityPubBridge', false);
        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->outboxFilename =
            $outboxFilename ?? option('mauricerenck.indieConnector.send.outboxFilename', 'indieConnector.json');

        // backwards compatibility
        if (!$fieldsToParseUrls && option('mauricerenck.indieConnector.send-mention-url-fields', false)) {
            $this->fieldsToParseUrls = option('mauricerenck.indieConnector.send-mention-url-fields');
        }

        if (!$outboxFilename && option('mauricerenck.indieConnector.outboxFilename', false)) {
            $this->outboxFilename = option('mauricerenck.indieConnector.outboxFilename');
        }
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

    public function getPostTargetUrl($target, $page)
    {

        if ($target === 'mastodon' && $page->mastodonStatusUrl()->isNotEmpty()) {
            return $page->mastodonStatusUrl()->value();
        }

        if ($target === 'bluesky' && $page->blueskyStatusUrl()->isNotEmpty()) {
            $bluesky = new Bluesky();
            $bskUrl = $page->blueskyStatusUrl()->value();
            return (str_starts_with('at://', $bskUrl)) ? $bluesky->getDidFromUrl($bskUrl) : $bskUrl;
        }

        $outbox = $this->readOutbox($page);

        $posts = array_filter($outbox['posts'], function ($post) use ($target) {
            return $post['target'] === $target;
        });

        $firstEntry = reset($posts);
        return $firstEntry['url'] ?? null;
    }

    public function getPostTargetUrlAndStatus($target, $page)
    {

        if ($target === 'mastodon' && $page->mastodonStatusUrl()->isNotEmpty()) {
            return ['url' => $page->mastodonStatusUrl()->value(), 'status' => 'success'];
        }

        if ($target === 'bluesky' && $page->blueskyStatusUrl()->isNotEmpty()) {
            $bluesky = new Bluesky();
            $bskUrl = $page->blueskyStatusUrl()->value();
            $url = (str_starts_with('at://', $bskUrl)) ? $bluesky->getDidFromUrl($bskUrl) : $bskUrl;
            return ['url' => $url, 'status' => 'success'];
        }

        $outbox = $this->readOutbox($page);

        $posts = array_filter($outbox['posts'], function ($post) use ($target) {
            return $post['target'] === $target;
        });

        if (!$posts) {
            return ['url' => null, 'status' => null];
        }

        $firstEntry = reset($posts);
        return ['url' => $firstEntry['url'], 'status' => $firstEntry['status']] ?? ['url' => null, 'status' => null];
    }

    public function alreadySentToTarget($target, $page)
    {
        $post = $this->getPostTargetUrl($target, $page);

        return !is_null($post);
    }

    public function updateExternalPosts($posts, $page)
    {
        $outbox = $this->readOutbox($page);

        $retries = [];
        foreach ($outbox['posts'] as $post) {
            if (isset($post['target'])) {
                $retries[$post['target']] = $post['retries'];
            }
        }

        $newPosts = [];

        foreach ($posts as $post) {
            $status = $post['status'] === 200 ? 'success' : 'error';
            $newPosts[] = [
                'id' => $post['id'],
                'url' => $post['uri'],
                'status' => $status,
                'target' => $post['target'],
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ];
        }

        $processedPosts = array_merge($newPosts, $outbox['posts']);
        $outbox['posts'] = $processedPosts;

        $this->writeOutbox($outbox, $page);

        return $outbox;
    }

    public function updateResponseCollectionUrls($posts, $page)
    {
        $responseCollector = new ResponseCollector();

        foreach ($posts as $post) {
            $status = $post['status'] === 200 ? 'success' : 'error';

            if ($status === 'success') {
                $responseCollector->registerPostUrl($page->uuid()->id(), $post['uri'], $post['target']);
            }
        }

        return;
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

    public function getOutboxVersion($outbox)
    {
        if (!isset($outbox['version'])) {
            return 1;
        }

        return $outbox['version'];
    }

    public function createOutbox($page): array
    {
        $data = [
            'version' => $this->outboxVersion,
            'webmentions' => [],
            'posts' => [],
        ];

        $this->writeOutbox($data, $page);
        return $data;
    }

    public function convertOutboxToV2($outbox)
    {
        $convertedOutbox = [
            'version' => $this->outboxVersion,
            'webmentions' => $this->convertProcessedUrlsToV2($outbox),
            'posts' => [],
        ];
        return $convertedOutbox;
    }

    public function readOutbox($page): array
    {
        $outboxFile = $page->file($this->outboxFilename);

        if (is_null($outboxFile)) {
            return $this->createOutbox($page);
        }

        if (!$outboxFile->exists()) {
            return $this->createOutbox($page);
        }

        $outbox = Json::read($outboxFile->root());

        if ($this->getOutboxVersion($outbox) === 1) {
            return $this->convertOutboxToV2($outbox);
        }

        // fix for outbox format mismatch
        if (count($outbox['webmentions']) > 0 && !isset($outbox['webmentions'][0]['url'])) {
            $outbox['webmentions'] = $this->convertProcessedUrlsToV2($outbox['webmentions']);
        }

        return $outbox;
    }

    public function writeOutbox($outboxData, $page)
    {
        $outboxFile = $page->file($this->outboxFilename);
        $filePath = is_null($outboxFile) ? $page->root() . '/' . $this->outboxFilename : $outboxFile->root();

        Json::write($filePath, $outboxData);
    }
}
