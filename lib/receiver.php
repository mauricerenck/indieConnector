<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Url;
use Kirby\Http\Response;

class Receiver
{
    public function __construct(private ?bool $receiveWebmention = null)
    {
        $this->receiveWebmention = $receiveWebmention ?? option('mauricerenck.indieConnector.receive.enabled', true);
    }

    public function processIncomingWebmention($data)
    {
        $urlChecks = new UrlChecks();
        $pageChecks = new PageChecks();

        if (!$this->receiveWebmention) {
            return new Response('Webmention receiving is disabled', 'text/plain', 405); // Method Not Allowed
        }

        $urls = $this->getPostDataUrls($data);
        if (!$urlChecks->urlIsValid($urls['source'])) {
            return new Response('Source URL is not valid', 'text/plain', 400); // Not Acceptable
        }

        if (!$urlChecks->urlIsValid($urls['target'])) {
            return new Response('Target URL is not valid', 'text/plain', 400); // Not Acceptable
        }

        if ($urlChecks->isBlockedSource($urls['source'])) {
            return new Response('Source URL is blocked', 'text/plain', 400); // Not Acceptable
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
        return isset($postBody['secret']) && $postBody['secret'] === option('mauricerenck.indieConnector.secret', '') && $postBody['secret'] !== '';
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
            $page = kirby()->router()->call($path, 'GET');

            if (!$page) {
                return false;
            }

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
            'service' => $data['service'],
            'author' => [
                'type' => 'card',
                'name' => $data['author']['name'],
                'avatar' => $data['author']['photo'],
                'url' => $data['author']['url'],
            ],
        ];
    }
}
