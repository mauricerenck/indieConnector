<?php

namespace mauricerenck\IndieConnector;

return [
    'icShare' => function ($page) {
        return [
            'icon' => 'share',
            'text' => 'Share',
            'theme' => 'green',
            'dialog' => 'icShare/?page=' . $page->uuid()->toString(),
        ];
    },
];
