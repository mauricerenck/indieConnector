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

        if (!$newPage->isDraft() && $oldPage->isDraft()) {

            $postResults = [];

            $mastodonSender = new MastodonSender();
            $mastodonPost = $mastodonSender->sendPost($newPage);
            if ($mastodonPost !== false) {
                $postResults[] = $mastodonPost;
            }

            $blueskySender = new BlueskySender();
            $blueskyPost = $blueskySender->sendPost($newPage);

            if ($blueskyPost !== false) {
                $postResults[] = $blueskyPost;
            }

            $mastodonSender->updateExternalPosts($postResults, $newPage);
        }
    },

    'page.delete:after' => function ($page) {
        $webmentions = new WebmentionSender();
        if ($webmentions->markPageAsDeleted($page)) {
            $webmentions->sendWebmentions($page);
        }
    },

    'page.create:after' => function ($page) {
        $webmentions = new WebmentionSender();
        $webmentions->removePageFromDeleted($page);
    },

    'page.changeSlug:after' => function ($newPage) {
        $webmentions = new WebmentionSender();
        $webmentions->removePageFromDeleted($newPage);
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
            $page = page($targetPage);

            $stats->trackMention(
                $page->id(),
                $webmention['source'],
                $webmention['type'],
                $webmention['author']['avatar'],
                $webmention['author']['name'],
                $webmention['title']
            );
        }
    },

    'indieConnector.webmention.send' => function ($page, $targetUrl, $sourceUrl) {
        if (option('mauricerenck.indieConnector.send.hook.enabled', false)) {
            $webmentions = new WebmentionSender();
            $webmentions->sendWebmentionFromHook($page, $targetUrl, $sourceUrl);
        }
    },
];
