<?php

namespace mauricerenck\IndieConnector;

$panelButtons = include_once __DIR__ . '/areas-buttons.php';
$panelDialogs = include_once __DIR__ . '/areas-dialogs.php';

$panelStats = [];
if (PluginOptions::get('panel.view.enabled', true) === true) {
    $panelStats = include_once __DIR__ . '/areas-stats.php';
}

return [
    'webmentions' => $panelStats,
    'site' => [
        'buttons' => $panelButtons,
        // 'dialogs' => $panelDialogs,
    ],
];
