<?php

namespace mauricerenck\IndieConnector;

use Kirby\Http\Response;

return [
    'routes' => [
        [
            'pattern' => 'indieconnector/queue/processItem/(:any)',
            'method' => 'POST',
            'action' => function (string $id) {
                $queueHandler = new QueueHandler();
                $response = $queueHandler->getAndProcessQueuedItem($id);

                return new Response(json_encode($response), 'application/json');
            },
        ],
        [
            'pattern' => 'indieconnector/queue/process',
            'method' => 'POST',
            'action' => function () {
                $postBody = kirby()->request()->data();
                $queueHandler = new QueueHandler();

                $results = [];
                foreach ($postBody as $item) {
                    $response = $queueHandler->getAndProcessQueuedItem($item);
                    $results[] = $response;
                }

                return new Response(json_encode($results), 'application/json');
            },
        ],
        [
            'pattern' => 'indieconnector/responses/fill-queue',
            'method' => 'POST',
            'action' => function () {
                $collector = new ResponseCollector();
                $results = $collector->getDuePostUrls();

                return new Response(json_encode($results), 'application/json');
            },
        ],
        [
            'pattern' => 'indieconnector/responses/process-queue',
            'method' => 'POST',
            'action' => function () {
                $collector = new ResponseCollector();
                $responses = $collector->processResponses();

                $results = ['processed' => 0];

                if (is_null($responses)) {
                    return new Response(json_encode($results), 204);
                }

                if (count($responses) === 0) {
                    return new Response(json_encode($results), 204);
                }

                $webmentionReceiver = new WebmentionReceiver();

                $processedIds = [];
                foreach ($responses as $response) {
                    $webmentionReceiver->triggerWebmentionHook($response, $response['page_uuid']);
                    $processedIds[] = $response['id'];
                }

                $results['processed'] = count($processedIds);
                $collector->markProcessed($processedIds);

                return new Response(json_encode($results), 'application/json');
            },
        ],
        [
            'pattern' => 'indieconnector/block/url',
            'method' => 'POST',
            'action' => function () {
                $postBody = kirby()->request()->data();

                $urlHandler = new UrlHandler();
                $urlHandler->blockUrl($postBody['url'], $postBody['direction'], $postBody['hostOnly']);

                $webmentionStats = new WebmentionStats();
                $webmentionStats->updateOutboxStatus($postBody['id'], 'blocked');

                return new Response(json_encode([]), 'application/json');
            },
        ],
    ],
];
