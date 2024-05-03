<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Url;
use Kirby\Toolkit\V;

class Receiver
{
    public function __construct()
    {
    }

    public function hasValidSecret($response)
    {
        return isset($response->secret) && $response->secret === option('mauricerenck.indieConnector.secret', '');
    }

    public function responseHasPostBody($response)
    {
        if (!isset($response->post)) {
            return false;
        }

        return true;
    }

    public function getTargetUrl($response)
    {
        if (!isset($response->target)) {
            return false;
        }

        if (!V::url($response->target)) {
            return false;
        }

        return $response->target;
    }

    public function getSourceUrl($response)
    {
        if (!isset($response->source)) {
            return false;
        }

        if (!V::url($response->source)) {
            return false;
        }

        if (strpos($response->source, '//localhost') === true || strpos($response->source, '//127.0.0') === true) {
            return false;
        }

        return $response->source;
    }

    public function getPageFromUrl(string $url): bool|object
    {
        $path = Url::path($url);

        if ($path == '') {
            $page = page(site()->homePageId());
        } elseif (!($page = page($path))) {
            $page = page(kirby()->router()->call($path));

            if ($page->isHomeOrErrorPage()) {
                return false;
            }
        }

        if (is_null($page)) {
            return false;
        }

        return $page;
    }

    public function createWebmention()
    {
    }

    public function getTransformedSourceUrl(string $url): string
    {
        if (V::url($url)) {
            return $url;
        }

        return '';
    }

    public function getWebmentionType($response)
    {
        if (!isset($response->post->{'wm-property'})) {
            return 'MENTION';
        }

        switch ($response->post->{'wm-property'}) {
            case 'like-of':
                return 'LIKE';
            case 'in-reply-to':
                return 'REPLY';
            case 'repost-of':
                return 'REPOST'; // retweet z.b.
            case 'mention-of':
                return 'MENTION'; // classic webmention z.b.
            case 'bookmark-of':
                return 'MENTION'; // classic webmention z.b.
            case 'rsvp':
                return 'MENTION'; // classic webmention z.b.
            default:
                return 'REPLY';
        }
    }

    public function getAuthor($response)
    {
        $authorInfo = $response->post->author;
        $author = [
            'type' => isset($authorInfo->type) && !empty($authorInfo->type) ? $authorInfo->type : null,
            'name' => isset($authorInfo->name) && !empty($authorInfo->name) ? $authorInfo->name : null,
            'avatar' => isset($authorInfo->photo) && !empty($authorInfo->photo) ? $authorInfo->photo : '',
            'url' => isset($authorInfo->url) && !empty($authorInfo->url) ? $authorInfo->url : null,
        ];

        if ($this->getWebmentionType($response) === 'MENTION') {
            if (is_null($author['name'])) {
                $author['name'] = $this->getSourceUrl($response);
            }
            if (is_null($author['url'])) {
                $author['url'] = $this->getSourceUrl($response);
            }
        }

        return $author;
    }

    public function getContent($response)
    {
        return isset($response->post->content) && isset($response->post->content->text)
            ? $response->post->content->text
            : '';
    }

    public function getPubDate($response)
    {
        return !is_null($response->post->published) ? $response->post->published : $response->post->{'wm-received'};
    }

    public function isKnownNetwork(string $authorUrl)
    {
        $networkHosts = ['x.com', 'twitter.com', 'instagram.com', 'mastodon.online', 'mastodon.social'];

        foreach ($networkHosts as $host) {
            if (strpos($authorUrl, $host) !== false) {
                return true;
            }
        }

        return false;
    }
}
