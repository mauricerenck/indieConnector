<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\V;
use Kirby\Cms\Url;

class UrlHandler
{
    private $indieDb;

    public function __construct(
        private ?array $localHosts = null,
        private ?array $blockedSources = null,
        private ?array $blockedTargets = null,
        private ?IndieConnectorDatabase $indieDatabase = null
    ) {
        $this->localHosts =
            $localHosts ?? option('mauricerenck.indieConnector.localhosts', ['//localhost', '//127.0.0.1']);
        $this->blockedSources = $blockedSources ?? option('mauricerenck.indieConnector.blockedSources', []);
        $this->blockedTargets = $blockedTargets ?? option('mauricerenck.indieConnector.blockedTargets', []);
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
    }

    public function urlIsValid(string $url): bool
    {
        return V::url($url);
    }

    public function urlExists(string $url): bool
    {
        $rejectedStatusCodes = [404, 403, 401, 400, 500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);

        if (!$result) {
            return false;
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (in_array($statusCode, $rejectedStatusCodes)) {
            return false;
        }

        return true;
    }

    public function isLocalUrl(string $url): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        return in_array($urlHost, $this->localHosts);
    }

    public function skipSameHost($url)
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $host = kirby()->environment()->host();

        // backwards compatibility
        $skip =
            option('mauricerenck.indieConnector.skipSameHost', null) ??
            option('mauricerenck.indieConnector.send.skipSameHost', true);

        return $skip && $urlHost === $host;
    }

    public function isBlockedSource(string $url): bool
    {
        $host = Url::stripPath($url);

        $dbSources = $this->indieDb->select(
            'blocked_urls',
            ['url', 'direction', 'created_at'],
            'WHERE url LIKE "' . $host . '%" AND direction = "incoming"'
        );

        $dbSourceUrls = array_column($dbSources->toArray(), 'url');
        $blockedSources = array_merge($this->blockedSources, $dbSourceUrls);

        $blockedSourceUrl = in_array($url, $blockedSources);
        $blockedSourceHost = in_array($host, $blockedSources);

        return $blockedSourceUrl || $blockedSourceHost;
    }

    public function isBlockedTarget(string $url): bool
    {
        $host = Url::stripPath($url);

        $dbTargets = $this->indieDb->select(
            'blocked_urls',
            ['url', 'direction', 'created_at'],
            'WHERE url LIKE "' . $host . '%" AND direction = "outgoing"'
        );

        $dbSourceUrls = array_column($dbTargets->toArray(), 'url');
        $blockedTargets = array_merge($this->blockedTargets, $dbSourceUrls);

        $blockedSourceUrl = in_array($url, $blockedTargets);
        $blockedSourceHost = in_array($host, $blockedTargets);

        return $blockedSourceUrl || $blockedSourceHost;
    }

    public function blockUrl(string $url, string $direction, bool $hostOnly): bool
    {
        $url = $hostOnly ? Url::stripPath($url) : $url;

        $dbResult = $this->indieDb->insert(
            'blocked_urls',
            ['url', 'direction'],
            [$url, $direction]
        );

        return $dbResult;
    }
}
