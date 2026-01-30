<?php

namespace mauricerenck\IndieConnector;

use cjrasmussen\BlueskyApi\BlueskyApi;
use Kirby\Http\Remote;
use Kirby\Filesystem\F;
use Exception;

class Bluesky
{
    private $connected = false;

    public function __construct(
        private ?bool $enabled = null,
        private ?string $handle = null,
        private ?string $password = null,
        private ?int $resizeImages = null,

        private ?Outbox $outbox = null,
        private ?BlueskyApi $bskClient = null,
        private ?ExternalPostSender $externalPostSender = null
    ) {
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.bluesky.enabled', false);
        $this->handle = $password ?? option('mauricerenck.indieConnector.bluesky.handle', false);
        $this->password = $password ?? option('mauricerenck.indieConnector.bluesky.password', false);
        $this->resizeImages = $resizeImages ?? option('mauricerenck.indieConnector.bluesky.resizeImages', 800);

        $this->outbox = $outbox ?? new Outbox();
        $this->externalPostSender = $externalPostSender ?? new ExternalPostSender();

        if (!$this->handle) {
            return;
        }

        if (!$this->password) {
            return;
        }

        $this->bskClient = $bskClient ?? new BlueskyApi();
    }

    public function connect()
    {
        $this->bskClient->auth($this->handle, $this->password);
        $this->connected = true;
    }

