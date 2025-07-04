<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\App as Kirby;

@require_once __DIR__ . '/dependencies/indieweb-comments.php';
@include_once __DIR__ . '/vendor/autoload.php';


Kirby::plugin('mauricerenck/indieConnector', [
    'api' => require_once __DIR__ . '/plugin/api.php',
    'hooks' => require_once __DIR__ . '/plugin/hooks.php',
    'areas' => require_once __DIR__ . '/plugin/areas.php',
    'fields' => require_once __DIR__ . '/plugin/fields.php',
    'snippets' => [
        'webmention-endpoint' => __DIR__ . '/snippets/webmention-endpoint.php',
        'activitypub-wm' => __DIR__ . '/snippets/activitypub-webmention.php',
    ],
    'templates' => [
        'indie-post-response' => __DIR__ . '/templates/pages/response.php',
    ],
    'tags' => require_once __DIR__ . '/plugin/kirbytags.php',
    'blueprints' => [
        'indieconnector/fields/webmentions' => __DIR__ . '/blueprints/fields/block-webmentions.yml',
    ],
    'routes' => require_once __DIR__ . '/plugin/routes.php',
    'pageMethods' => require_once __DIR__ . '/plugin/page-methods.php',
]);
