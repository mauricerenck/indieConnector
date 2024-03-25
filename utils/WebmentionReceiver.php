<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Remote;
use Mf2;

class WebmentionReceiver extends Receiver
{
    private $microformats = null;
    public function __construct(private $pageUrl = null)
    {
        parent::__construct();
        $this->microformats = new Microformats($pageUrl);
    }

    public function webmentionFullfillsCriteria($sourceUrl, $targetUrl)
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

    public function getHEntry($microformats)
    {
        foreach ($microformats['items'] as $item) {
            if (isset($item['type']) && in_array('h-entry', $item['type'])) {
                return $item;
            }
        }
        return null;
    }

    public function getHCard($microformats)
    {
        foreach ($microformats['items'] as $item) {
            if (isset($item['type']) && in_array('h-card', $item['type'])) {
                return $item;
            }
        }

        return null;
    }

    public function getContent($hentry)
    {
        return $hentry['properties']['summary'][0] ?? ($hentry['properties']['content'][0]['value'] ?? null);
    }

    // TODO IMPLEMENT AND TEST
    public function getWebmentionData($microformats)
    {
        $data = [];

        if (empty($microformats['items'])) {
            return false;
        }

        $entry = $this->getHEntry($microformats);

        $data['type'] = $this->microformats->getTypes($microformats['items']);
        $data['content'] = $this->getContent($entry);
        $data['published'] = $entry['properties']['published'][0];
        $data['author'] = $this->microformats->getAuthor($microformats);

        return $data;
    }

    // TODO IMPLEMENT AND TEST
    public function convertToHookData($data)
    {
        // TODO multiple may be possible
        // TODO add some sort of status to the data - for example to mark if no mf2 data was found
        return [
            'type' => $data['type'],
            'target' => null, // TODO
            'source' => null, // TODO
            'published' => $data['published'],
            'title' => null, // TODO NEW
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
