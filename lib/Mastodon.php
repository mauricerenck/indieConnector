<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;

class Mastodon
{
    public function __construct(
        private ?string $instanceUrl = null,
        private ?string $token = null,
        private ?bool $enabled = null,
        private ?int $resizeImages = null,

        private ?Outbox $outbox = null,
        private ?ExternalPostSender $externalPostSender = null
    ) {
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.mastodon.enabled', false);
        $this->instanceUrl = $instanceUrl ?? option('mauricerenck.indieConnector.mastodon.instance-url', false);
        $this->token = $token ?? option('mauricerenck.indieConnector.mastodon.bearer', false);
        $this->resizeImages = $resizeImages ?? option('mauricerenck.indieConnector.mastodon.resizeImages', 0);

        $this->outbox = $outbox ?? new Outbox();
        $this->externalPostSender = $externalPostSender ?? new ExternalPostSender();
    }

    public function sendPost($page, string | null $manualTextMessage = null): mixed
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->token) {
            throw new Exception('No bluesky app password set');
            return false;
        }

        if (!$this->instanceUrl) {
            throw new Exception('No Mastodon Instance set');
            return false;
        }

        if (!$this->externalPostSender->preconditionsMet($page)) {
            return false;
        }

        try {
            $fullMessage = $this->externalPostSender->getTrimmedFullMessage(page: $page, manualTextMessage: $manualTextMessage, service: 'bluesky');
            $language = $this->externalPostSender->getPreferedLanguage();
            $altField = $this->externalPostSender->imageAltField;

            $mediaIds = [];
            if ($images = $this->externalPostSender->getImages($page)) {
                foreach ($images->toFiles()->limit(4) as $image) {
                    if (is_null($image)) {
                        continue;
                    }

                    if ($this->resizeImages !== 0) {
                        $resizedImage = $image->resize($this->resizeImages);
                        $resizedImage->base64(); // this forces kirby to generate the image

                        if (F::exists($resizedImage->root())) {
                            $image = $resizedImage;
                        }
                    }

                    $imagePath = $image->root();
                    $imageAlt = $image->{$altField}()->isNotEmpty() ? $image->{$altField}()->value() : '';
                    $mediaId = $this->uploadImage($imagePath, $imageAlt);

                    if (!$mediaId) {
                        continue;
                    }

                    $mediaIds[] = $mediaId;
                }
            }

            $requestBody = [
                'status' => $fullMessage,
                'visibility' => 'public',
                'language' => $language,
            ];

            if (count($mediaIds) > 0) {
                $requestBody['media_ids'] = $mediaIds;
            }


            $response = Remote::request($this->instanceUrl . '/api/v1/statuses', [
                'method' => 'POST',
                'headers' => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'User-Agent: IndieConnector/1.0 (+' . site()->url() . ')'
                ],
                'data' => json_encode($requestBody),
            ]);

            $result = $response->json();

            $url = $result['url'] ?? null;
            $id = $result['id'] ?? null;

            return [
                'id' => $id,
                'uri' => $url,
                'status' => 200,
                'target' => 'mastodon'
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    public function uploadImage($imagePath, $imageAlt)
    {
        try {
            if (!F::exists($imagePath)) {
                return false;
            }

            // Verwende das ursprünglich übergebene Bild direkt
            // (Die Größenoptimierung können wir später hinzufügen wenn nötig)
            $optimizedImagePath = $imagePath;

            // 2) CURLFile erzeugen
            $cfile = curl_file_create(
                $optimizedImagePath,
                mime_content_type($optimizedImagePath),
                basename($optimizedImagePath)
            );

            // 3) cURL-Request mit verbesserter Kompatibilität
            $url = rtrim($this->instanceUrl, '/') . '/api/v1/media';
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => [
                    'file' => $cfile,
                    'description' => $imageAlt
                ],
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->token,
                    'Accept: application/json',
                    'User-Agent: IndieConnector/1.0 (+' . site()->url() . ')',
                    'Expect:',  // verhindert "100-continue"-Handshake
                ],
            ]);

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 4) Erfolg prüfen und ID zurückgeben
            if ($httpCode !== 200 || empty($responseBody)) {
                return false;
            }

            $responseData = json_decode($responseBody, true);
            return $responseData['id'] ?? false;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
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

    public function paginateResponses(string $host, $postId, $type, $cursor)
    {
        switch ($type) {
            case 'like-of':
                $endpoint = 'favourited_by';
                break;
            case 'repost-of':
                $endpoint = 'reblogged_by';
                break;
            case 'in-reply-to':
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

    /*
         * Check the list and return bool, we will filter out the entry later we just
         * want to stop the pagination at this point
        */
    public function responsesIncludeKnownId($responses, $knownIds): bool
    {
        return (!empty(array_intersect(array_column($responses, 'id'), $knownIds)));
    }

    public function fetchResponseByType(string $postUrl, array $knownIds, string $type)
    {
        switch ($type) {
            case 'like-of':
                return $this->getLikes(knownIds: $knownIds, postUrl: $postUrl);
                break;
            case 'repost-of':
                return $this->getReposts(knownIds: $knownIds, postUrl: $postUrl);
                break;
            case 'in-reply-to':
                return $this->getReplies(knownIds: $knownIds, postUrl: $postUrl);
                break;
            default:
                return [];
                break;
        }
    }

    public function getLikes(array $knownIds, string $postUrl)
    {
        $favs = $this->getResponses($postUrl, 'like-of', $knownIds);

        if (count($favs) === 0) {
            continue;
        }

        $latestId = $favs[0]['id'];
        foreach ($favs as $fav) {
            if (!in_array($fav['id'], $knownIds)) {
                $likeQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $fav['id'],
                    'responseType' => 'like-of',
                    'responseSource' => 'mastodon',
                    'responseDate' => $this->currentDateTime(),
                    'responseText' => '',
                    'responseUrl' => $postUrl, // 'response_url' likes don't have a url, use post url instead
                    'authorId' => $fav['id'],
                    'authorName' => $fav['display_name'],
                    'authorUsername' => $fav['username'],
                    'authorAvatar' => $fav['avatar_static'],
                    'authorUrl' => $fav['url'],
                ];
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $likeQueueData,
        ];
    }

    public function getReposts(array $knownIds, string $postUrl)
    {
        $reposts = $this->getResponses($postUrl, 'repost-of', $knownIds);

        if (count($reposts) === 0) {
            continue;
        }

        $latestId = $reposts[0]['id'];
        foreach ($reposts as $repost) {
            if (!in_array($repost['id'], $knownIds)) {
                $likeQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $repost['id'],
                    'responseType' => 'repost-of',
                    'responseSource' => 'mastodon',
                    'responseDate' => $this->currentDateTime(),
                    'responseText' => '',
                    'responseUrl' => $postUrl,
                    'authorId' => $repost['id'],
                    'authorName' => $repost['display_name'],
                    'authorUsername' => $repost['username'],
                    'authorAvatar' => $repost['avatar_static'],
                    'authorUrl' => $repost['url'],
                ];
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $likeQueueData,
        ];
    }

    public function getReplies(array $knownIds, string $postUrl)
    {
        $replies = $this->getResponses($postUrl, 'in-reply-to', $knownIds);

        if (count($replies) === 0) {
            continue;
        }

        list($_urlHost, $postId) = $this->getPostUrlData($postUrl);
        $latestId = $replies[0]['id'];
        foreach ($replies as $reply) {
            if (!in_array($reply['id'], $knownIds)) {
                if ($reply['in_reply_to_id'] === $postId && $reply['visibility'] === 'public') {
                    $likeQueueData[] = [
                        'postUrl' => $postUrl,
                        'responseId' => $reply['id'],
                        'responseType' => 'in-reply-to',
                        'responseSource' => 'mastodon',
                        'responseDate' => $reply['created_at'],
                        'responseText' => $reply['content'],
                        'responseUrl' => $reply['url'],
                        'authorId' => $reply['account']['id'],
                        'authorName' => $reply['account']['display_name'],
                        'authorUsername' => $reply['account']['username'],
                        'authorAvatar' => $reply['account']['avatar_static'],
                        'authorUrl' => $reply['account']['url'],
                    ];
                }
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $likeQueueData,
        ];
    }
    /*
        * this class helps with testing date stuff as we can hand in a fixed date
        */
    public function currentDateTime($dateTime = null)
    {
        return $dateTime ?? date('Y-m-d H:i:s');
    }
}
