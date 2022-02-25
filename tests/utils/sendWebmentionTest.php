<?php

use mauricerenck\IndieConnector\WebmentionSender;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;

final class sendWebmentionTest extends TestCase
{
    public function testShouldSendWebmention()
    {
        $page = page('phpunit');

        $sendWebmention = new WebmentionSender();
        $result = $sendWebmention->send('https://maurice-renck.de/de/blog/internet-helden-karl-boris-und-kimble', $page->url());
        $this->assertTrue($result);
    }

    public function testShouldNotSendWebmention()
    {
        $page = page('phpunit');

        $sendWebmention = new WebmentionSender();
        $result = $sendWebmention->send('https://text-field-url.tld', $page->url());
        $this->assertFalse($result);
    }
}
