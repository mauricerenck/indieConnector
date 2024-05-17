<?php

return [
    'debug' => true,
    'url' => 'http://indieconnector.ddev.site',
    'api' => [
        'basicAuth' => true,
    ],
    'languages' => true,
    'mauricerenck.indieConnector.sendWebmention' => true,
    'mauricerenck.indieConnector.debug' => true,
    'mauricerenck.indieConnector.secret' => 'my-secret',
    'mauricerenck.indieConnector.stats' => [
        'enabled' => true,
    ],
    'mauricerenck.indieConnector.receive' => [
        'useHtmlContent' => false,
    ],
    'mauricerenck.indieConnector.queue' => [
        'enabled' => true,
        'maxRetries' => 5,
    ],
    'mauricerenck.indieConnector.sqlitePath' => '.sqlite/',
    'mauricerenck.indieConnector.allowedTemplates' => ['phpunit'],
    'mauricerenck.indieConnector.blockedTemplates' => ['blocked-template'],
    'mauricerenck.indieConnector.send-mention-url-fields' => [
        'textfield:text',
        'layouteditor:layout',
        'blockeditor:block',
    ],
];
