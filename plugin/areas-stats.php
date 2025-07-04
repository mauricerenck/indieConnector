<?php

namespace mauricerenck\IndieConnector;

return function () {
    return [
        'label' => 'Webmentions',
        'icon' => 'live',
        'menu' => true,
        'link' => 'webmentions',
        'views' => [
            [
                'pattern' => ['webmentions', 'webmentions/(:any)/(:any)'],
                'action' => function ($year = null, $month = null) {

                    $queueHandler = new QueueHandler();
                    $queuedItems = $queueHandler->getQueuedItems(limit: 0, includeFailed: true);
                    $itemsInQueue = $queuedItems->count();

                    if (is_null($year) || is_null($month)) {
                        $timestamp = time();
                        $year = date('Y', $timestamp);
                        $month = date('m', $timestamp);
                    }

                    if ($month < 12) {
                        $nextMonth = $month + 1;
                        $nextYear = $year;
                    } else {
                        $nextMonth = 1;
                        $nextYear = $year + 1;
                    }

                    if ($month > 1) {
                        $prevMonth = $month - 1;
                        $prevYear = $year;
                    } else {
                        $prevMonth = 12;
                        $prevYear = $year - 1;
                    }

                    $stats = new WebmentionStats();

                    if ($stats === false) {
                        return [
                            'component' => 'k-webmentions-view',
                            'title' => 'Webmentions',
                            'props' => [
                                'year' => $year,
                                'month' => $month,
                                'nextYear' => $nextYear,
                                'nextMonth' => $nextMonth,
                                'prevYear' => $prevYear,
                                'prevMonth' => $prevMonth,
                                'summary' => [],
                                'targets' => [],
                                'sources' => [],
                                'sent' => [],
                                'itemsInQueue' => $itemsInQueue ?? 0
                            ],
                        ];
                    }

                    $summary = $stats->getSummaryByMonth($year, $month);
                    $targets = $stats->getTargets($year, $month);
                    $sources = $stats->getSourceHosts($year, $month);
                    $authors = $stats->getSourceAuthors($year, $month);
                    $sent = $stats->getSentMentions($year, $month);

                    return [
                        'component' => 'k-webmentions-view',
                        'title' => 'Webmentions',
                        'props' => [
                            'year' => $year,
                            'month' => $month,
                            'nextYear' => $nextYear,
                            'nextMonth' => $nextMonth,
                            'prevYear' => $prevYear,
                            'prevMonth' => $prevMonth,
                            'summary' => $summary,
                            'targets' => $targets,
                            'sources' => $sources,
                            'authors' => $authors,
                            'sent' => $sent,
                            'itemsInQueue' => $itemsInQueue ?? 0
                        ],
                    ];
                },
            ],
            [
                'pattern' => ['webmentions/queue'],
                'action' => function () {
                    $responseCollector = new ResponseCollector();
                    $queueHandler = new QueueHandler();

                    $queuedItems = $queueHandler->getQueuedItems(limit: 0, includeFailed: true);
                    $itemsInQueue = $queuedItems->count();

                    $urlCounts = $responseCollector->getPostUrlMetrics();

                    return [
                        'component' => 'k-webmentions-queue-view',
                        'title' => 'Test',
                        'props' => [
                            'disabled' => !$queueHandler->queueEnabled(),
                            'itemsInQueue' => $itemsInQueue ?? 0,
                            'queuedItems' => $queuedItems->toArray(),
                            'responses' => [
                                'enabled' => $responseCollector->isEnabled(),
                                'limit' => option('mauricerenck.indieConnector.responses.limit', 10),
                                'urls' => [
                                    'total' => $urlCounts['total'],
                                    'due' => $urlCounts['due'],
                                    'mastodon' => $urlCounts['mastodon'],
                                    'bluesky' => $urlCounts['bluesky'],
                                ],
                            ],
                        ],
                    ];
                }
            ],
        ],
        'dialogs' => [
            'queue/delete/(:any)' => [
                'load' => function () {
                    return [
                        'component' => 'k-remove-dialog',
                        'props' => [
                            'text' => 'Do you really want to delete this item?'
                        ]
                    ];
                },
                'submit' => function (string $id) {
                    $queueHandler = new QueueHandler();
                    $result = $queueHandler->deleteQueueItem($id);

                    return $result;
                }
            ],
            'queue/clean/(:any)' => [
                'load' => function (string $status) {
                    return [
                        'component' => 'k-remove-dialog',
                        'props' => [
                            'text' => 'This will delete all entries with the status <strong>' . $status . '</strong>. Do you really want to proceed?'
                        ]
                    ];
                },
                'submit' => function (string $status) {
                    $queueHandler = new QueueHandler();
                    $result = $queueHandler->cleanQueue($status);

                    return $result;
                }
            ],
        ]
    ];
};
