<?php

namespace mauricerenck\IndieConnector;

return [
    'webmentions' => function ($kirby) {
        return [
            'label' => 'Webmentions',
            'icon' => 'live',
            'menu' => true,
            'link' => 'webmentions',
            'views' => [
                [
                    'pattern' => 'webmentions',
                    'action' => function () {
                        return [
                            'component' => 'k-webmentions-view',
                            'title' => 'Webmentions',
                            'props' => [
                                'summary' => function () {
                                    $stats = new WebmentionStats();
                                    $timestamp = time();
                                    $summary = $stats->getSummaryByMonth($timestamp);

                                    return $summary;
                                },
                                'targets' => function () {
                                    $stats = new WebmentionStats();
                                    $timestamp = time();
                                    $summary = $stats->getTargets($timestamp);

                                    return $summary;
                                },
                                'sources' => function () {
                                    $stats = new WebmentionStats();
                                    $timestamp = time();
                                    $summary = $stats->getSources($timestamp);

                                    return $summary;
                                },
                                'version' => function () {
                                    $stats = new WebmentionStats();
                                    return $stats->getPluginVersion();
                                }
                            ],
                        ];
                    }
                ]
            ]
        ];
    }
];
