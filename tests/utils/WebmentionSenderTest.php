<?php

use mauricerenck\IndieConnector\TestCaseMocked;

final class WebmentionSenderTest extends TestCaseMocked
{
    private $senderUtilsMock;
    private $databaseMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->databaseMock = Mockery::mock(mauricerenck\IndieConnector\IndieConnectorDatabase::class);

        $this->senderUtilsMock = Mockery::mock(
            'mauricerenck\IndieConnector\WebmentionSender[pageFullfillsCriteria,shouldSendWebmentionToTarget,send,storeProcessedUrls,readOutbox,writeOutbox]',
            ['indieDatabase' => $this->databaseMock]
        );
    }

    /**
     * @group webmentions
     * @testdox sendWebmentions - should send to one url
     */
    public function testShouldSendWebmention()
    {
        $urls = ['https://text-field-url.tld'];

        $pageMock = $this->getPageMock(false, [
            'webmentionsStatus' => true,
            'Textfield' => 'https://indieconnector.dev',
        ]);
        $this->senderUtilsMock->shouldReceive('pageFullfillsCriteria')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('getUnprocessedUrls')->andReturn($urls);
        $this->senderUtilsMock->shouldReceive('filterDuplicateUrls')->andReturn($urls);
        $this->senderUtilsMock->shouldReceive('readOutbox');
        $this->senderUtilsMock->shouldReceive('mergeUrlsWithOutbox');
        $this->senderUtilsMock->shouldReceive('writeOutbox');
        $this->senderUtilsMock->shouldReceive('isValidTarget')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('send')->andReturn(true);

        $result = $this->senderUtilsMock->sendWebmentions($pageMock);
        $this->assertCount(1, $result);
    }

    /**
     * @group webmentions
     * @testdox sendWebmentions - should not send wm when disabled on page
     */
    public function testShouldSendWebmentionSendNone()
    {
        $urls = ['https://processed-url.tld'];

        $pageMock = $this->getPageMock(false, ['webmentionsStatus' => false]);
        $this->senderUtilsMock->shouldReceive('pageFullfillsCriteria')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('shouldSendWebmentionToTarget')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('send')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('storeProcessedUrls')->andReturn(true);

        $result = $this->senderUtilsMock->sendWebmentions($pageMock, $urls);
        $this->assertFalse($result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should convert an array of urls
     */
    public function testConvertProcessedUrlV2()
    {
        $urls = ['https://processed-url.tld', 'https://processed-url-2.tld'];

        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should convert an array of urls in old and new format
     */
    public function testConvertProcessedUrlV2Mixed()
    {
        $urls = [
            'https://processed-url.tld',
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should handle new formats only
     */
    public function testConvertProcessedUrlV2New()
    {
        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($expect);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should handle empty array
     */
    public function testConvertProcessedUrlV2Empty()
    {
        $urls = [];
        $expect = [];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox getProcessedUrls - should return 2 processed url
     */
    public function testGetProcessedUrls()
    {
        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $pageMock = $this->getPageMock();

        $outboxData = [
            'https://processed-url.tld',
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($outboxData);

        $result = $this->senderUtilsMock->getProcessedUrls($pageMock);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox urlWasAlreadyProcessed - should detect 1 processed url
     */
    public function testUrlWasAlreadyProcessed()
    {
        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 10,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->urlWasAlreadyProcessed('https://processed-url.tld', $outboxData);
        $this->assertTrue($result);
    }

    /**
     * @group sendWebmentions
     * @testdox urlWasAlreadyProcessed - should detect 1 unprocessed url
     */
    public function testUrlWasNotAlreadyProcessed()
    {
        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->urlWasAlreadyProcessed('https://un-processed-url.tld', $outboxData);
        $this->assertFalse($result);
    }

    /**
     * @group sendWebmentions
     * @testdox urlWasAlreadyProcessed - should detect 1 unprocessed url when failed 2 times
     */
    public function testUrlWasAlreadyProcessedError()
    {
        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 2,
            ],
        ];

        $result = $this->senderUtilsMock->urlWasAlreadyProcessed('https://processed-url-2.tld', $outboxData);
        $this->assertFalse($result);
    }

    /**
     * @group sendWebmentions
     * @testdox urlWasAlreadyProcessed - should detect 1 unprocessed url when max retry reached
     */
    public function testUrlWasAlreadyProcessedMaxRetryReached()
    {
        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 3,
            ],
        ];

        $result = $this->senderUtilsMock->urlWasAlreadyProcessed('https://processed-url-2.tld', $outboxData);
        $this->assertTrue($result);
    }

    /**
     * @group sendWebmentions
     * @testdox cleanupUrls - should return 1 unprocessed url
     */
    public function testCleanupUrls()
    {
        $urls = ['https://un-processed-url.tld'];
        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 3,
            ],
        ];

        $expected = ['https://un-processed-url.tld'];

        $result = $this->senderUtilsMock->cleanupUrls($urls, $outboxData);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox cleanupUrls - should skip same host url
     */
    public function testCleanupUrlsSameHost()
    {
        $pageMock = $this->getPageMock();

        $urls = [$pageMock->url(), 'https://un-processed-url.tld'];

        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 3,
            ],
        ];

        $expected = ['https://un-processed-url.tld'];

        $result = $this->senderUtilsMock->cleanupUrls($urls, $outboxData);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox filterDuplicateUrls - should return 2 urls
     */
    public function testFilterDuplicateUrls()
    {
        $urls = ['https://un-processed-url.tld', 'https://un-processed-url.tld', 'https://un-processed-url-2.tld'];

        $result = $this->senderUtilsMock->filterDuplicateUrls($urls);
        $this->assertCount(2, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox mergeUrlsWithOutbox - merge one entry
     */
    public function testMergeUrlsWithOutbox()
    {
        $pageMock = $this->getPageMock();

        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $this->senderUtilsMock->shouldReceive('readOutbox');
        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn($outboxData);

        $newEntries = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 1,
            ],
            [
                'url' => 'https://un-processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $expected = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'error',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 2,
            ],
            [
                'url' => 'https://un-processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->mergeUrlsWithOutbox($newEntries, $pageMock);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group deletePages
     * @testdox markPageAsDeleted - should create db entry
     */
    public function testMarkPageAsDeleted()
    {
        $pageMock = $this->getPageMock();

        $outboxData = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $this->senderUtilsMock->shouldReceive('markDeletedPages')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($outboxData);
        $this->senderUtilsMock->shouldReceive('sendWebmentions')->andReturn(true);

        $this->databaseMock->shouldReceive('getFormattedDate')->once()->andReturn('0');
        $this->databaseMock->shouldReceive('connect')->once()->andReturn(true);
        $this->databaseMock
            ->shouldReceive('insert')
            ->with('deleted_pages', ['id', 'slug', 'deletedAt'], [$pageMock->uuid()->id(), $pageMock->uri(), 0])
            ->once()
            ->andReturn(true);

        $result = $this->senderUtilsMock->markPageAsDeleted($pageMock);
        $this->assertTrue($result);
    }
}
