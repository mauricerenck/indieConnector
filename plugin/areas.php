<?php

namespace mauricerenck\IndieConnector;

if (option('mauricerenck.indieConnector.stats.enabled', false) === false) {
    return null;
}

return [
    'webmentions' => function ($kirby) {
        return [
            'label' => 'Webmentions',
            'icon' => 'live',
            'menu' => true,
            'link' => 'webmentions',
            'views' => [
                [
                    'pattern' => ['webmentions', 'webmentions/(:any)/(:any)'],
                    'action' => function ($year = null, $month = null) {
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
                                ],
                            ];
                        }

                        $summary = $stats->getSummaryByMonth($year, $month);
                        $targets = $stats->getTargets($year, $month);
                        $sources = $stats->getSourceHosts($year, $month);
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
                                'sent' => $sent,
                            ],
                        ];
                    },
                ],
            ],
        ];
    },
];
