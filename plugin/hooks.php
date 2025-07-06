<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;

return [
    'page.update:after' => function ($newPage, $oldPage) {
        $responseCollector = new ResponseCollector();

        if (option('mauricerenck.indieConnector.send.automatically', true)) {
            $webmentions = new WebmentionSender();
            $webmentions->sendWebmentions($newPage);
        }

        if ($mastodonUrl = $newPage->mastodonStatusUrl()) {
            if ($oldPage->mastodonStatusUrl() === $mastodonUrl || $mastodonUrl->isEmpty()) {
                return;
            }

            $responseCollector->registerPostUrl($newPage->uuid()->id(), $mastodonUrl->value(), 'mastodon');
        }

        if ($blueskyUrl = $newPage->blueskyStatusUrl()) {
            if ($oldPage->blueskyStatusUrl() === $blueskyUrl || $blueskyUrl->isEmpty()) {
                return;
            }

            $responseCollector->registerPostUrl($newPage->uuid()->id(), $blueskyUrl->value(), 'bluesky');
        }
    },

    'page.changeStatus:after' => function ($newPage, $oldPage) {
        $webmentions = new WebmentionSender();
        $webmentions->sendWebmentions($newPage);


        if (option('mauricerenck.indieConnector.post.automatically', true) && !$newPage->isDraft() && $oldPage->isDraft()) {
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
            $mastodonSender->updateResponseCollectionUrls($postResults, $newPage);
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

    'page.render:after' => function (string $contentType, array $data, string $html, Page $page) {
        $reponseId = $page->responseId();

        if (is_null($reponseId) || $reponseId->isEmpty()) {
            return;
        }

        $isPanelPreview = $page->panelPreview();
        if (is_null($isPanelPreview) || $isPanelPreview->isEmpty() || $isPanelPreview->isFalse()) {
            $responseCollector = new ResponseCollector();
            $responseCollector->removeFromQueue($reponseId->value());
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
            $page = page($targetPage);

            $stats->trackMention(
                $page->id(),
                $webmention['source'],
                $webmention['type'],
                $webmention['author']['avatar'],
                $webmention['author']['name'],
                $webmention['author']['url'],
                $webmention['title'],
                $webmention['service'],
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
