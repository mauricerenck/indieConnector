<?php

namespace mauricerenck\IndieConnector;

use Kirby\Data\Json;

class Outbox
{
    private $outboxVersion = 2;

    public function __construct(
        private ?string $outboxFilename = null,
    ) {
        $this->outboxFilename =
            $outboxFilename ?? option('mauricerenck.indieConnector.send.outboxFilename', 'indieConnector.json');
    }

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
