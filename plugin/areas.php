<?php

namespace mauricerenck\IndieConnector;

$panelStats = [];
$panelButtons = [];
$panelDialogs = [];

if (option('mauricerenck.indieConnector.stats.enabled', false) === true) {
    $panelStats = include_once __DIR__ . '/areas-stats.php';
}

$panelButtons = include_once __DIR__ . '/areas-buttons.php';
$panelDialogs = include_once __DIR__ . '/areas-dialogs.php';


return [
    'webmentions' => $panelStats,
    'site' => [
        'buttons' => $panelButtons,
        'dialogs' => $panelDialogs,
    ],
];
