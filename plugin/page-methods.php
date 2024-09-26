<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $mastodonSender = new MastodonSender();
        return $mastodonSender->getPostTargetUrl('mastodon', $this);
    },
    'icGetBlueskyUrl' => function () {
        $mastodonSender = new BlueskySender();
        return $mastodonSender->getPostTargetUrl('bluesky', $this);
    },
];
