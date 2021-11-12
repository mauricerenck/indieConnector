<?php

namespace mauricerenck\IndieConnector;

use Kirby;

Kirby::plugin('mauricerenck/hooklogger', [
    'hooks' => [
        'indieConnector.webhook.received' => function ($webmention, $targetPage) {
            if (option('mauricerenck.indieConnector.debug', false) === true) {
                $time = time();
                file_put_contents('webmentionhook.' . $time . '.json', json_encode($webmention));
            }
        }
    ]
]);
