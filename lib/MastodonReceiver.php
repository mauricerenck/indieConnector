<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;
use Kirby\Toolkit\V;

class MastodonReceiver
{
    public function __construct(
        private ?bool $enabled = null,
    ) {
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.mastodon.enabled', false);
    }

    public function getResponses(string $postUrl, string $type, array $knownIds): array
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            list($urlHost, $postId) = $this->getPostUrlData($postUrl);

            if (is_null($urlHost) || is_null($postId)) {
                return [];
            }

            $response = $this->paginateResponses($urlHost, $postId, $type, null);
            $entries = $response['data'];

            if ($this->responsesIncludeKnownId($entries, $knownIds)) {
                return $entries;
            }

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($urlHost, $postId, $type, $response['next']);
                $entries = [...$entries, ...$response['data']];

                // if there is a known Id in the loop set next to null to stop it
                if ($this->responsesIncludeKnownId($response['data'], $knownIds)) {
                    $response['next'] = null;
                }
            }

            return $entries;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return [];
        }
    }

    /*
     * Check the list and return bool, we will filter out the entry later we just
     * want to stop the pagination at this point
    */
    public function responsesIncludeKnownId($responses, $knownIds): bool
    {
        return (!empty(array_intersect(array_column($responses, 'id'), $knownIds)));
    }

    public function paginateResponses(string $host, $postId, $type, $cursor)
    {
        switch ($type) {
            case 'likes':
                $endpoint = 'favourited_by';
                break;
            case 'reposts':
                $endpoint = 'reblogged_by';
                break;
            case 'replies':
                $endpoint = 'context';
                break;
            default:
                $endpoint = null;
                break;
        }

        if (is_null($endpoint)) {
            return [
                'data' => [],
                'next' => null
            ];
        }

        try {
            $url = ($cursor) ?  $cursor : $host . '/api/v1/statuses/' . $postId . '/' . $endpoint . '?limit=1';
            $response = Remote::get($url);

            if ($response->code() !== 200) {
                return [
                    'data' => [],
                    'next' => null
                ];
            }

            $json = $response->json();
            $headers = $response->headers();

            switch ($type) {
                case 'replies':
                    $data = isset($json['descendants']) ? $json['descendants'] : [];
                    break;
                default:
                    $data = isset($json) ? $json : [];
            }

            return [
                'data' => $data,
                'next' => isset($headers['link']) ? $this->extractMastodonNextPageUrl($headers['link']) : null
            ];
        } catch (Exception $e) {
            return [
                'data' => [],
                'next' => null
            ];
        }
    }

    public function getPostUrlData(string $postUrl): array
    {
        if (!V::url($postUrl)) {
            return [
                null,
                null
            ];
        }

        $urlHost = parse_url($postUrl, PHP_URL_HOST);
        $urlPath = parse_url($postUrl, PHP_URL_PATH);
        $pathElements = explode('/', $urlPath);
        $postId = end($pathElements);

        if (empty($postId)) {
            $postId = null;
        }

        return [
            $urlHost,
            $postId
        ];
    }

    public function extractMastodonNextPageUrl($link)
    {
        $matches = [];
        preg_match('/<([^>]+)>; rel="next"/', $link, $matches);

        return $matches[1] ?? null;
    }

    public function postExists(string $postUrl): bool
    {
        $response = Remote::get($postUrl);
        if ($response->code() == 404) {
            return false;
        }
        return true;
    }

    public function fetchMastodonPostText(string $postUrl)
    {
        $response = Remote::get($postUrl, [
            'headers' => ['Accept: application/activity+json']
        ]);

        if ($response->code() === 200) {
            $data = json_decode($response->content(), true);
            return $data['content'];
        }
        return null;
    }
}
