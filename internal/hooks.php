<?php

namespace mauricerenck\IndieConnector;

use Kirby\Data\yaml;

return [
    'page.update:after' => function ($newPage, $oldPage) {
        if (!option('mauricerenck.indieConnector.sendWebmention', true)) {
            return;
        }

        $senderUtils = new SenderUtils();

        if (!$senderUtils->pageFullfillsCriteria($newPage)) {
            return;
        }

        $urls = $senderUtils->findUrls($newPage);

        if (count($urls) === 0) {
            return;
        }

        $cleanedUrls = $senderUtils->cleanupUrls($urls, $newPage);

        if (count($cleanedUrls) === 0) {
            return;
        }

        $processedUrls = [];
        if ($senderUtils->shouldSendWebmention()) {
            $sendWebmention = new WebmentionSender();

            foreach ($cleanedUrls as $url) {
                $sent = $sendWebmention->send($url, $newPage->url());

                if ($sent) {
                    $processedUrls[] = $url;
                }
            }
        }

        $senderUtils->storeProcessedUrls($urls, $newPage);
    },

    'page.changeStatus:after' => function ($newPage, $oldPage) {
        if (option('mauricerenck.indieConnector.sendMastodon', false)) {
            if (!$newPage->isDraft() && $oldPage->isDraft()) {
                $mastodonSender = new MastodonSender();
                $mastodonSender->sendToot($newPage);
            }
        }
    },
];
