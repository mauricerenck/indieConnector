<?php

namespace mauricerenck\IndieConnector;

return [
    'page.update:after' => function ($newPage) {
        $webmentions = new WebmentionSender();
        $webmentions->sendWebmentions($newPage);
    },

    'page.changeStatus:after' => function ($newPage, $oldPage) {

        $webmentions = new WebmentionSender();
        $webmentions->sendWebmentions($newPage);

        if (option('mauricerenck.indieConnector.sendMastodon', false)) {
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
