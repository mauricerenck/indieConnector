<?php

use mauricerenck\IndieConnector\WebmentionStats;
use mauricerenck\IndieConnector\TestCaseMocked;

final class WebmentionStatsTest extends TestCaseMocked
{
    private $databaseMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseMock = Mockery::mock(mauricerenck\IndieConnector\IndieConnectorDatabase::class);
    }
    /**
     * @group webmentionStats
     * @testdox processQueue - should process queue entries
     */
    public function testTrackMention()
    {
        $queueHandler = new WebmentionStats(null, $this->databaseMock);

        $type = 'like-of';
        $mentionDate = '0';
        $source = 'source';
        $target = 'target';
        $image = 'image';
        $uniqueHash = md5($target . $source . $type . $mentionDate);

        $this->databaseMock->shouldReceive('getFormattedDate')->once()->andReturn('0');
        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('insert')
            ->with(
                'webmentions',
                ['id', 'mention_type', 'mention_date', 'mention_source', 'mention_target', 'mention_image'],
                [$uniqueHash, $type, $mentionDate, $source, $target, $image]
            )
            ->once()
            ->andReturn(true);

        $result = $queueHandler->trackMention('target', 'source', 'like-of', 'image');
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
