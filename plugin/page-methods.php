<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $sender = new ExternalPostSender();
        $data = $sender->getPostTargetUrlAndStatus('mastodon', $this);
        return $data['url'];
    },
    'icGetBlueskyUrl' => function () {
        $bluesky = new Bluesky();

        $urls = $bluesky->getUrlsFromPage($this);
        return $urls['http'];
    },
];
