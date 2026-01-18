<?php

namespace mauricerenck\IndieConnector;

return function () {
    return [
        'label' => 'IndieConnector',
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
                        'title' => 'Queue',
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
            [
                'pattern' => ['webmentions/status'],
                'action' => function () {
                    return [
                        'component' => 'k-webmentions-status-view',
                        'title' => 'Plugin Status',
                        'props' => [
                            'features' => [
                                [
                                    'label' => 'Receive Webmentions',
                                    'description' => 'Receive Webmentions from sites linking to your site',
                                    'enabled' => option('mauricerenck.indieConnector.receive.enabled'),
                                ],
                                [
                                    'label' => 'Send Webmentions',
                                    'description' => 'Send Webmentions to sites linked in your content',
                                    'enabled' => option('mauricerenck.indieConnector.send.enabled'),
                                ],
                                [
                                    'label' => 'Webmention Queue',
                                    'description' => 'Queue all incoming webmentions before processing them',
                                    'enabled' => option('mauricerenck.indieConnector.queue.enabled'),
                                ],
                                [
                                    'label' => 'Webmention Stats',
                                    'description' => 'View statistics about your webmentions',
                                    'enabled' => option('mauricerenck.indieConnector.stats.enabled'),
                                ],
                                [
                                    'label' => 'Mastodon',
                                    'description' => 'Post to Mastodon when publishing pages',
                                    'enabled' => option('mauricerenck.indieConnector.mastodon.enabled'),
                                ],
                                [
                                    'label' => 'Bluesky',
                                    'description' => 'Post to Bluesky when publishing pages',
                                    'enabled' => option('mauricerenck.indieConnector.bluesky.enabled'),
                                ],
                                [
                                    'label' => 'Response Collection',
                                    'description' => 'Collect responses from Bluesky and Mastodon',
                                    'enabled' => option('mauricerenck.indieConnector.responses.enabled'),
                                ],
                                [
                                    'label' => 'ActivityPub',
                                    'description' => 'Act as an ActivityPub instance',
                                    'enabled' => option('mauricerenck.indieConnector.activityPubBridge'),
                                ],
                            ],
                            'webmentionsSend' => [
                                [
                                    'label' => 'automatically',
                                    'enabled' => option('mauricerenck.indieConnector.send.automatically'),
                                    'description' => 'Automatically send Webmentions',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'allowedTemplates',
                                    'enabled' => option('mauricerenck.indieConnector.send.allowedTemplates', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.send.allowedTemplates', []),
                                    'description' => 'Only these template are allowed to send webmentions',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'blockedTemplates',
                                    'enabled' => option('mauricerenck.indieConnector.send.blockedTemplates', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.send.blockedTemplates', []),
                                    'description' => 'These templates cannot send webmentions',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'url-fields',
                                    'enabled' => option('mauricerenck.indieConnector.send.url-fields', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.send.url-fields', []),
                                    'description' => 'Set fieldnames and types to look for urls in',
                                    'docs' => '#',
                                ],
                            ],
                            'webmentionsReceive' => [
                                [
                                    'label' => 'useHtmlContent',
                                    'enabled' => option('mauricerenck.indieConnector.receive.useHtmlContent'),
                                    'description' => 'Render received html content from the sender',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'blockedSources',
                                    'enabled' => option('mauricerenck.indieConnector.receive.blockedSources', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.receive.blockedSources', []),
                                    'description' => 'These source URLs are blocked',
                                    'docs' => '#',
                                ],
                            ],
                            'posting' => [
                                [
                                    'label' => 'automatically',
                                    'enabled' => option('mauricerenck.indieConnector.post.automatically', true) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.automatically'),
                                    'description' => 'Send posts automatically when a page is published',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'prefereLanguage',
                                    'enabled' => option('mauricerenck.indieConnector.post.prefereLanguage', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.prefereLanguage'),
                                    'description' => 'Use another language than your default language to use the text from',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'usePermalinkUrl',
                                    'enabled' => option('mauricerenck.indieConnector.post.usePermalinkUrl', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.usePermalinkUrl'),
                                    'description' => 'Use the permalink url instead of the page url',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'skipUrl',
                                    'enabled' => option('mauricerenck.indieConnector.post.skipUrl', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.skipUrl'),
                                    'description' => 'NEVER add the url to the post',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'skipUrlTemplates',
                                    'enabled' => option('mauricerenck.indieConnector.post.skipUrlTemplates', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.skipUrlTemplates', []),
                                    'description' => 'Do not add the url to the post when using the given templates',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'textfields',
                                    'enabled' => option('mauricerenck.indieConnector.post.textfields', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.textfields', []),
                                    'description' => 'Text source fields for posting elsewhere',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'imagefield',
                                    'enabled' => option('mauricerenck.indieConnector.post.imagefield', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.imagefield'),
                                    'description' => 'Image source field for posting elsewhere',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'tagsfield',
                                    'enabled' => option('mauricerenck.indieConnector.post.tagsfield', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.tagsfield', []),
                                    'description' => 'A Kirby tag field to use for hashtags on mastodon and bluesky',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'allowedTemplates',
                                    'enabled' => option('mauricerenck.indieConnector.post.allowedTemplates', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.allowedTemplates', []),
                                    'description' => 'Set templates allowed to create posts',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'blockedTemplates',
                                    'enabled' => option('mauricerenck.indieConnector.post.blockedTemplates', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.post.blockedTemplates', []),
                                    'description' => 'Block templates from sending webmentions',
                                    'docs' => '#',
                                ],

                                [
                                    'label' => 'instance-url',
                                    'enabled' => option('mauricerenck.indieConnector.mastodon.instance-url', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.mastodon.instance-url'),
                                    'description' => 'Your mastodon instance url',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'text-length',
                                    'enabled' => option('mauricerenck.indieConnector.mastodon.text-length', false) === false ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.mastodon.text-length'),
                                    'description' => 'When to trim the text',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'resizeImages',
                                    'enabled' => option('mauricerenck.indieConnector.mastodon.resizeImages', 0) === 0 ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.mastodon.resizeImages', 0),
                                    'description' => 'Resize images before upload, value in pixel, 0 means disabled',
                                    'docs' => '#',
                                ],
                                [
                                    'label' => 'resizeImages',
                                    'enabled' => option('mauricerenck.indieConnector.bluesky.resizeImages', 0) === 0 ? false : true,
                                    'setting' => option('mauricerenck.indieConnector.bluesky.resizeImages', 0),
                                    'description' => 'Resize images before upload, value in pixel, 0 means disabled',
                                    'docs' => '#',
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
