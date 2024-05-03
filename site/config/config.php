<?php

return [
    'debug' => true,
    'url' => 'https://indie-connector.test:8890',
    'api' => [
        'basicAuth' => true,
    ],
    'languages' => true,
    'mauricerenck.indieConnector.sendWebmention' => true,
    'mauricerenck.indieConnector.debug' => true,
    'mauricerenck.indieConnector.secret' => 'my-secret',
    'mauricerenck.indieConnector.stats' => true,
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