    public function sendPost($page, string | null $manualTextMessage = null): mixed
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->password) {
            throw new Exception('No bluesky app password set');
            return false;
        }

        if (!$this->externalPostSender->preconditionsMet($page)) {
            return false;
        }

        try {
            if (!$this->bskClient->auth($this->handle, $this->password)) {
                return false;
            }

            $fullMessage = $this->externalPostSender->getTrimmedFullMessage(page: $page, manualTextMessage: $manualTextMessage, service: 'bluesky');
            $language = $this->externalPostSender->getPreferedLanguage();
            $altField = $this->externalPostSender->imageAltField;

            $imageList = [];
            if ($images = $this->externalPostSender->getImages($page)) {
                foreach ($images->toFiles()->limit(4) as $image) {
                    if (is_null($image)) {
                        continue;
                    }

                    $mediaAttachment = $this->getMediaAttachment($image);

                    if (!$mediaAttachment) {
                        continue;
                    }

                    $altText = $image->{$altField}()->isNotEmpty() ? $image->{$altField}()->value() : '';

                    $response = $this->bskClient->request(
                        'POST',
                        'com.atproto.repo.uploadBlob',
                        [],
                        $mediaAttachment['content'],
                        $mediaAttachment['mime']
                    );

                    $image = $response->blob;
                    $imageList[] = [
                        'alt' => $altText ?? '',
                        'image' => $image,
                        'aspectRatio' => [
                            'width'  => $mediaAttachment['width'],
                            'height' => $mediaAttachment['height'],
                        ],
                    ];
                }
            }

            $postArgs = [
                'repo' => $this->bskClient->getAccountDid(),
                'collection' => 'app.bsky.feed.post',
                'record' => [
                    'text' => $fullMessage,
                    'langs' => [$language],
                    'createdAt' => date('c'),
                    '$type' => 'app.bsky.feed.post',
                    'facets' => array_merge($this->getLinks($fullMessage), $this->getHashtags($fullMessage))
                ],
            ];

            if (count($imageList) > 0) {
                $postArgs['record']['embed'] = [
                    '$type' => 'app.bsky.embed.images',
                    'images' => $imageList,
                ];
            }

            $response = $this->bskClient->request('POST', 'com.atproto.repo.createRecord', $postArgs);

            if (isset($response->error)) {
                return [
                    'id' => null,
                    'uri' => null,
                    'status' => 500,
                    'target' => 'bluesky'
                ];
            }

            return [
                'id' => $response->cid,
                'uri' => $response->uri,
                'status' => 200,
                'target' => 'bluesky'
            ];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        }
    }

    public function getUrlsFromPage($page)
    {
        // if the user manually entered a Bluesky URL, we will use it directly
        if ($page->blueskyStatusUrl()->isNotEmpty()) {
            $bskUrl = $page->blueskyStatusUrl()->value();
        } else {
            $urlData = $this->outbox->getExternalPostByNetwork($page, 'bluesky');
            $bskUrl = $urlData['url'] ?? null;
        }

        if (is_null($bskUrl)) {
            return [
                'at' => null,
                'http' => null
            ];
        }

        $atUrl = (str_starts_with('at://', $bskUrl)) ? $bskUrl : $this->getDidFromUrl($bskUrl);
        $httpUrl = (str_starts_with('http', $bskUrl)) ? $bskUrl : $this->getUrlFromDid($bskUrl);

        return [
            'at' => $atUrl,
            'http' => $httpUrl
        ];
    }

    public function getUrlFromDid(string $atUri): string
    {
        // Regular expression to match the DID and RKEY
        // $regex = '/^at:\/\/(did:plc:[a-zA-Z0-9]+)\/app\.bsky\.feed\.post\/([a-zA-Z0-9]+)$/';
        $regex = '/at:\/\/([^\/]+)\/app\.bsky\.feed\.post\/([^\/]+)/';

        // Check if the AT-URI matches the pattern
        if (preg_match($regex, $atUri, $matches)) {
            // Extract DID and RKEY from the matched groups
            $didOrHandle = $matches[1];  // Group 1: DID
            $postId = $matches[2]; // Group 2: postId


            if (strpos($didOrHandle, 'did:') === 0) {
                // It's a DID, need to resolve it to a handle
                $handle = $this->resolveDidToHandle($didOrHandle);
            } else {
                // It's already a handle
                $handle = $didOrHandle;
            }

            // Generate the Bluesky post URL
            return "https://bsky.app/profile/$handle/post/$postId";
        }

        return $atUri;
    }

    public function getDidFromUrl(string $url): string
    {
        // Regular expression to match the Bluesky post URL
        // $regex = '/^https:\/\/bsky\.app\/profile\/(did:plc:[a-zA-Z0-9]+)\/post\/([a-zA-Z0-9]+)$/';
        $regex = '/https?:\/\/bsky\.app\/profile\/([^\/]+)\/post\/([^\/\?]+)/';

        // Check if the Bluesky URL matches the pattern
        if (preg_match($regex, $url, $matches)) {
            // Extract DID and RKEY from the matched groups
            $handle = $matches[1];  // Group 1: DID
            $postId = $matches[2]; // Group 2: RKEY

            // Resolve handle to DID
            $did = $this->resolveHandleToDid($handle);

            // Generate the AT-URI
            return "at://$did/app.bsky.feed.post/$postId";
        }

        return $url; // Return the original URL if it doesn't match
    }

    public function didToData(string $atUri): array | null
    {
        if (!str_starts_with($atUri, 'at://')) {
            $atUri = $this->getDidFromUrl($atUri);
        }

        $withoutScheme = substr($atUri, 5); // remove 'at://'
        $parts = explode('/', $withoutScheme, 3); // split into [did, collection, rkey]

        if (count($parts) !== 3) {
            return null;
        }

        return [
            'did' => $parts[0],
            'collection' => $parts[1],
            'rkey' => $parts[2],
        ];
    }

    function resolveHandleToDid($handle)
    {
        $url = "https://bsky.social/xrpc/com.atproto.identity.resolveHandle?handle=" . urlencode($handle);

        $response = Remote::get($url);

        if ($response->code() !== 200) {
            return null;
        }

        $data = $response->json();
        return $data['did'] ?? null;
    }

    function resolveDidToHandle($did)
    {
        $url = "https://bsky.social/xrpc/com.atproto.repo.describeRepo?repo=" . urlencode($did);

        $response = Remote::get($url);

        if ($response->code() !== 200) {
            return null;
        }

        $data = $response->json();
        return $data['handle'] ?? null;
    }

    public function postExists(string $did): bool
    {
        $didData = $this->didToData($did);
        if (!$didData) {
            return false;
        }

        try {
            $urlCheckResponse = Remote::get('https://bsky.social/xrpc/com.atproto.repo.getRecord', [
                'data' => [
                    'repo' => $didData['did'],
                    'collection' => $didData['collection'],
                    'rkey' => $didData['rkey'],
                ],

            ]);

            if ($urlCheckResponse->code() === 404 || $urlCheckResponse->code() === 400) {
                $responseCollector = new ResponseCollector();
                $responseCollector->disablePostUrls([$did]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getResponses(string $did, string $type, array $knownIds)
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            $response = $this->paginateResponses($did, $type, null);
            $entries = $response['data'];

            if ($this->responsesIncludeKnownId($entries, $knownIds)) {
                return $entries;
            }

            while ($response['next'] !== null) {
                $response = $this->paginateResponses($did, $type, $response['next']);
                $entries = [...$entries, ...$response['data']];

                // if there is a known Id in the loop set next to null to stop it
                if ($this->responsesIncludeKnownId($response['data'], $knownIds)) {
                    $response['next'] = null;
                }
            }

            return $entries;
        } catch (Exception $e) {
            // throw new Exception($e->getMessage()); FIXME this shouldnt be here?
            return [];
        }
    }

    public function fetchResponseByType(string $postUrl, array $knownIds, string $type)
    {
        $did = $this->getDidFromUrl($postUrl);

        switch ($type) {
            case 'like-of':
                return $this->getLikes(did: $did, knownIds: $knownIds, postUrl: $postUrl);
                break;
            case 'repost-of':
                return $this->getReposts(did: $did, knownIds: $knownIds, postUrl: $postUrl);
                break;
            case 'mention-of':
                return $this->getQuotes(did: $did, knownIds: $knownIds, postUrl: $postUrl);
                break;
            case 'in-reply-to':
                return $this->getReplies(did: $did, knownIds: $knownIds, postUrl: $postUrl);
                break;
            default:
                return [];
                break;
        }
    }

    public function getLikes(string $did, array $knownIds, string $postUrl)
    {
        $likes = $this->getResponses($did, 'like-of', $knownIds);

        if (count($likes) === 0) {
            return [];
        }

        $latestId = $likes[0]->indieConnectorId;
        $likeQueueData = [];

        foreach ($likes as $like) {
            if (!in_array($like->indieConnectorId, $knownIds)) {
                $displayName = (!empty($like->actor->displayName)) ? $like->actor->displayName : $like->actor->handle;
                $avatar = isset($like->actor->avatar) ? $like->actor->avatar : '';

                $likeQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $like->indieConnectorId,
                    'responseType' => 'like-of',
                    'responseSource' => 'bluesky',
                    'responseDate' => $like->createdAt,
                    'responseText' => '',
                    'responseUrl' => $postUrl, // 'response_url' likes don't have a url, use post url instead
                    'authorId' => $like->actor->did,
                    'authorName' => $displayName,
                    'authorUsername' => $like->actor->handle,
                    'authorAvatar' => $avatar,
                    'authorUrl' => 'https://bsky.app/profile/' . $like->actor->handle,
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

    public function getReposts(string $did, array $knownIds, string $postUrl)
    {
        $reposts = $this->getResponses($did, 'repost-of', $knownIds);

        if (count($reposts) === 0) {
            return [];
        }

        $latestId = $reposts[0]->indieConnectorId;
        $repostQueueData = [];

        foreach ($reposts as $repost) {
            if (!in_array($repost->indieConnectorId, $knownIds)) {
                $displayName = (!empty($repost->displayName)) ? $repost->displayName : $repost->handle;
                $avatar = isset($repost->avatar) ? $repost->avatar : '';

                $repostQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $repost->indieConnectorId,
                    'responseType' => 'repost-of',
                    'responseSource' => 'bluesky',
                    'responseDate' => $repost->createdAt,
                    'responseText' => '',
                    'responseUrl' => $postUrl,
                    'authorId' => $repost->did,
                    'authorName' => $displayName,
                    'authorUsername' => $repost->handle,
                    'authorAvatar' => $avatar,
                    'authorUrl' => 'https://bsky.app/profile/' . $repost->handle,
                ];
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $repostQueueData,
        ];
    }

    public function getQuotes(string $did, array $knownIds, string $postUrl)
    {
        $quotes = $this->getResponses($did, 'mention-of', $knownIds);

        if (count($quotes) === 0) {
            return [];
        }

        $latestId = $quotes[0]->indieConnectorId;
        $quotesQueueData = [];

        foreach ($quotes as $quote) {
            if (!in_array($quote->indieConnectorId, $knownIds)) {
                $displayName = (!empty($quote->author->displayName)) ? $quote->author->displayName : $quote->author->handle;
                $avatar = isset($quote->author->avatar) ? $quote->author->avatar : '';
                $responseUrl = $this->getUrlFromDid($quote->uri);

                $quotesQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $quote->indieConnectorId,
                    'responseType' => 'mention-of',
                    'responseSource' => 'bluesky',
                    'responseDate' => $quote->record->createdAt,
                    'responseText' => $quote->record->text ?? '',
                    'responseUrl' =>  $responseUrl,
                    'authorId' => $quote->author->did,
                    'authorName' => $displayName,
                    'authorUsername' => $quote->author->handle,
                    'authorAvatar' => $avatar,
                    'authorUrl' => 'https://bsky.app/profile/' . $quote->author->handle,
                ];
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $quotesQueueData,
        ];
    }

    public function getReplies(string $did, array $knownIds, string $postUrl)
    {
        $replies = $this->getResponses($did, 'in-reply-to', $knownIds);

        if (count($replies) === 0) {
            return [];
        }

        $latestId = $replies[0]->indieConnectorId;
        $quotesQueueData = [];

        foreach ($replies as $reply) {
            if (!in_array($reply->indieConnectorId, $knownIds)) {
                $displayName = (!empty($reply->post->author->displayName)) ? $reply->post->author->displayName : $reply->post->author->handle;
                $avatar = isset($reply->post->author->avatar) ? $reply->post->author->avatar : '';
                $responseUrl = $this->getUrlFromDid($reply->post->uri);

                $quotesQueueData[] = [
                    'postUrl' => $postUrl,
                    'responseId' => $reply->indieConnectorId,
                    'responseType' => 'in-reply-to',
                    'responseSource' => 'bluesky',
                    'responseDate' => $reply->post->record->createdAt,
                    'responseText' => $reply->post->record->text ?? '',
                    'responseUrl' => $responseUrl,
                    'authorId' => $reply->post->author->did,
                    'authorName' => $displayName,
                    'authorUsername' => $reply->post->author->handle,
                    'authorAvatar' => $avatar,
                    'authorUrl' => 'https://bsky.app/profile/' . $reply->post->author->handle,
                ];
            } else {
                break;
            }
        }

        return [
            'latestId' => $latestId,
            'data' => $quotesQueueData,
        ];
    }

    // Check the list and return bool, we will filter out the entry later we just
    // want to stop the pagination at this point
    public function responsesIncludeKnownId($responses, $knownIds): bool
    {
        return !empty(array_intersect(array_map(fn($response) => $response->indieConnectorId, $responses), $knownIds));
    }

    public function paginateResponses(string $did, string $type, $cursor)
    {
        $args = [
            'uri' => $did,
            'limit' => 50,
        ];

        switch ($type) {
            case 'like-of':
                $endpoint = 'getLikes';
                break;
            case 'repost-of':
                $endpoint = 'getRepostedBy';
                break;
            case 'mention-of':
                $endpoint = 'getQuotes';
                break;
            case 'in-reply-to':
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

            if (!$this->connected) {
                $this->connect();
            }

            $response = $this->bskClient->request('GET', 'app.bsky.feed.' . $endpoint, $args);

            switch ($type) {
                case 'like-of':
                    $data = isset($response->likes) ? $this->appendIndieConnectorId($response->likes, 'like-of') : [];
                    break;
                case 'repost-of':
                    $data = isset($response->repostedBy) ? $this->appendIndieConnectorId($response->repostedBy, 'repost-of') : [];
                    break;
                case 'mention-of':
                    $data = isset($response->posts) ? $this->appendIndieConnectorId($response->posts, 'mention-of') : [];
                    break;
                case 'in-reply-to':
                    $data = isset($response->thread->replies) ? $this->appendIndieConnectorId($response->thread->replies, 'in-reply-to') : [];
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

    // Unfortunately bluesky does not have Ids for every result entry, so we create them here
    public function appendIndieConnectorId(array $responses, string $responseType): array
    {
        return array_map(function ($response) use ($responseType) {
            switch ($responseType) {
                case 'like-of':
                    $id = md5($response->actor->did . $response->createdAt);
                    break;
                case 'repost-of':
                    $id = md5($response->did . $response->createdAt);
                    break;
                case 'mention-of':
                    $id = $response->cid;
                    break;
                case 'in-reply-to':
                    $id = $response->post->cid;
                    break;
            }

            $response->indieConnectorId = $id;
            return $response;
        }, $responses);
    }

    public function getMediaAttachment($image)
    {
        try {
            $imageMimeType = $image->mime();
            $resizedImage = $image->resize($this->resizeImages); // image size must be very low, so we need to resize it
            $resizedImage->base64(); // this forces kirby to generate the image

            if (!F::exists($resizedImage->root())) {
                return false;
            }

            return [
                'content' => file_get_contents($resizedImage->root()),
                'mime' => $imageMimeType,
                'width' => $resizedImage->width(),
                'height' => $resizedImage->height()
            ];
        } catch (Exception $e) {
            return false;
        }
    }

    public function getLinks($message)
    {
        $links = [];
        $regex = '/(https?:\/\/[^\s]+)/';
        preg_match_all($regex, $message, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $match) {
            $url = $match[0];
            $start = $match[1];
            $end = $start + strlen($url);

            $links[] = [
                'index' => [
                    'byteStart' => $start,
                    'byteEnd' => $end,
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#link',
                        'uri' => $url,
                    ],
                ],
            ];
        }

        return $links;
    }

    public function getHashtags($message)
    {
        $hashtags = [];
        $regex = '/(^|\s)#(\p{L}[\p{L}\p{N}_]*)/u'; // captures #tag with letters/numbers/underscore

        preg_match_all($regex, $message, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $index => $match) {
            $fullMatch = $match[0];
            $start = $match[1] + (substr($fullMatch, 0, 1) === '#' ? 0 : 1); // skip space if present
            $tagText = $matches[2][$index][0]; // without the '#'

            // Bluesky wants byte offsets, not character positions
            $byteStart = strlen(mb_strcut($message, 0, $start, 'UTF-8'));
            $byteEnd   = $byteStart + strlen($fullMatch) - ($fullMatch[0] === '#' ? 0 : 1);

            $hashtags[] = [
                'index' => [
                    'byteStart' => $byteStart,
                    'byteEnd'   => $byteEnd,
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#tag',
                        'tag'   => $tagText,
                    ],
                ],
            ];
        }

        return $hashtags;
    }
}
