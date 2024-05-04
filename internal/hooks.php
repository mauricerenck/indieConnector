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
    },

    'indieConnector.webmention.queue' => function ($targetUrl, $sourceUrl) {
        $queueHandler = new QueueHandler();

        if ($queueHandler->queueEnabled()) {
            $queueHandler->queueWebmention($sourceUrl, $targetUrl);
            return;
        }

        $webmentionReceiver = new WebmentionReceiver($sourceUrl, $targetUrl);
        $webmentionReceiver->processWebmention($sourceUrl, $targetUrl);
        return;
    },

    'indieConnector.webmention.processQueue' => function ($limit) {
        $queueHandler = new QueueHandler();

        if ($queueHandler->queueEnabled()) {
            $queueHandler->processQueue($limit);
            return;
        }

        return;
    },

    'indieConnector.webmention.received' => function ($webmention, $targetPage) {
        if (option('mauricerenck.indieConnector.stats.enabled', false)) {
            $stats = new WebmentionStats();
            $stats->trackMention(
                $webmention['target'],
                $webmention['source'],
                $webmention['type'],
                $webmention['author']['avatar']
            );
        }
    },
];
