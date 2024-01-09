<?php

namespace mauricerenck\IndieConnector;

return [
    'page.update:after' => function ($newPage) {
        $webmentions = new WebmentionSender();

        $urls = $webmentions->getUnprocessedUrls($newPage);
        $webmentions->sendWebmentions($newPage, $urls);
    },

    'page.changeStatus:after' => function ($newPage, $oldPage) {
        $webmentions = new WebmentionSender();

        $urls = $webmentions->getUnprocessedUrls($newPage);
        $webmentions->sendWebmentions($newPage, $urls);

        if (option('mauricerenck.indieConnector.sendMastodon', false)) {
            // FIXME use existing sender class for tests
            if (!$newPage->isDraft() && $oldPage->isDraft()) {
                $mastodonSender = new MastodonSender();
                $mastodonSender->sendToot($newPage);
            }
        }
    },

    'system.loadPlugins:after' => function () {
        $migrations = new Migrations();
        $migrations->migrate();
    }
];
