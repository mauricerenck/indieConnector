<?php

namespace mauricerenck\IndieConnector;

use Kirby;
use Kirby\Http\Response;

@require_once __DIR__ . '/lib/indieweb-comments.php';
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/indieConnector', [
    'options' => require_once __DIR__ . '/internal/options.php',
    'hooks' => require_once __DIR__ . '/internal/hooks.php',
    'areas' => require_once __DIR__ . '/components/areas.php',
    'snippets' => [
        'activitypub-wm' => __DIR__ . '/snippets/activitypub-webmention.php',
    ],
    'blueprints' => [
        'indieconnector/fields/webmentions' => __DIR__ . '/blueprints/fields/block-webmentions.yml',
    ],
    'routes' => [
        [
            'pattern' => '(indieConnector|indieconnector)/webmention',
            'method' => 'GET',
            'action' => function () {
                // TODO show a form as a fallback
            },
        ],
        [
            'pattern' => '(indieConnector|indieconnector)/webmention',
            'method' => 'POST',
            'action' => function () {
                $request = kirby()->request();
                $data = $request->data();

                if (!isset($data) || empty($data)) {
                    return new Response('No POST data found', 'text/plain', 400); // Not Acceptable
                }

                $webmentionReceiver = new WebmentionReceiver();
                $result = $webmentionReceiver->processIncomingWebmention($data);

                if ($result instanceof Response) {
                    return $result;
                }

                kirby()->trigger('indieConnector.webmention.queue', [
                    'targetUrl' => $result['urls']['target'],
                    'sourceUrl' => $result['urls']['source'],
                ]);

                return new Response('Webmention received', 'text/plain', 202); // Accepted
            },
        ],
        [
            'pattern' => '(indieConnector|indieconnector)/queue',
            'method' => 'POST',
            'action' => function () {
                $request = kirby()->request();
                $data = $request->data();

                if (!isset($data) || empty($data)) {
                    return new Response('No POST data found', 'text/plain', 400); // Not Acceptable
                }

                $receiver = new Receiver();
                if (!$receiver->hasValidSecret($data)) {
                    return new Response('Authentication failed', 'text/plain', 401);
                }

                $limit = $data['limit'] ?? 10;

                kirby()->trigger('indieConnector.webmention.processQueue', [
                    'limit' => $limit,
                ]);

                return new Response('Queue processed', 'text/plain', 202); // Accepted
            },
        ],
        [
            'pattern' => '(indieConnector|indieconnector)/webhook/webmentionio',
            'method' => 'POST',
            'action' => function () {
                $request = kirby()->request();
                $data = $request->data();

                if (!isset($data) || empty($data)) {
                    return new Response('No POST data found', 'text/plain', 400); // Not Acceptable
                }

                $webmentionReceiver = new WebmentionIo();

                if (!$webmentionReceiver->hasValidSecret($data)) {
                    return new Response('Authentication failed', 'text/plain', 401);
                }

                $result = $webmentionReceiver->processIncomingWebmention($data);

                if ($result instanceof Response) {
                    return $result;
                }

                $webmention = [
                    'type' => $webmentionReceiver->getWebmentionType($data),
                    'targetUrl' => $result['urls']['target'],
                    'sourceUrl' => $result['urls']['source'],
                    'title' => null,
                    'author' => $webmentionReceiver->getAuthor($data),
                    'content' => $webmentionReceiver->getContent($data),
                    'published' => $webmentionReceiver->getPubDate($data),
                ];

                $hookData = $webmentionReceiver->convertToHookData($webmention, $result['urls']);

                kirby()->trigger('indieConnector.webmention.received', [
                    'webmention' => $hookData,
                    'targetPage' => $result['urls']['target'],
                ]);

                return new Response('Webmention received', 'text/plain', 202); // Accepted
            },
        ],
        [
            'pattern' => 'indieconnector/send-test-mention/(:any)',
            'action' => function ($secret) {
                if ($secret !== option('mauricerenck.indieConnector.secret', '')) {
                    return new Response('Authentication failed', 'text/plain', 401);
                }

                // FIXME send a post request to new indieconnector domain to trigger webmention
                $webmentionSender = new WebmentionSender();
                $result = $webmentionSender->send(site()->homePage()->url(), site()->homePage()->url());

                if (!$result) {
                    return 'Could not sent webmention';
                }

                return 'Sent! You should be able to configure your webmention.io hook now.';
            },
        ],
        [
            'pattern' => '^.well-known/((host-meta|webfinger).(:any)|(host-meta|webfinger))',
            'method' => 'OPTIONS|GET|POST|PUT',
            'action' => function ($file) {
                if (!option('mauricerenck.indieConnector.activityPubBridge', false)) {
                    return false;
                }

                $query = kirby()->request()->query()->toArray();

                $queryString = [];
                foreach ($query as $key => $value) {
                    $queryString[] = $key . '=' . $value;
                }

                $redirectUrl = 'https://fed.brid.gy/.well-known/' . $file . '?' . implode('&', $queryString);

                die(header('Location: ' . $redirectUrl));
            },
        ],
    ],
]);
