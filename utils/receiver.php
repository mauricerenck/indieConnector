<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Url;
use Kirby\Http\Response;

class Receiver
{
    public function __construct()
    {
    }

    public function processIncomingWebmention($data)
    {
        $urlChecks = new UrlChecks();
        $pageChecks = new PageChecks();

        $urls = $this->getPostDataUrls($data);
        if (!$urlChecks->urlIsValid($urls['source'])) {
            return new Response('Source URL is not valid', 'text/plain', 406); // Not Acceptable
        }

        if (!$urlChecks->urlIsValid($urls['target'])) {
            return new Response('Target URL is not valid', 'text/plain', 406); // Not Acceptable
        }

        if ($urlChecks->isBlockedSource($urls['source'])) {
            return new Response('Source URL is blocked', 'text/plain', 406); // Not Acceptable
        }

        $page = $this->getPageFromUrl($urls['target']);

        if (!$page) {
            return new Response('Target page not found', 'text/plain', 404);
        }

        if (!$pageChecks->pageHasNeededStatus($page)) {
            return new Response('Target page not found', 'text/plain', 404);
        }

        return [
            'status' => 'success',
            'urls' => $urls,
        ];
    }

    public function hasValidSecret($postBody)
    {
        return isset($postBody['secret']) && $postBody['secret'] === option('mauricerenck.indieConnector.secret', '');
    }

    public function getPostDataUrls($postBody): array|bool
    {
        if (isset($postBody['source']) && isset($postBody['target'])) {
            return [
                'source' => $postBody['source'],
                'target' => $postBody['target'],
            ];
        }

        return false;
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

    public function convertToHookData($data, array $urls)
    {
        return [
            'type' => $data['type'],
            'target' => $urls['target'],
            'source' => $urls['source'],
            'published' => $data['published'],
            'title' => $data['title'],
            'content' => $data['content'],
            'author' => [
                'type' => 'card',
                'name' => $data['author']['name'],
                'avatar' => $data['author']['photo'],
                'url' => $data['author']['url'],
            ],
        ];
    }
    // =======

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
