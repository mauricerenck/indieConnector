<?php

namespace mauricerenck\IndieConnector;

class Bluesky
{
    public function getBlueskyUrl($page)
    {
        $blueskySender = new Sender();
        $atUri = $blueskySender->getPostTargetUrl('bluesky', $page);

        if (is_null($atUri)) {
            return [
                'at' => null,
                'http' => null
            ];
        }

        $atUrl = (str_starts_with('at://', $atUri)) ? $this->getDidFromUrl($atUri) : $atUri;
        $httpUrl = (str_starts_with('http', $atUri)) ? $atUri : $this->getUrlFromDid($atUri);

        return [
            'at' => $atUrl,
            'http' => $httpUrl
        ];
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
