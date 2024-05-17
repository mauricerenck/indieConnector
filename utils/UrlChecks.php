<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\V;
use Kirby\Cms\Url;

class UrlChecks
{
    public function __construct(private ?array $localHosts = null, private ?array $blockedSources = null)
    {
        $this->localHosts =
            $localHosts ?? option('mauricerenck.indieConnector.debug.localHosts', ['//localhost', '//127.0.0.1']);
        $this->blockedSources = $blockedSources ?? option('mauricerenck.indieConnector.block.sources', []);
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
        return !in_array($url, $this->localHosts);
    }

    public function isBlockedSource(string $url): bool
    {
        // TODO also get blocked sources from database
        $blockedSourceUrl = in_array($url, $this->blockedSources);
        $blockedSourceHost = in_array(Url::stripPath($url), $this->blockedSources);

        return $blockedSourceUrl || $blockedSourceHost;
    }
}
