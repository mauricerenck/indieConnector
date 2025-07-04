<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\App as Kirby;
use Composer\Semver\Semver;

$kirbyVersion = (Semver::satisfies(Kirby::version() ?? '0.0.0', '~5') === false) ? 4 : 5;

$panelStats = [];
$panelButtons = [];
$panelDialogs = [];

if (option('mauricerenck.indieConnector.stats.enabled', false) === true) {
    $panelStats = include_once __DIR__ . '/areas-stats.php';
}

if ($kirbyVersion === 5) {
    $panelButtons = include_once __DIR__ . '/areas-buttons.php';
}

if ($kirbyVersion === 5) {
    $panelDialogs = include_once __DIR__ . '/areas-dialogs.php';
}

return [
    'webmentions' => $panelStats,
    'site' => [
        'buttons' => $panelButtons,
        'dialogs' => $panelDialogs,
    ],
];
