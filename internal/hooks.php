<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Server;
use Kirby\Data\yaml;

return [
    'page.update:after' => function ($newPage, $oldPage) {
        // TODO
        // use sender
        $senderUtils = new SenderUtils($newPage);

        if (!$senderUtils->sendingIsEnabled($newPage)) {
            return;
        }

        if (!$senderUtils->pageFullfillsCriteria($newPage)) {
            return;
        }

        $urls = [];

        $urls = $senderUtils->findUrls($newPage);
        // should send webmention
        // should send mastodon
        // should ping archive.org

        // getUrls

        $webmentionSender = new WebmentionSender($newPage);
        if (option('mauricerenck.komments.send-mention-on-update', false) && !$newPage->isDraft() && $webmentionSender->templateIsWhitelisted($newPage->intendedTemplate())) {
            $sendWebmention = new WebmentionSender($newPage);
            $sendWebmention->send();
        }
    },
    'page.changeStatus:after' => function ($newPage, $oldPage) {
        if (option('mauricerenck.komments.send-to-mastodon-on-publish', false)) {
            $webmentionSender = new WebmentionSender($newPage);

            if ($newPage->isListed() && !$oldPage->isListed() && $webmentionSender->templateIsWhitelisted($newPage->intendedTemplate())) {
                $mastodon = new MastodonSender();
                $mastodon->sendToot($newPage);
            }
        }
    },
];
