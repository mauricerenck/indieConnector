<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;
use Exception;
use Kirby\Filesystem\F;
use cjrasmussen\BlueskyApi\BlueskyApi;

class BlueskySender extends Sender
{
    private $maxPostLength = 300;

    public function __construct(
        private ?string $textfield = null,
        private ?string $imagefield = null,
        private ?string $handle = null,
        private ?string $password = null,
        private ?bool $enabled = null,
        private ?UrlChecks $urlChecks = null,
        private ?PageChecks $pageChecks = null
    ) {
        $this->textfield = $textfield ?? option('mauricerenck.indieConnector.post.textfield', 'description');
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);

        $this->handle = $password ?? option('mauricerenck.indieConnector.bluesky.handle', false);
        $this->password = $password ?? option('mauricerenck.indieConnector.bluesky.password', false);
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.bluesky.enabled', false);

        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();

        // backwards compatibility
        if (!$textfield && option('mauricerenck.indieConnector.mastodon-text-field', false)) {
            $this->textfield = option('mauricerenck.indieConnector.mastodon-text-field');
        }
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

        try {
            $pageUrl = $page->url();
            $trimTextPosition = $this->calculatePostTextLength($page->url());
            $textfield = $this->textfield;
            $language = 'en';

            $message = $page->$textfield()->isNotEmpty()
                ? $page->$textfield()->value()
                : Str::short($page->title(), $trimTextPosition);
            $message .= "\n" . $pageUrl;

            if ($defaultLanguage = kirby()->defaultLanguage()) {
                $language = $defaultLanguage->code();
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
                        ],
                    ],
                ];
            }

            $args['repo'] = $bluesky->getAccountDid();
            $args['record']['facets'] = $this->getLinks($message);

            $response = $bluesky->request('POST', 'com.atproto.repo.createRecord', $args);

            return true;
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
                    $imagePath = $image->toFile()->resize(800)->root(); // TODO image size must be very low, so we need to resize it

                    if (!F::exists($imagePath)) {
                        return false;
                    }

                    return [
                        'content' => file_get_contents($imagePath),
                        'mime' => $imageMimeType,
                    ];
                }
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function calculatePostTextLength(string $url)
    {
        $urlLength = Str::length($url);
        return $this->maxPostLength - $urlLength - 2;
    }
}
