<?php
namespace mauricerenck\IndieConnector;

use Kirby\Http\Response;

return [
    [
        'pattern' => '(:all)',
        'method' => 'GET|POST|PUT',
        'action' => function ($slug) {
            $webmentions = new WebmentionSender();
            if ($webmentions->returnAsDeletedPage($slug)) {
                return new Response('Gone', 'text/plain', 410);
            }

            $this->next();
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

            $webmentionIo = new WebmentionIo();

            if (!$webmentionIo->hasValidSecret($data)) {
                return new Response('Authentication failed', 'text/plain', 401);
            }

            $result = $webmentionIo->processIncomingWebmention($data);

            if ($result instanceof Response) {
                return $result;
            }

            $webmention = [
                'type' => $webmentionIo->getWebmentionType($data),
                'targetUrl' => $result['urls']['target'],
                'sourceUrl' => $result['urls']['source'],
                'title' => '',
                'author' => $webmentionIo->getAuthor($data),
                'content' => $webmentionIo->getContent($data),
                'published' => $webmentionIo->getPubDate($data),
            ];

            $hookData = $webmentionIo->convertToHookData($webmention, $result['urls']);

            kirby()->trigger('indieConnector.webmention.received', [
                'webmention' => $hookData,
                'targetPage' => $result['urls']['target'],
            ]);

            return new Response('Webmention received', 'text/plain', 202); // Accepted
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
];
