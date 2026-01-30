<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $mastodonSender = new MastodonSender();
        return $mastodonSender->getPostTargetUrl('mastodon', $this);
    },
    'icGetMastodonPostData' => function () {
        $mastodonSender = new MastodonSender();
        $mastodonReceiver = new MastodonReceiver();
        $responseCollector = new ResponseCollector();

        $url = $mastodonSender->getPostTargetUrl('mastodon', $this);
        $text = $mastodonReceiver->fetchMastodonPostText($url);
        $stats = $responseCollector->getMastodonPostResponseStats($url);

        $postData = [
            'text' => $text,
            'likes' => $stats['like-of'],
            'reposts' => $stats['repost-of'],
            'mentions' => $stats['mention-of'],
            'replies' => $stats['in-reply-to']
        ];

        return new Content($postData);
    },
    'icGetBlueskyUrl' => function () {
        $bluesky = new Bluesky();

        $urls = $bluesky->getUrlsFromPage($this);
        return $urls['http'];
    },
];
