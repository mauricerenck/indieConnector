<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use cjrasmussen\BlueskyApi\BlueskyApi;

class BlueskySender extends ExternalPostSender
{
    public function __construct(
        private ?string $handle = null,
        private ?string $password = null,
        private ?bool $enabled = null,
    ) {
        parent::__construct();

        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.bluesky.enabled', false);
        $this->handle = $password ?? option('mauricerenck.indieConnector.bluesky.handle', false);
        $this->password = $password ?? option('mauricerenck.indieConnector.bluesky.password', false);
    }

    public function sendPost($page, string | null $manualTextMessage = null)
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->password) {
            throw new Exception('No bluesky app password set');
            return false;
        }

        if (!$this->pageChecks->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->urlChecks->isLocalUrl($page->url())) {
            throw new Exception('Local url');
            return false;
        }

        if (!$this->pageChecks->pageHasEnabledExternalPosting($page)) {
            return false;
        }

        if ($this->alreadySentToTarget('bluesky', $page)) {
            return false;
        }

        try {
            $pageUrl = $this->getPostUrl($page);
            $trimTextPosition = $this->calculatePostTextLength($pageUrl);
            $language = 'en';

            $message = $this->getTextFieldContent($page, $trimTextPosition);
            $message = is_null($manualTextMessage) ? $this->getTextFieldContent($page, $trimTextPosition) : Str::short($manualTextMessage, $trimTextPosition);
            $message .= "\n" . $pageUrl;

            if ($defaultLanguage = kirby()->defaultLanguage()) {
                $language = $defaultLanguage->code();
            }

            if ($this->prefereLanguage !== false && !empty($this->prefereLanguage)) {
                $language = $this->prefereLanguage;
            }

            $bluesky = new BlueskyApi();
            $bluesky->auth($this->handle, $this->password);

            $args = [
                'collection' => 'app.bsky.feed.post',
                'record' => [
                    'text' => $message,
                    'langs' => [$language],
                    'createdAt' => date('c'),
                    '$type' => 'app.bsky.feed.post',
                ],
            ];

            if ($images = $this->getImages($page)) {

                $imageList = [];
                foreach ($images->toFiles()->limit(4) as $image) {
                    if (is_null($image)) {
                        continue;
                    }

                    $mediaAttachment = $this->getMediaAttachment($image);

                    if (!$mediaAttachment) {
                        continue;
                    }

                    $response = $bluesky->request(
                        'POST',
                        'com.atproto.repo.uploadBlob',
                        [],
                        $mediaAttachment['content'],
                        $mediaAttachment['mime']
                    );

                    $image = $response->blob;
                    $imageList[] = [
                        'alt' => $page->title()->value(),
                        'image' => $image,
                        'aspectRatio' => [
                            'width'  => $mediaAttachment['width'],
                            'height' => $mediaAttachment['height'],
                        ],
                    ];
                }

                $args['record']['embed'] = [
                    '$type' => 'app.bsky.embed.images',
                    'images' => $imageList,
                ];
            }

            $args['repo'] = $bluesky->getAccountDid();
            $args['record']['facets'] = $this->getLinks($message);

            $response = $bluesky->request('POST', 'com.atproto.repo.createRecord', $args);

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

    public function getMediaAttachment($image)
    {
        try {
            $imageMimeType = $image->mime();
            $resizedImage = $image->resize(800); // image size must be very low, so we need to resize it
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

    public function getDidFromUrl(string $url): string
    {
        // Regular expression to match the Bluesky post URL
        $regex = '/^https:\/\/bsky\.app\/profile\/(did:plc:[a-zA-Z0-9]+)\/post\/([a-zA-Z0-9]+)$/';

        // Check if the Bluesky URL matches the pattern
        if (preg_match($regex, $url, $matches)) {
            // Extract DID and RKEY from the matched groups
            $did = $matches[1];  // Group 1: DID
            $rkey = $matches[2]; // Group 2: RKEY

            // Generate the AT-URI
            return "at://$did/app.bsky.feed.post/$rkey";
        }

        return $url; // Return the original URL if it doesn't match
    }
}
