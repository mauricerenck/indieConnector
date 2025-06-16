<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Remote;
use Mf2;
use Exception;

class WebmentionReceiver extends Receiver
{
    public function __construct(private $sourceUrl = null, private $targetUrl = null)
    {
        parent::__construct();
    }

    public function processWebmention($sourceUrl, $targetUrl)
    {
        if (is_null($this->sourceUrl)) {
            $this->sourceUrl = $sourceUrl;
        }

        if (is_null($this->targetUrl)) {
            $this->targetUrl = $targetUrl;
        }

        try {
            $microformats = $this->getDataFromSource($sourceUrl);
            $webmention = $this->getWebmentionData($microformats);

            if ($webmention === false) {
                return [
                    'status' => 'error',
                    'message' => 'no webmention data',
                ];
            }

            if (isset($webmention['status']) && $webmention['status'] === 'deleted') {
                kirby()->trigger('indieConnector.webmention.deleted', [
                    'targetUrl' => $targetUrl,
                    'sourceUrl' => $sourceUrl,
                ]);
            }

            $targetPage = $this->getPageFromUrl($targetUrl);

            if (!$targetPage) {
                return [
                    'status' => 'error',
                    'message' => 'no target page found for ' . $targetUrl,
                ];
            }

            $hookData = $this->splitWebmentionDataIntoHooks($webmention);

            foreach ($hookData as $data) {
                $hookData = $this->convertToHookData($data, [
                    'source' => $sourceUrl,
                    'target' => $targetUrl,
                ]);

                $this->triggerWebmentionHook($hookData, $targetPage->uuid()->toString());
            }

            return [
                'status' => 'success',
                'message' => 'webmention processed',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getDataFromSource($sourceUrl)
    {
        $request = Remote::get($sourceUrl);
        $responseCode = $request->code();

        if ($responseCode === 410) {
            return [
                'status' => 'deleted',
            ];
        }

        if ($responseCode === 200) {
            $sourceBody = $request->content();
            if (empty($sourceBody)) {
                return false;
            }

            $mf = Mf2\parse($sourceBody, $sourceUrl);

            return $mf;
        }

        return false;
    }

    public function getWebmentionData($microformats)
    {
        $data = [];

        if (empty($microformats['items'])) {
            return false;
        }

        $mf2 = new Microformats($this->targetUrl);
        $data['types'] = $mf2->getTypes($microformats);
        $data['content'] = $mf2->getSummaryOrContent($microformats);
        $data['published'] = $mf2->getPublishDate($microformats);
        $data['author'] = $mf2->getAuthor($microformats);
        $data['title'] = $mf2->getTitle($microformats);
        $data['service'] = $mf2->getService($microformats);

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

    public function triggerWebmentionHook($webmention, $pageUuid)
    {
        kirby()->trigger('indieConnector.webmention.received', [
            'webmention' => $webmention,
            'targetPage' => $pageUuid,
        ]);
    }
}
