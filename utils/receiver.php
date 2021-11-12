<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Url;
use Kirby\Toolkit\V;
use Kirby\Toolkit\Str;
use Kirby\Http\Remote;
use json_decode;
use json_encode;
use is_null;
use preg_split;
use str_replace;
use date;
use Mf2;

class WebmentionReceiver
{
    public function getPageFromUrl(string $url)
    {
        if (V::url($url)) {
            $path = Url::path($url);
            $languages = kirby()->languages();

            if ($languages->count() > 0) {
                foreach ($languages as $language) {
                    $languagePattern = '/^' . $language . '\//';
                    $path = preg_replace($languagePattern, '', $path);
                }
            }

            $targetPage = page($path);

            if (is_null($targetPage)) {
                return null;
            }

            return $targetPage;
        }

        return null;
    }

    public function createWebmention()
    {
    }

    public function getTransformedSourceUrl(string $url): string
    {
        if (V::url($url)) {
            if (strpos($url, 'brid.gy') !== false) {
                $bridyResult = Remote::get($url . '?format=json');
                $bridyJson = json_decode($bridyResult->content());
                $authorUrls = $bridyJson->properties->author[0]->properties->url;

                foreach ($authorUrls as $authorUrl) {
                    if ($this->isKnownNetwork($authorUrl)) {
                        return $authorUrl;
                    }
                }
            }

            return $url;
        }

        return '';
    }

    // TODO move to webmention.io specific class
    public function getWebmentionType(string $wmProperty)
    {
        /*
            in-reply-to
            like-of
            repost-of
            bookmark-of
            mention-of
            rsvp
        */
        switch ($wmProperty) {
            case 'like-of': return 'LIKE';
            case 'in-reply-to': return 'REPLY';
            case 'repost-of': return 'REPOST'; // retweet z.b.
            case 'mention-of': return 'MENTION'; // classic webmention z.b.
            default: return 'REPLY';
        }
    }

    public function getAuthor($webmention)
    {
        $authorInfo = $webmention->post->author;

        return [
            'type' => (isset($authorInfo->type) && !empty($authorInfo->type)) ? $authorInfo->type : null,
            'name' => (isset($authorInfo->name) && !empty($authorInfo->name)) ? $authorInfo->name : null,
            'avatar' => (isset($authorInfo->photo) && !empty($authorInfo->photo)) ? $authorInfo->photo : null,
            'url' => (isset($authorInfo->url) && !empty($authorInfo->url)) ? $authorInfo->url : null,
        ];
    }

    private function isKnownNetwork(string $authorUrl): boolean
    {
        $networkHosts = [
            'twitter.com',
            'instagram.com',
            'mastodon.online',
            'mastodon.social',
        ];

        foreach ($networkHosts as $host) {
            if (strpos($authorUrl, $host) !== false) {
                return true;
            }
        }

        return false;
    }
}
