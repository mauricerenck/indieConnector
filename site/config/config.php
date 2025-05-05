<?php

return [
    'debug' => true,
    'url' => 'https://indieconnector.ddev.site',
    'api' => [
        'basicAuth' => true,
    ],
    'languages' => true,

    'mauricerenck.indieConnector.localhosts' => [],
    'mauricerenck.indieConnector.secret' => 'my-secret',
    'mauricerenck.indieConnector.stats.enabled' => true,

    'mauricerenck.indieConnector.send' => [
        'enabled' => true,
        'markDeleted' => true,
        'maxRetries' => 3,
        'skipSameHost' => true,
        'allowedTemplates' => ['phpunit', 'default'],
        'blockedTemplates' => ['blocked-template'],
        'url-fields' => ['text:text', 'textfield:text', 'layouteditor:layout', 'blockeditor:block'],
    ],

    'mauricerenck.indieConnector.receive' => [
        'enabled' => true,
        'useHtmlContent' => false,
        'blockedSources' => [],
    ],

    'mauricerenck.indieConnector.queue' => [
        'enabled' => false,
        'maxRetries' => 5,
    ],
    'mauricerenck.indieConnector.sqlitePath' => '.sqlite/',

    'mauricerenck.indieConnector.post' => [
        'textfields' => ['description'],
        'imagefield' => 'mastodonimage',
        'allowedTemplates' => ['phpunit', 'default'],
        'blockedTemplates' => ['blocked-template'],
    ],
];
