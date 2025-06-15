<?php

use mauricerenck\IndieConnector\WebmentionStats;
use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\IndieConnectorDatabase;

final class WebmentionStatsTest extends TestCaseMocked
{
    private $indieDb;

    public function setUp(): void
    {
        parent::setUp();

        // $this->databaseMock = Mockery::mock(mauricerenck\IndieConnector\IndieConnectorDatabase::class);
        $this->indieDb = $this->createMock(IndieConnectorDatabase::class);
    }
    /**
     * @group webmentionStats
     * @testdox processQueue - should process queue entries
     */
    public function testTrackMention()
    {
        $statsHandler = $this->getMockBuilder(\mauricerenck\IndieConnector\WebmentionStats::class)
            ->setConstructorArgs([null, $this->indieDb])
            ->onlyMethods(['doNotTrackHost', 'webmentionIsUpdate'])
            ->getMock();

        $statsHandler->expects($this->once())
            ->method('doNotTrackHost')
            ->willReturn(false);

        $statsHandler->expects($this->once())
            ->method('webmentionIsUpdate')
            ->willReturn(false);

        $this->indieDb->expects($this->once())->method('getFormattedDate')->willReturn('0');

        $this->indieDb
            ->expects($this->once())
            ->method('insert')
            ->willReturn(true);

        $result = $statsHandler->trackMention('target', 'source', 'like-of', 'image', 'author', 'authorUrl', 'title', 'sourceService');
        $this->assertTrue($result);
    }

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
