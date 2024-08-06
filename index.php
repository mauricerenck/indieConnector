<?php

namespace mauricerenck\IndieConnector;

use Kirby;

@require_once __DIR__ . '/dependencies/indieweb-comments.php';
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/indieConnector', [
    'hooks' => require_once __DIR__ . '/plugin/hooks.php',
    'areas' => require_once __DIR__ . '/plugin/areas.php',
    'snippets' => [
        'webmention-endpoint' => __DIR__ . '/snippets/webmention-endpoint.php',
        'activitypub-wm' => __DIR__ . '/snippets/activitypub-webmention.php',
    ],
    'tags' => require_once __DIR__ . '/plugin/kirbytags.php',
    'blueprints' => [
        'indieconnector/fields/webmentions' => __DIR__ . '/blueprints/fields/block-webmentions.yml',
    ],
    'routes' => require_once __DIR__ . '/plugin/routes.php',
    'pageMethods' => require_once __DIR__ . '/plugin/page-methods.php',
]);
