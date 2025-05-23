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

                if ($responses->count() === 0) {
                    return new Response(json_encode($results), 204);
                }

                $webmentions = new WebmentionSender();
                $sourceBaseUrl = kirby()->url() . '/indieconnector/response/';

                $processedIds = [];
                foreach ($responses as $response) {

                    $targetPage = page('page://' . $response->page_uuid);
                    $sourceUrl = $sourceBaseUrl . $response->id;

                    if (is_null($targetPage)) {
                        continue;
                    }

                    $webmentions->send($targetPage->url(), $sourceUrl);
                    $processedIds[] = $response->id;
                }

                $results['processed'] = count($processedIds);
                $collector->markProcessed($processedIds);

                return new Response(json_encode($results), 'application/json');
            },
        ],
    ],
];
