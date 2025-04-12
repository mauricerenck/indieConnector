<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Filesystem\F;
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

    public function sendPost($page)
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

            if ($mediaAttachment = $this->getMediaAttachment($page)) {
                $response = $bluesky->request(
                    'POST',
                    'com.atproto.repo.uploadBlob',
                    [],
                    $mediaAttachment['content'],
                    $mediaAttachment['mime']
                );

                $image = $response->blob;

                $args['record']['embed'] = [
                    '$type' => 'app.bsky.embed.images',
                    'images' => [
                        [
                            'alt' => $page->title()->value(),
                            'image' => $image,
                            'aspectRatio' => [
                                'width'  => $mediaAttachment['width'],
                                'height' => $mediaAttachment['height'],
                            ],
                        ],
                    ],
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
    public function getMediaAttachment($page)
    {
        try {
            if ($this->imagefield) {
                $imagefield = $this->imagefield;
                $image = $page->$imagefield();


                if (!is_null($image) && $image->isNotEmpty()) {
                    $imageMimeType = $image->toFile()->mime();
                    $resizedImage = $image->toFile()->resize(800); // image size must be very low, so we need to resize it
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
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}
