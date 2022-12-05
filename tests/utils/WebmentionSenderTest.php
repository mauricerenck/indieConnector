<?php

use mauricerenck\IndieConnector\WebmentionSender;
use PHPUnit\Framework\TestCase;

final class WebmentionSenderTest extends TestCase
{
    public function testShouldSendWebmention()
    {
        $sendWebmention = new WebmentionSender();
        $result = $sendWebmention->send('https://maurice-renck.de/de/blog/2003/internet-helden-karl-boris-und-kimble', site()->url()); // FIXME
        $this->assertTrue($result);
    }

    public function testShouldNotSendWebmention()
    {
        $sendWebmention = new WebmentionSender();
        $result = $sendWebmention->send('https://text-field-url.tld', site()->url());
        $this->assertFalse($result);
    }
}
