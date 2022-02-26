<?php

use mauricerenck\IndieConnector\MastodonSender;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;

final class sendMastodonTest extends TestCase
{
    public function testShouldFailWithoutSettings()
    {
        $page = page('phpunit');
        $sendMastodon = new MastodonSender();
        $result = $sendMastodon->sendToot($page);
        $this->assertFalse($result);
    }
}
