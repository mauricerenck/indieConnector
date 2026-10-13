<?php

use mauricerenck\IndieConnector\ResponseCollector;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\OptionHandlerMock;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class ResponseCollectorTest extends TestCase
{
    private $indieDb;
    private $collector;
    private $mastodonMock;
    private $blueskyMock;
    private $urlHandlerMock;

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
            'CREATE TABLE external_post_urls (
                                id VARCHAR UNIQUE,
                                page_uuid VARCHAR NOT NULL,
                                post_url VARCHAR NOT NULL,
                                post_type VARCHAR NOT NULL,
                                active BOOLEAN NOT NULL DEFAULT TRUE,
                                last_fetched TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                            )'
        );

        self::$db->query(
            'CREATE TABLE known_responses (
            id            VARCHAR,
            post_selector VARCHAR UNIQUE,
            post_url      VARCHAR NOT NULL,
            post_type     VARCHAR NOT NULL
        )'
        );

        self::$db->query(
            'CREATE TABLE queue_responses (
                id VARCHAR UNIQUE,
                response_id VARCHAR NOT NULL,
                response_type VARCHAR NOT NULL,
                response_source VARCHAR NOT NULL,
                response_date VARCHAR NOT NULL,
                response_text TEXT NOT NULL,
                response_url VARCHAR NOT NULL,

                author_id VARCHAR NOT NULL,
                author_name VARCHAR NOT NULL,
                author_username VARCHAR NOT NULL,
                author_avatar VARCHAR NOT NULL,
                author_url VARCHAR NOT NULL,

                queueStatus VARCHAR NOT NULL,
                processLog TEXT,
                retries NUMBER
            , "page_uuid" varchar NOT NULL)'
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
        $this->indieDb->truncate(table: 'external_post_urls');
        $this->indieDb->truncate(table: 'known_responses');
        $this->indieDb->truncate(table: 'queue_responses');

        $this->mastodonMock = $this->createMock(\mauricerenck\IndieConnector\Mastodon::class);
        $this->blueskyMock = $this->createMock(\mauricerenck\IndieConnector\Bluesky::class);
        $this->urlHandlerMock = $this->createMock(\mauricerenck\IndieConnector\UrlHandler::class);

        $this->collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['isEnabled', 'getPageByUuid', 'getSourceBaseUrl'])
            ->getMock();
    }


    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Group('responseCollector')]
    #[TestDox('registerPostUrl - does nothing when disabled')]
    #[AllowMockObjectsWithoutExpectations]
    public function testRegisterPostUrlDoesNothingWhenDisabled()
    {
        // overwriting db to mock it
        $indieDb = $this->createMock(IndieConnectorDatabase::class);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['isEnabled'])
            ->getMock();

        $collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $indieDb->expects($this->never())->method('select');
        $collector->registerPostUrl('uuid', 'url', 'type');
    }

    #[Group('responseCollector')]
    #[TestDox('registerPostUrl - inserts when no existing')]
    #[AllowMockObjectsWithoutExpectations]
    public function testRegisterPostUrlInsertsWhenNoExisting()
    {
        // overwriting db to mock it
        $indieDb = $this->createMock(IndieConnectorDatabase::class);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['isEnabled'])
            ->getMock();

        $collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $indieDb->expects($this->once())
            ->method('select')
            ->willReturn(new class {
                public function count()
                {
                    return 0;
                }
            });

        $indieDb->expects($this->once())
            ->method('insert')
            ->with(
                'external_post_urls',
                $this->callback(function (array $values) {
                    // we're doing this, so we don't have to worry about the order of the keys or their values
                    return array_keys($values) === [
                        'id',
                        'page_uuid',
                        'post_url',
                        'post_type',
                        'last_fetched',
                    ];
                })
            );

        $collector->registerPostUrl('uuid', 'url', 'type');
    }

    #[Group('responseCollector')]
    #[TestDox('registerPostUrl - updates when existing')]
    #[AllowMockObjectsWithoutExpectations]
    public function testRegisterPostUrlUpdatesWhenExisting()
    {
        // overwriting db to mock it
        $indieDb = $this->createMock(IndieConnectorDatabase::class);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['isEnabled'])
            ->getMock();

        $collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $existing = new class {
            public $id = 'existing-id';
        };

        $indieDb->expects($this->once())
            ->method('select')
            ->willReturn(new class($existing) {
                private $existing;
                public function __construct($existing)
                {
                    $this->existing = $existing;
                }
                public function count()
                {
                    return 1;
                }
                public function toArray()
                {
                    return [$this->existing];
                }
            });

        $indieDb->expects($this->once())
            ->method('update')
            ->with(
                'external_post_urls',
                ['post_url' => 'url', 'last_fetched' => '1'],
                ['id' => 'existing-id', 'page_uuid' => 'uuid', 'post_type' => 'type']
            );

        $collector->registerPostUrl('uuid', 'url', 'type');
    }

    #[Group('responseCollector')]
    #[TestDox('getDuePostUrlsDbResult - returns due post urls')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetDuePostUrlsDbResult()
    {
        $now = time();
        $old = $now - 61 * 60;      // älter als ttl -> fällig
        $recent = $now - 10 * 60;   // jünger als ttl -> nicht fällig

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-1',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/1',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-2',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/2',
                'post_type' => 'bluesky',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // not due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-3',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/3',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $recent,
            ]
        );

        // not active
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-4',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/4',
                'post_type' => 'bluesky',
                'active' => 0,
                'last_fetched' => $old,
            ]
        );

        $rows = $this->collector->getDuePostUrlsDbResult();
        $this->assertCount(2, $rows);

        $types = $rows->pluck('post_type');
        sort($types);
        $this->assertSame(['bluesky', 'mastodon'], $types);

        $urls = $rows->findBy('post_type', 'bluesky')->post_urls;
        $this->assertSame('https://b.example/2', $urls);

        $urls = $rows->findBy('post_type', 'mastodon')->post_urls;
        $this->assertSame('https://a.example/1', $urls);
    }

    #[Group('responseCollector')]
    #[TestDox('getPostUrlMetricsDbResult - returns due post url metrics')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPostUrlMetricsDbResult()
    {
        $now = time();
        $old = $now - 61 * 60;      // older than ttl -> due
        $recent = $now - 10 * 60;   // younger than ttl -> not due

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-1',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/1',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-2',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/2',
                'post_type' => 'bluesky',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // not due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-3',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/3',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $recent,
            ]
        );

        // not active
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-4',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/4',
                'post_type' => 'bluesky',
                'active' => 0,
                'last_fetched' => $old,
            ]
        );

        $rows = $this->collector->getPostUrlMetricsDbResult();
        $this->assertCount(2, $rows);

        $types = $rows->pluck('post_type');
        sort($types);
        $this->assertSame(['bluesky', 'mastodon'], $types);

        $urls = $rows->findBy('post_type', 'bluesky')->urls;
        $this->assertSame("1", $urls);

        $urls = $rows->findBy('post_type', 'mastodon')->urls;
        $this->assertSame("2", $urls);
    }

    #[Group('responseCollector')]
    #[TestDox('getPostUrlMetricsDueUrlDbResult - returns due post url metrics')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPostUrlMetricsDueUrlDbResult()
    {
        $now = time();
        $old = $now - 61 * 60;      // older than ttl -> due
        $recent = $now - 10 * 60;   // younger than ttl -> not due

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-1',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/1',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-2',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/2',
                'post_type' => 'bluesky',
                'active' => 1,
                'last_fetched' => $old,
            ]
        );

        // not due
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-3',
                'page_uuid' => 'uuid',
                'post_url' => 'https://a.example/3',
                'post_type' => 'mastodon',
                'active' => 1,
                'last_fetched' => $recent,
            ]
        );

        // not active
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'existing-id-4',
                'page_uuid' => 'uuid',
                'post_url' => 'https://b.example/4',
                'post_type' => 'bluesky',
                'active' => 0,
                'last_fetched' => $old,
            ]
        );

        $rows = $this->collector->getPostUrlMetricsDueUrlDbResult();
        $this->assertSame("2", $rows->first()->urls());
    }

    #[Group('responseCollector')]
    #[TestDox('getLastResponsesFromDb - returns due post url metrics')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetLastResponsesFromDb()
    {
        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-1',
                'post_selector' => 'example.com/@user/1_like-of',
                'post_url' => 'https://example.com/@user/1',
                'post_type' => 'like-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-2',
                'post_selector' => 'example.com/@user/2_repost-of',
                'post_url' => 'https://example.com/@user/1',
                'post_type' => 'repost-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-3',
                'post_selector' => 'example.com/@user/2_repost-of',
                'post_url' => 'https://example.com/@user/2',
                'post_type' => 'repost-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-4',
                'post_selector' => 'example.com/@user/3_like-of',
                'post_url' => 'https://example.com/@user/3',
                'post_type' => 'like-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-5',
                'post_selector' => 'example.com/@user/4_like-of',
                'post_url' => 'https://example.com/@user/4',
                'post_type' => 'like-of',
            ]
        );

        $rows = $this->collector->getLastResponsesFromDb(['https://example.com/@user/1', 'https://example.com/@user/3']);

        $ids = $rows->findBy('post_type', 'like-of')->ids();
        $this->assertSame("existing-id-1,existing-id-4", $ids);

        $ids = $rows->findBy('post_type', 'repost-of')->ids();
        $this->assertSame("existing-id-2", $ids);
    }

    #[Group('responseCollector')]
    #[TestDox('getKnownIds - returns known ids')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetKnownIds()
    {
        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-1',
                'post_selector' => 'example.com/@user/1_like-of',
                'post_url' => 'https://example.com/@user/1',
                'post_type' => 'like-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-4',
                'post_selector' => 'example.com/@user/3_like-of',
                'post_url' => 'https://example.com/@user/3',
                'post_type' => 'like-of',
            ]
        );


        $lastResponses = $this->collector->getLastResponsesFromDb(['https://example.com/@user/1', 'https://example.com/@user/3']);
        $ids = $this->collector->getKnownIds($lastResponses, 'like-of');

        $this->assertSame(['existing-id-1', 'existing-id-4'], $ids);
    }

    #[Group('responseCollector')]
    #[TestDox('getKnownIds - returns no ids')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetKnownIdsReturnsNoIds()
    {
        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-1',
                'post_selector' => 'example.com/@user/1_like-of',
                'post_url' => 'https://example.com/@user/1',
                'post_type' => 'like-of',
            ]
        );

        $this->indieDb->insert(
            table: 'known_responses',
            values: [
                'id' => 'existing-id-4',
                'post_selector' => 'example.com/@user/3_like-of',
                'post_url' => 'https://example.com/@user/3',
                'post_type' => 'like-of',
            ]
        );


        $lastResponses = $this->collector->getLastResponsesFromDb(['https://example.com/@user/6', 'https://example.com/@user/7']);
        $ids = $this->collector->getKnownIds($lastResponses, 'like-of');

        $this->assertSame([], $ids);
    }

    #[Group('responseCollector')]
    #[TestDox('addToQueue - should insert one row')]
    #[AllowMockObjectsWithoutExpectations]
    public function testAddToQueue()
    {
        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'id-1',
                'page_uuid' => 'example.com/@user/1_like-of',
                'post_url' => 'https://example.com/@user/1',
                'post_type' => 'mastodon',
                'active' => '1',
                'last_fetched' => '1',
            ]
        );

        $this->indieDb->insert(
            table: 'external_post_urls',
            values: [
                'id' => 'id-2',
                'page_uuid' => 'example.com/@user/2_like-of',
                'post_url' => 'https://example.com/@user/2',
                'post_type' => 'bluesky',
                'active' => '1',
                'last_fetched' => '1',
            ]
        );


        $this->collector->addToQueue(
            postUrl: 'https://example.com/@user/1',
            responseId: 'response-id-1',
            responseType: 'like-of',
            responseSource: 'mastodon',
            responseDate: '2026-01-02',
            authorId: 'author-id-1',
            authorName: 'author name',
            authorUsername: 'authorname',
            authorAvatar: 'avatar',
            authorUrl: 'https://example.com',
        );

        $expected = [
            'response_id' => 'response-id-1',
            'response_type' => 'like-of',
            'response_source' => 'mastodon',
            'response_text' => '',
            'response_url' => '',
            'retries' => '0',
        ];

        $results = $this->indieDb->select('queue_responses', ['response_id', 'response_type', 'response_source', 'response_text', 'response_url', 'retries'], '');
        $result = $results->first()->toArray();

        $this->assertSame($results->count(), 1);
        $this->assertSame($expected, $result);
    }

    #[Group('responseCollector')]
    #[TestDox('processResponses - should return 2 results')]
    #[AllowMockObjectsWithoutExpectations]
    public function testProcessResponses()
    {
        $mockPage = new class {
            public function url()
            {
                return 'https://example.com/my-post';
            }
        };

        $this->indieDb->insert(
            table: 'queue_responses',
            values: [
                'id' => 'id-1',
                'page_uuid' => 'page-uuid-id',
                'response_id' => 'response-id-1',
                'response_type' => 'like-of',
                'response_source' => 'mastodon',
                'response_date' => '2026-01-02',
                'response_text' => 'text',
                'response_url' => 'https://example.com',
                'author_id' => 'author-id-1',
                'author_name' => 'author name',
                'author_username' => 'authorname',
                'author_avatar' => 'avatar',
                'author_url' => 'https://example.com',
                'queueStatus' => 'pending',
                'retries' => 0
            ]
        );

        $this->indieDb->insert(
            table: 'queue_responses',
            values: [
                'id' => 'id-2',
                'page_uuid' => 'page-uuid-id',
                'response_id' => 'response-id-2',
                'response_type' => 'like-of',
                'response_source' => 'bluesky',
                'response_date' => '2026-01-02',
                'response_text' => 'text',
                'response_url' => 'https://example.com',
                'author_id' => 'author-id-2',
                'author_name' => 'author name',
                'author_username' => 'authorname',
                'author_avatar' => 'avatar',
                'author_url' => 'https://example.com',
                'queueStatus' => 'pending',
                'retries' => 0
            ]
        );

        $this->indieDb->insert(
            table: 'queue_responses',
            values: [
                'id' => 'id-3',
                'page_uuid' => 'page-uuid-id',
                'response_id' => 'response-id-3',
                'response_type' => 'like-of',
                'response_source' => 'mastodon',
                'response_date' => '2026-01-02',
                'response_text' => 'text',
                'response_url' => 'https://example.com',
                'author_id' => 'author-id-3',
                'author_name' => 'author name',
                'author_username' => 'authorname',
                'author_avatar' => 'avatar',
                'author_url' => 'https://example.com',
                'queueStatus' => 'published',
                'retries' => 0
            ]
        );

        $this->collector->expects($this->exactly(1))
            ->method('getSourceBaseUrl')
            ->willReturn('https://example.com/indieconnector/response/');

        $this->collector->expects($this->atLeast(2))
            ->method('getPageByUuid')
            ->willReturn($mockPage);

        $results = $this->collector->processResponses();

        $this->assertCount(2, $results);
    }

    #[Group('responseCollector')]
    #[TestDox('convertToWebmentionHookData - skips responses with no matching page')]
    #[AllowMockObjectsWithoutExpectations]
    public function testConvertToWebmentionHookDataSkipsResponsesWithNoPage()
    {
        $this->collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $this->collector->method('getPageByUuid')->willReturn(null);

        $response = (object)[
            'id' => 'resp1',
            'page_uuid' => 'uuid1',
            'response_type' => 'like-of',
            'response_date' => '2025-01-01',
            'response_text' => '',
            'response_source' => 'mastodon',
            'author_name' => 'Alice',
            'author_avatar' => 'avatar.png',
            'author_url' => 'https://mastodon.social/@alice',
        ];

        $result = $this->collector->convertToWebmentionHookData([$response]);

        $this->assertEquals([], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('convertToWebmentionHookData - returns correct data structure')]
    #[AllowMockObjectsWithoutExpectations]
    public function testConvertToWebmentionHookDataReturnsCorrectStructure()
    {
        $mockPage = new class {
            public function url()
            {
                return 'https://example.com/my-post';
            }
        };

        $this->collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $this->collector->method('getPageByUuid')->willReturn($mockPage);

        $response = (object)[
            'id' => 'resp1',
            'page_uuid' => 'uuid1',
            'response_type' => 'like-of',
            'response_date' => '2025-01-01',
            'response_text' => 'Great post!',
            'response_source' => 'mastodon',
            'author_name' => 'Alice',
            'author_avatar' => 'avatar.png',
            'author_url' => 'https://mastodon.social/@alice',
        ];

        $result = $this->collector->convertToWebmentionHookData([$response]);

        $this->assertEquals([
            [
                'id' => 'resp1',
                'page_uuid' => 'page://uuid1',
                'type' => 'like-of',
                'target' => 'https://example.com/my-post',
                'source' => 'https://example.com/indieconnector/response/resp1',
                'published' => '2025-01-01',
                'title' => 'like-of',
                'content' => 'Great post!',
                'service' => 'mastodon',
                'author' => [
                    'type' => 'card',
                    'name' => 'Alice',
                    'avatar' => 'avatar.png',
                    'url' => 'https://mastodon.social/@alice',
                ],
            ]
        ], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('convertToWebmentionHookData - handles multiple responses')]
    #[AllowMockObjectsWithoutExpectations]
    public function testConvertToWebmentionHookDataHandlesMultipleResponses()
    {
        $mockPage = new class {
            public function url()
            {
                return 'https://example.com/my-post';
            }
        };

        $this->collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $this->collector->expects($this->exactly(3))
            ->method('getPageByUuid')
            ->willReturnOnConsecutiveCalls($mockPage, null, $mockPage);

        $responses = [
            (object)['id' => 'resp1', 'page_uuid' => 'uuid1', 'response_type' => 'like-of', 'response_date' => '2025-01-01', 'response_text' => '', 'response_source' => 'mastodon', 'author_name' => 'Alice', 'author_avatar' => 'avatar.png', 'author_url' => 'https://mastodon.social/@alice'],
            (object)['id' => 'resp2', 'page_uuid' => 'uuid2', 'response_type' => 'repost-of', 'response_date' => '2025-01-02', 'response_text' => '', 'response_source' => 'mastodon', 'author_name' => 'Bob', 'author_avatar' => 'avatar2.png', 'author_url' => 'https://mastodon.social/@bob'],
            (object)['id' => 'resp3', 'page_uuid' => 'uuid3', 'response_type' => 'in-reply-to', 'response_date' => '2025-01-03', 'response_text' => 'Nice!', 'response_source' => 'bluesky', 'author_name' => 'Carol', 'author_avatar' => 'avatar3.png', 'author_url' => 'https://bsky.app/carol'],
        ];

        $result = $this->collector->convertToWebmentionHookData($responses);

        $this->assertCount(2, $result);
        $this->assertEquals('resp1', $result[0]['id']);
        $this->assertEquals('resp3', $result[1]['id']);
    }

    #[Group('responseCollector')]
    #[TestDox('updateKnownReponses - handles multiple responses')]
    #[AllowMockObjectsWithoutExpectations]
    public function testUpdateKnownReponses()
    {
        $this->collector->updateKnownReponses(postUrl: 'https://example.com/my-post', latestId: 'id-1', verb: 'like-of');
        $results = $this->indieDb->select('known_responses', ['*'], '');
        $results = $results->toArray();

        $expected = ['id' => 'id-1', 'post_selector' => 'example.com/my-post_like-of', 'post_url' => 'https://example.com/my-post', 'post_type' => 'like-of'];
        $this->assertCount(1, $results);
        $this->assertEquals($expected, $results[0]->toArray());

        $this->collector->updateKnownReponses(postUrl: 'https://example.com/my-post', latestId: 'id-2', verb: 'like-of');
        $results = $this->indieDb->select('known_responses', ['*'], '');
        $results = $results->toArray();

        $expected = ['id' => 'id-2', 'post_selector' => 'example.com/my-post_like-of', 'post_url' => 'https://example.com/my-post', 'post_type' => 'like-of'];
        $this->assertCount(1, $results);
        $this->assertEquals($expected, $results[0]->toArray());

        $this->collector->updateKnownReponses(postUrl: 'https://example.com/my-post', latestId: 'id-3', verb: 'mention-of');
        $results = $this->indieDb->select('known_responses', ['*'], '');
        $results = $results->toArray();

        $expected1 = ['id' => 'id-2', 'post_selector' => 'example.com/my-post_like-of', 'post_url' => 'https://example.com/my-post', 'post_type' => 'like-of'];
        $expected2 = ['id' => 'id-3', 'post_selector' => 'example.com/my-post_mention-of', 'post_url' => 'https://example.com/my-post', 'post_type' => 'mention-of'];
        $this->assertCount(2, $results);
        $this->assertEquals($expected1, $results[0]->toArray());
        $this->assertEquals($expected2, $results[1]->toArray());
    }

    #[Group('responseCollector')]
    #[TestDox('cleanPostUrls - returns valid and invalid urls')]
    #[AllowMockObjectsWithoutExpectations]
    public function testCleanPostUrls()
    {
        $postUrls = [
            'https://example.com/post/1',
            'https://example.com/post/2',
            'https://example.com/post/3',
        ];

        $plattformClass = new class {
            public function postExists(string $url)
            {
                return $url === 'https://example.com/post/2';
            }
        };

        $results = $this->collector->cleanPostUrls(postUrls: $postUrls, plattformClass: $plattformClass);

        $this->assertCount(1, $results['valid']);
        $this->assertCount(2, $results['invalid']);
        $this->assertEquals('https://example.com/post/1', $results['invalid'][0]);
    }

    #[Group('responseCollector')]
    #[TestDox('getDuePostUrls - calls parser with correct urls')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetDuePostUrlsCallsParsersWithCorrectUrls()
    {
        $mockResult = new class {
            public function count()
            {
                return 2;
            }
            public function filterBy($field, $value)
            {
                if ($value === 'mastodon') {
                    return new class {
                        public function first()
                        {
                            return (object)['post_urls' => 'mastodon_url1,mastodon_url2'];
                        }
                    };
                }
                if ($value === 'bluesky') {
                    return new class {
                        public function first()
                        {
                            return (object)['post_urls' => 'bluesky_url1,bluesky_url2'];
                        }
                    };
                }
                return new class {
                    public function first()
                    {
                        return null;
                    }
                };
            }
        };

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, null, null, null, $this->indieDb])
            ->onlyMethods(['getDuePostUrlsDbResult', 'parseResponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getDuePostUrlsDbResult')
            ->willReturn($mockResult);

        $collector->expects($this->exactly(2))
            ->method('parseResponses');

        $collector->getDuePostUrls();
    }

    #[Group('responseCollector')]
    #[TestDox('getDuePostUrls - returns zero when no results')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetDuePostUrlsReturnsZeroWhenNoResults()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getDuePostUrlsDbResult', 'parseResponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getDuePostUrlsDbResult')
            ->willReturn(null);

        $result = $collector->getDuePostUrls();

        $this->assertEquals(['urls' => 0, 'responses' => 0], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('getDuePostUrls - aggregates responses correctly')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetDuePostUrlsAggregatesResponses()
    {
        $mockResult = new class {
            public function count()
            {
                return 2;
            }
            public function filterBy($field, $value)
            {
                if ($value === 'mastodon') {
                    return new class {
                        public function first()
                        {
                            return (object)['post_urls' => 'url1,url2', 'post_type' => 'mastodon'];
                        }
                    };
                }
                if ($value === 'bluesky') {
                    return new class {
                        public function first()
                        {
                            return (object)['post_urls' => 'url3', 'post_type' => 'bluesky'];
                        }
                    };
                }
                return new class {
                    public function first()
                    {
                        return null;
                    }
                };
            }
        };

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getDuePostUrlsDbResult', 'parseResponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getDuePostUrlsDbResult')
            ->willReturn($mockResult);

        $collector->method('parseResponses')->willReturn(['urls' => 2, 'responses' => 5]);

        $result = $collector->getDuePostUrls();

        $this->assertEquals(['urls' => 4, 'responses' => 10], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('getPostUrlMetrics - returns zero when no results')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPostUrlMetricsReturnsZeroWhenNoResults()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getPostUrlMetricsDbResult'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getPostUrlMetricsDbResult')
            ->willReturn(null);

        $result = $collector->getPostUrlMetrics();

        $this->assertEquals(['total' => 0, 'mastodon' => 0, 'bluesky' => 0, 'due' => 0], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('getPostUrlMetrics - returns correct metrics')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPostUrlMetricsReturnsCorrectMetrics()
    {
        $mockResult = new class {
            public function count()
            {
                return 2;
            }
            public function filterBy($field, $value)
            {
                if ($value === 'mastodon') {
                    return new class {
                        public function first()
                        {
                            return (object)['urls' => 3];
                        }
                    };
                }
                if ($value === 'bluesky') {
                    return new class {
                        public function first()
                        {
                            return (object)['urls' => 2];
                        }
                    };
                }
                return new class {
                    public function first()
                    {
                        return null;
                    }
                };
            }
        };

        $mockDueResult = new class {
            public function first()
            {
                return (object)['urls' => 4];
            }
        };

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getPostUrlMetricsDbResult', 'getPostUrlMetricsDueUrlDbResult'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getPostUrlMetricsDbResult')
            ->willReturn($mockResult);

        $collector->expects($this->once())
            ->method('getPostUrlMetricsDueUrlDbResult')
            ->willReturn($mockDueResult);

        $result = $collector->getPostUrlMetrics();

        $this->assertEquals(['total' => 5, 'mastodon' => 3, 'bluesky' => 2, 'due' => 4], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('getPostUrlMetrics - handles missing mastodon or bluesky results')]
    #[AllowMockObjectsWithoutExpectations]
    public function testGetPostUrlMetricsHandlesMissingServiceResults()
    {
        $mockResult = new class {
            public function count()
            {
                return 1;
            }
            public function filterBy($field, $value)
            {
                if ($value === 'mastodon') {
                    return new class {
                        public function first()
                        {
                            return (object)['urls' => 5];
                        }
                    };
                }
                return new class {
                    public function first()
                    {
                        return null;
                    }
                };
            }
        };

        $mockDueResult = new class {
            public function first()
            {
                return (object)['urls' => 2];
            }
        };

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getPostUrlMetricsDbResult', 'getPostUrlMetricsDueUrlDbResult'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getPostUrlMetricsDbResult')
            ->willReturn($mockResult);

        $collector->expects($this->once())
            ->method('getPostUrlMetricsDueUrlDbResult')
            ->willReturn($mockDueResult);

        $result = $collector->getPostUrlMetrics();

        $this->assertEquals(['total' => 5, 'mastodon' => 5, 'bluesky' => 0, 'due' => 2], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('parseResponses - returns zero when postUrls is empty')]
    #[AllowMockObjectsWithoutExpectations]
    public function testParseResponsesReturnsZeroWhenPostUrlsEmpty()
    {
        $collector = new ResponseCollector(true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock);

        $result = $collector->parseResponses('', 'mastodon');

        $this->assertEquals(['urls' => 0, 'responses' => 0], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('parseResponses - returns zero for unknown service')]
    #[AllowMockObjectsWithoutExpectations]
    public function testParseResponsesReturnsZeroForUnknownService()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getLastResponsesFromDb'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getLastResponsesFromDb')
            ->willReturn(null);

        $result = $collector->parseResponses('url1', 'unknown');

        $this->assertEquals(['urls' => 0, 'responses' => 0], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('parseResponses - calls fetchResponseByType for each mastodon response type')]
    #[AllowMockObjectsWithoutExpectations]
    public function testParseResponsesCallsFetchResponseByTypeForMastodon()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getLastResponsesFromDb', 'cleanPostUrls', 'disablePostUrls', 'fetchResponseByType', 'updateLastFetched'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getLastResponsesFromDb')
            ->willReturn(null);

        $collector->expects($this->once())
            ->method('cleanPostUrls')
            ->willReturn(['valid' => ['url1'], 'invalid' => []]);

        $collector->expects($this->exactly(3)) // like-of, repost-of, in-reply-to
            ->method('fetchResponseByType')
            ->willReturn(2);

        $collector->expects($this->once())
            ->method('updateLastFetched');

        $result = $collector->parseResponses('url1', 'mastodon');

        $this->assertEquals(['urls' => 1, 'responses' => 6], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('parseResponses - calls fetchResponseByType for each bluesky response type')]
    #[AllowMockObjectsWithoutExpectations]
    public function testParseResponsesCallsFetchResponseByTypeForBluesky()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getLastResponsesFromDb', 'cleanPostUrls', 'disablePostUrls', 'fetchResponseByType', 'updateLastFetched'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getLastResponsesFromDb')
            ->willReturn(null);

        $collector->expects($this->once())
            ->method('cleanPostUrls')
            ->willReturn(['valid' => ['url1'], 'invalid' => []]);

        $collector->expects($this->exactly(4)) // like-of, repost-of, mention-of, in-reply-to
            ->method('fetchResponseByType')
            ->willReturn(1);

        $collector->expects($this->once())
            ->method('updateLastFetched');

        $result = $collector->parseResponses('url1', 'bluesky');

        $this->assertEquals(['urls' => 1, 'responses' => 4], $result);
    }

    #[Group('responseCollector')]
    #[TestDox('parseResponses - disables invalid post urls')]
    #[AllowMockObjectsWithoutExpectations]
    public function testParseResponsesDisablesInvalidPostUrls()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getLastResponsesFromDb', 'cleanPostUrls', 'disablePostUrls', 'fetchResponseByType', 'updateLastFetched'])
            ->getMock();

        $collector->method('getLastResponsesFromDb')->willReturn(null);
        $collector->method('cleanPostUrls')->willReturn(['valid' => [], 'invalid' => ['bad-url']]);
        $collector->method('fetchResponseByType')->willReturn(0);
        $collector->method('updateLastFetched');

        $collector->expects($this->once())
            ->method('disablePostUrls')
            ->with(['bad-url']);

        $collector->parseResponses('bad-url', 'mastodon');
    }

    #[Group('responseCollector')]
    #[TestDox('fetchResponseByType - returns zero when no responses')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFetchResponseByTypeReturnsZeroWhenNoResponses()
    {
        $this->mastodonMock->expects($this->once())
            ->method('fetchResponseByType')
            ->willReturn([]);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->method('getKnownIds')->willReturn([]);
        $collector->expects($this->never())->method('addToQueue');
        $collector->expects($this->never())->method('updateKnownReponses');

        $result = $collector->fetchResponseByType(['url1'], (object)[], 'like-of', $this->mastodonMock);

        $this->assertEquals(0, $result);
    }

    #[Group('responseCollector')]
    #[TestDox('fetchResponseByType - adds responses to queue and returns count')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFetchResponseByTypeAddsToQueueAndReturnsCount()
    {
        $responseData = [
            'latestId' => 'resp1',
            'data' => [
                [
                    'postUrl' => 'url1',
                    'responseId' => 'resp1',
                    'responseType' => 'like-of',
                    'responseSource' => 'mastodon',
                    'responseDate' => '2025-01-01T00:00:00Z',
                    'responseText' => '',
                    'responseUrl' => '',
                    'authorId' => 'author1',
                    'authorName' => 'Alice',
                    'authorUsername' => 'alice',
                    'authorAvatar' => 'avatar.png',
                    'authorUrl' => 'https://mastodon.social/@alice',
                ]
            ]
        ];

        $this->mastodonMock->expects($this->once())
            ->method('fetchResponseByType')
            ->willReturn($responseData);

        $this->urlHandlerMock->expects($this->once())
            ->method('isBlockedSource')
            ->with('https://mastodon.social/@alice')
            ->willReturn(false);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->method('getKnownIds')->willReturn([]);

        $collector->expects($this->once())
            ->method('addToQueue')
            ->with(
                postUrl: 'url1',
                responseId: 'resp1',
                responseType: 'like-of',
                responseSource: 'mastodon',
                responseDate: '2025-01-01T00:00:00Z',
                authorId: 'author1',
                authorName: 'Alice',
                authorUsername: 'alice',
                authorAvatar: 'avatar.png',
                authorUrl: 'https://mastodon.social/@alice',
                responseText: '',
                responseUrl: '',
            );

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'resp1', 'like-of');

        $result = $collector->fetchResponseByType(['url1'], (object)[], 'like-of', $this->mastodonMock);

        $this->assertEquals(1, $result);
    }

    #[Group('responseCollector')]
    #[TestDox('fetchResponseByType - skips blocked sources')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFetchResponseByTypeSkipsBlockedSources()
    {
        $responseData = [
            'latestId' => 'resp1',
            'data' => [
                [
                    'postUrl' => 'url1',
                    'responseId' => 'resp1',
                    'responseType' => 'like-of',
                    'responseSource' => 'mastodon',
                    'responseDate' => '2025-01-01T00:00:00Z',
                    'responseText' => '',
                    'responseUrl' => '',
                    'authorId' => 'author1',
                    'authorName' => 'Alice',
                    'authorUsername' => 'alice',
                    'authorAvatar' => 'avatar.png',
                    'authorUrl' => 'https://blocked.social/@alice',
                ]
            ]
        ];

        $this->mastodonMock->expects($this->once())
            ->method('fetchResponseByType')
            ->willReturn($responseData);

        $this->urlHandlerMock->expects($this->once())
            ->method('isBlockedSource')
            ->with('https://blocked.social/@alice')
            ->willReturn(true);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->method('getKnownIds')->willReturn([]);
        $collector->expects($this->never())->method('addToQueue');

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'resp1', 'like-of');

        $result = $collector->fetchResponseByType(['url1'], (object)[], 'like-of', $this->mastodonMock);

        $this->assertEquals(0, $result);
    }

    #[Group('responseCollector')]
    #[TestDox('fetchResponseByType - skips known responses')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFetchResponseByTypeSkipsKnownResponses()
    {
        $this->mastodonMock->expects($this->once())
            ->method('fetchResponseByType')
            ->with('url1', ['resp1'], 'like-of')
            ->willReturn([
                'latestId' => 'resp1',
                'data' => []
            ]);

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->willReturn(['resp1']);

        $collector->expects($this->never())->method('addToQueue');

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'resp1', 'like-of');

        $result = $collector->fetchResponseByType(['url1'], (object)[], 'like-of', $this->mastodonMock);

        $this->assertEquals(0, $result);
    }
}
