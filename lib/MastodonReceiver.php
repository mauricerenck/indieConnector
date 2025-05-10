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

    public function getResponses($postUrl, $type)
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            list($urlHost, $postId) = $this->getPostUrlData($postUrl);
            $response = $this->paginateResponses($urlHost, $postId, $type, null);
            $favs = $response['data'];

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($urlHost, $postId, $type, $response['next']);
                $favs = [...$favs, ...$response['data']];
            }

            return $favs;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return [];
        }
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
