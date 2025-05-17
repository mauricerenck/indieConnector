<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;

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
            $response = $this->paginateResponses($urlHost, $postId, $type, null);
            $favs = $response['data'];

            if ($this->responsesIncludeKnownId($favs, $knownIds)) {
                return $favs;
            }

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($urlHost, $postId, $type, $response['next']);
                $favs = [...$favs, ...$response['data']];

                // if there is a known Id in the loop set next to null to stop it
                if ($this->responsesIncludeKnownId($response['data'], $knownIds)) {
                    $response['next'] = null;
                }
            }

            return $favs;
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
            $url = ($cursor) ?  $cursor : $host . '/api/v1/statuses/' . $postId . '/' . $endpoint;
            $response = Remote::get($url);
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
        $urlHost = parse_url($postUrl, PHP_URL_HOST);
        $urlPath = parse_url($postUrl, PHP_URL_PATH);
        $pathElements = explode('/', $urlPath);
        $postId = end($pathElements);

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
}
