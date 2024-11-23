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
    ],
];
