<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\WebmentionSender;

use Kirby\Cms\Page;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class WebmentionSenderTest extends TestCase
{
    private $senderUtilsMock;
    private $databaseMock;
    private $urlHandlerMock;
    private $pageChecksMock;
    private $webmentionStatsMock;

    private $indieDb;
    private static $defaultOptions;
    private static string $dir;
    private static IndieConnectorDatabase $db;

    public static function setUpBeforeClass(): void
    {
        // setup temp dir for sqlite test database
        self::$dir = sys_get_temp_dir() . '/indieconnector-test-' . uniqid() . '/';
        mkdir(self::$dir, 0777, true);

        self::$defaultOptions = new OptionHandlerMock([
            'sqlitePath' => self::$dir,
        ]);

        // setup database
        self::$db = new IndieConnectorDatabase(options: self::$defaultOptions);

        self::$db->query(
            'CREATE TABLE blocked_urls (
                    url TEXT NOT NULL,
                    direction TEXT NOT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )'
        );
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$dir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$dir);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->indieDb = self::$db;
        $this->indieDb->truncate(table: 'blocked_urls');

        // TODO mention client mock
        $this->urlHandlerMock = $this->createMock(\mauricerenck\IndieConnector\UrlHandler::class);
        $this->pageChecksMock = $this->createMock(\mauricerenck\IndieConnector\PageChecks::class);
        $this->webmentionStatsMock = $this->createMock(\mauricerenck\IndieConnector\WebmentionStats::class);

        $this->senderUtilsMock = $this->getMockBuilder(WebmentionSender::class)
            ->setConstructorArgs([null, null, null, null, $this->urlHandlerMock, $this->pageChecksMock, $this->indieDb, $this->webmentionStatsMock])
            ->onlyMethods(['getUnprocessedUrls', 'filterDuplicateUrls', 'mergeUrlsWithOutbox', 'isValidTarget', 'findUrls', 'send', 'readOutbox', 'writeOutbox'])
            ->getMock();
    }

    #[Group('webmentionSender')]
    #[TestDox('sendWebmentions - should send to one url')]
    #[AllowMockObjectsWithoutExpectations]
    public function testShouldSendWebmentionXXXXXX()
    {
        $urls = ['https://text-field-url.tld'];

        $pageMock = $this->createMock(Kirby\Cms\Page::class);
        $uuidMock = $this->createMock(Kirby\Uuid\Uuid::class);
        $uuidMock->method('id')->willReturn('abc');

        $pageMock->expects($this->atLeastOnce())->method('uuid')
            ->willReturn($uuidMock);

        $this->webmentionStatsMock->expects($this->once())->method('enabled')->willReturn(false);

        $this->pageChecksMock->expects($this->once())->method('pageHasEnabledWebmentions')->willReturn(true);
        $this->pageChecksMock->expects($this->once())->method('pageFullfillsCriteria')->willReturn(true);

        $this->senderUtilsMock->expects($this->atLeastOnce())->method('readOutbox');
        $this->senderUtilsMock->expects($this->atLeastOnce())->method('findUrls');
        $this->senderUtilsMock->expects($this->once())->method('isValidTarget')->willReturn(true);
        $this->senderUtilsMock->expects($this->once())->method('send')->willReturn(true);
        $this->senderUtilsMock->expects($this->once())->method('mergeUrlsWithOutbox')->willReturn($urls);

        $this->senderUtilsMock->expects($this->once())->method('getUnprocessedUrls')->willReturn($urls);
        $this->senderUtilsMock->expects($this->once())->method('filterDuplicateUrls')->willReturn($urls);
        $this->senderUtilsMock->expects($this->once())->method('writeOutbox');

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
            'version' => 2,
            'posts' => [],
            'webmentions' => [
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

    /**
     * @group outbox
     * @testdox updateWebmentions - should only update webmention part
     */
    public function testUpdateWebmentions()
    {
        $pageMock = $this->getPageMock();

        $outboxData = [
            'version' => '2',
            'webmentions' => [
                [
                    'url' => 'https://processed-url.tld',
                    'status' => 'success',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
            ],
            'posts' => [
                [
                    'url' => 'https://mastodon.instance/@user/1234567890',
                    'status' => 'success',
                    'target' => 'mastodon',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
            ],
        ];

        $newUrls = [
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

        $expectedData = [
            'version' => '2',
            'webmentions' => [
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
            ],
            'posts' => [
                [
                    'url' => 'https://mastodon.instance/@user/1234567890',
                    'status' => 'success',
                    'target' => 'mastodon',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
            ],
        ];

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($outboxData);
        $this->senderUtilsMock->shouldReceive('writeOutbox');

        $result = $this->senderUtilsMock->updateWebmentions($newUrls, $pageMock);
        $this->assertEquals($expectedData, $result);
    }
}
