<?php

namespace mauricerenck\IndieConnector;

use Exception;
use cjrasmussen\BlueskyApi\BlueskyApi;

class BlueskyReceiver
{
    private $bskClient = null;

    public function __construct(
        private ?bool $enabled = null,
        private ?string $handle = null,
        private ?string $password = null,
        $bskClient = null
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

        $this->bskClient = $bskClient ?: new BlueskyApi();
        $this->bskClient->auth($this->handle, $this->password);
    }

    public function getResponses(string $did, string $type, array $knownIds)
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $response = $this->paginateResponses($did, $type, null);
            $likes = $response['data'];

            if ($this->responsesIncludeKnownId($likes, $knownIds)) {
                return $likes;
            }

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($did, $type, $response['next']);
                $likes = [...$likes, ...$response['data']];

                // if there is a known Id in the loop set next to null to stop it
                if ($this->responsesIncludeKnownId($response['data'], $knownIds)) {
                    $response['next'] = null;
                }
            }

            return $likes;
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
        return !empty(array_intersect(array_map(fn($response) => $response->indieConnectorId, $responses), $knownIds));
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
                    $data = isset($response->likes) ? $this->appendIndieConnectorId($response->likes, 'likes') : [];
                    break;
                case 'reposts':
                    $data = isset($response->repostedBy) ? $this->appendIndieConnectorId($response->repostedBy, 'reposts') : [];
                    break;
                case 'quotes':
                    $data = isset($response->posts) ? $this->appendIndieConnectorId($response->posts, 'quotes') : [];
                    break;
                case 'replies':
                    $data = isset($response->thread->replies) ? $this->appendIndieConnectorId($response->thread->replies, 'replies') : [];
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

    /*
    *   unfortunately bluesky does not have Ids for every result entry, so we create them here
    */
    public function appendIndieConnectorId(array $responses, string $responseType): array
    {
        return array_map(function ($response) use ($responseType) {
            switch ($responseType) {
                case 'likes':
                    $id = md5($response->actor->did . $response->createdAt);
                    break;
                case 'reposts':
                    $id = md5($response->did . $response->createdAt);
                    break;
                case 'quotes':
                    $id = $response->cid;
                    break;
                case 'replies':
                    $id = $response->post->cid;
                    break;
            }

            $response->indieConnectorId = $id;
            return $response;
        }, $responses);
    }
}
