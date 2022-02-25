<?php

namespace mauricerenck\IndieConnector;

use Kirby;
use \Response;

@require_once __DIR__ . '/lib/indieweb-comments.php';
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/indieConnector', [
    'options' => require_once(__DIR__ . '/internal/options.php'),
    'areas' => require_once(__DIR__ . '/components/areas.php'),
    'routes' => [
        [
            'pattern' => 'indieConnector/webhook/webmentionio',
            'method' => 'POST',
            'action' => function () {
                $response = json_decode(file_get_contents('php://input'));
                $receiver = new WebmentionReceiver();

                if (!$receiver->hasValidSecret($response)) {
                    return new Response('Authentication failed', 'text/plain', 401);
                }

                if (!$receiver->responseHasPostBody($response)) {
                    return new Response('Webmention body not found', 'text/plain', 406); // Not Acceptable
                }

                $targetUrl = $receiver->getTargetUrl($response);
                if (!$targetUrl) {
                    return new Response('No Target Url Given', 'text/plain', 406); // Not Acceptable
                }

                $sourceUrl = $receiver->getSourceUrl($response);
                if (!$sourceUrl) {
                    return new Response('No Source Url Given', 'text/plain', 406); // Not Acceptable
                }

                $targetPage = $receiver->getPageFromUrl($targetUrl);
                if (!$targetPage) {
                    return new Response('Target Page Not Found', 'text/plain', 404);
                }

                $webmention = [
                    'type' => $receiver->getWebmentionType($response),
                    'target' => $targetPage->id(),
                    'source' => $receiver->getTransformedSourceUrl($sourceUrl),
                    'author' => $receiver->getAuthor($response),
                    'content' => $receiver->getContent($response),
                    'published' => $receiver->getPubDate($response),
                ];

                $hookHelper = new HookHelper();
                $hookHelper->triggerHook('indieConnector.webmention.received', ['webmention' => $webmention, 'targetPage' => $targetPage]);

                if (option('mauricerenck.indieConnector.stats', false)) {
                    $stats = new WebmentionStats();
                    $stats->trackMention($webmention['target'], $webmention['source'], $webmention['type'], $webmention['author']['avatar']);
                }

                return $webmention;
            }
        ],
        [
            'pattern' => 'indieConnector/send-test-mention/(:any)',
            'action' => function ($secret) {
                if ($secret !== option('mauricerenck.indieConnector.secret', '')) {
                    return new Response('Authentication failed', 'text/plain', 401);
                }

                $webmentionSender = new WebmentionSender();
                $result = $webmentionSender->send(site()->homePage()->url(), site()->homePage()->url());

                if (!$result) {
                    return 'Could not sent webmention';
                }

                return 'Sent! You should be able to configure your webmention.io hook now.';
            }
        ],
    ]
]);
