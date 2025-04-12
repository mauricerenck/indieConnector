<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $mastodonSender = new MastodonSender();
        return $mastodonSender->getPostTargetUrl('mastodon', $this);
    },
    'icGetBlueskyUrl' => function () {
        $mastodonSender = new BlueskySender();
        $atUri = $mastodonSender->getPostTargetUrl('bluesky', $this);

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
    },
];
