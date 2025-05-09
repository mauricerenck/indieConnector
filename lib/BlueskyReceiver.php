<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Filesystem\F;
use cjrasmussen\BlueskyApi\BlueskyApi;

class BlueskyReceiver
{

    private $bskClient = null;

    public function __construct(
        private ?string $handle = null,
        private ?string $password = null,
        private ?bool $enabled = null,
    ) {
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.bluesky.enabled', false);
        $this->handle = $password ?? option('mauricerenck.indieConnector.bluesky.handle', false);
        $this->password = $password ?? option('mauricerenck.indieConnector.bluesky.password', false);

        if (!$this->handle) {
            throw new Exception('No bluesky handle set');
            return;
        }

        if (!$this->password) {
            throw new Exception('No bluesky app password set');
            return;
        }

        $this->bskClient = new BlueskyApi();
        $this->bskClient->auth($this->handle, $this->password);
    }

    public function getResponses($did, $type)
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $response = $this->paginateResponses($did, $type, null);
            $likes = $response['data'];

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($did, $type, $response['next']);
                $likes = [...$likes, ...$response['data']];
            }

            return $likes;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return [];
        }
    }

    public function paginateResponses(string $did, $type, $cursor)
    {
        $args = [
            'uri' => $did,
            'limit' => 50,
        ];

        switch ($type) {
            case 'likes':
                $endpoint = 'getLikes';
                break;
            case 'reposts':
                $endpoint = 'getRepostedBy';
                break;
            case 'quotes':
                $endpoint = 'getQuotes';
                break;
            case 'replies':
                $endpoint = 'getPostThread';
                $args['depth'] = 1;
                $args['parentHeight'] = 0;
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
            if ($cursor) {
                $args['cursor'] = $cursor;
            }

            $response = $this->bskClient->request('GET', 'app.bsky.feed.' . $endpoint, $args);

            switch ($type) {
                case 'likes':
                    $data = isset($response->likes) ? $response->likes : [];
                    break;
                case 'reposts':
                    $data = isset($response->repostedBy) ? $response->repostedBy : [];
                    break;
                case 'quotes':
                    $data = isset($response->posts) ? $response->posts : [];
                    break;
                case 'replies':
                    $data = isset($response->thread->replies) ? $response->thread->replies : [];
                    break;
            }

            return [
                'data' => $data,
                'next' => $response->cursor ?? null
            ];
        } catch (Exception $e) {
            return [
                'data' => [],
                'next' => null
            ];
        }
    }

    public function getUrlFromDid(string $atUri): string
    {
        // Regular expression to match the DID and RKEY
        $regex = '/^at:\/\/(did:plc:[a-zA-Z0-9]+)\/app\.bsky\.feed\.post\/([a-zA-Z0-9]+)$/';

        // Check if the AT-URI matches the pattern
        if (preg_match($regex, $atUri, $matches)) {
            // Extract DID and RKEY from the matched groups
            $did = $matches[1];  // Group 1: DID
            $rkey = $matches[2]; // Group 2: RKEY

            // Generate the Bluesky post URL
            return "https://bsky.app/profile/$did/post/$rkey";
        }

        return $atUri;
    }
}
