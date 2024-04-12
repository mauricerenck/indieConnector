<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Remote;
use Mf2;

class WebmentionReceiver extends Receiver
{
    private $microformats = null;
    public function __construct(private $sourceUrl = null, private $targetUrl = null)
    {
        parent::__construct();
        $this->microformats = new Microformats($targetUrl);
    }

    public function webmentionFullfillsCriteria()
    {
        // TODO check if source is a valid url
        // TODO check if source host is blocked
        // TODO check if source host is local
        // TODO check if target is a valid url
        // TODO check if target and source are the same
        // TODO check if target is a page
        // TODO check if target is a page with webmentions enabled

        return true;
    }

    public function getDataFromSource($sourceUrl)
    {
        $request = Remote::get($sourceUrl);
        $remote = $request->info();

        if ($remote['http_code'] === 410) {
            // TODO DELETED - DO SOMETHING
            return [
                'status' => 'deleted',
            ];
        }

        if ($remote['http_code'] === 200) {
            // TODO NOT FOUND - DO SOMETHING

            $sourceBody = $request->content();
            if (empty($sourceBody)) {
                return [
                    'status' => 'no content',
                ];
            }

            $mf = Mf2\parse($sourceBody, $sourceUrl);

            return $mf;
        }

        // TODO something is wrong here - return error
    }

    public function getWebmentionData($microformats)
    {
        $data = [];

        if (empty($microformats['items'])) {
            return false;
        }

        $data['types'] = $this->microformats->getTypes($microformats);
        $data['content'] = $this->microformats->getSummaryOrContent($microformats);
        $data['published'] = $this->microformats->getPublishDate($microformats);
        $data['author'] = $this->microformats->getAuthor($microformats);
        $data['title'] = $this->microformats->getTitle($microformats);

        return $data;
    }

    public function splitWebmentionDataIntoHooks($webmentionData): array
    {
        $hookData = [];
        foreach ($webmentionData['types'] as $webmentionType) {
            $data = $webmentionData;
            $data['type'] = $webmentionType;

            $hookData[] = $data;
        }

        return $hookData;
    }

    public function convertToHookData($data)
    {
        return [
            'type' => $data['type'],
            'target' => $this->targetUrl,
            'source' => $this->sourceUrl,
            'published' => $data['published'],
            'title' => $data['title'],
            'content' => $data['content'],
            'author' => [
                'type' => 'card',
                'name' => $data['author']['name'],
                'avatar' => $data['author']['photo'],
                'url' => $data['author']['url'],
            ],
        ];
    }
}
