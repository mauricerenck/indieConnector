<?php

namespace mauricerenck\IndieConnector;

return [
    'icGetMastodonUrl' => function () {
        $mastodonSender = new MastodonSender();
        return ($this->mastodonStatusUrl()->isNotEmpty()) ? $this->mastodonStatusUrl()->value() : $mastodonSender->getPostTargetUrl('mastodon', $this);
    },
];
