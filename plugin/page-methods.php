<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $mastodonSender = new MastodonSender();
        return $mastodonSender->getPostTargetUrl('mastodon', $this);
    },
    'icGetBlueskyUrl' => function () {
        $blueskySender = new BlueskySender();
        $atUri = $blueskySender->getPostTargetUrl('bluesky', $this);

        if (is_null($atUri)) {
            return '';
        }

        return $blueskySender->getUrlFromDid($atUri);
    },
];
