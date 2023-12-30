<?php

use mauricerenck\IndieConnector\WebmentionStats;
use PHPUnit\Framework\TestCase;

final class WebmentionStatsTest extends TestCase
{
    public function testShouldSkipHost()
    {
        $doNotTrack = ['fed.brid.gy'];
        $sendWebmention = new WebmentionStats($doNotTrack);
        $result = $sendWebmention->doNotTrackHost('https://fed.brid.gy/');
        $this->assertTrue($result);
    }

    public function testShouldNotSkipHost()
    {
        $doNotTrack = ['fed.brid.gy'];
        $sendWebmention = new WebmentionStats($doNotTrack);
        $result = $sendWebmention->doNotTrackHost('https://this-is-allowed.tld/');
        $this->assertFalse($result);
    }

    public function testDoNotTrackShouldHandleMissingSetting()
    {
        $sendWebmention = new WebmentionStats();
        $result = $sendWebmention->doNotTrackHost('https://fed.brid.by/');
        $this->assertFalse($result);
    }

    public function testDoNotTrackShouldHandleEmptyArray()
    {
        $sendWebmention = new WebmentionStats([]);
        $result = $sendWebmention->doNotTrackHost('https://fed.brid.by/');
        $this->assertFalse($result);
    }
}
