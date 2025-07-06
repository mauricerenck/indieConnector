<?php

namespace mauricerenck\IndieConnector;

return [
    'icShare' => function ($page) {
        return [
            'icon' => 'share',
            'text' => 'Share',
            'theme' => 'green',
            'dialog' => 'icShare/' . $page->uuid()->toString(),
        ];
    },
    'icWebmentions' => function ($page) {
        return [
            'icon' => 'live',
            'text' => 'Webmentions',
            'theme' => 'green',
            'dialog' => 'icSendWebmentions/' . $page->uuid()->toString(),
        ];
    },
];
