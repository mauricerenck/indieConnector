<?php

use mauricerenck\IndieConnector\ResponseCollector;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\TestCaseMocked;

final class ResponseCollectorTest extends TestCaseMocked
{
    private $indieDb;
    private $collector;
    private $mastodonMock;
    private $blueskyMock;
    private $urlHandlerMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->indieDb = $this->createMock(IndieConnectorDatabase::class);
        $this->mastodonMock = $this->createMock(\mauricerenck\IndieConnector\Mastodon::class);
        $this->blueskyMock = $this->createMock(\mauricerenck\IndieConnector\Bluesky::class);
        $this->urlHandlerMock = $this->createMock(\mauricerenck\IndieConnector\UrlHandler::class);

        $this->collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['isEnabled'])
            ->getMock();
    }

    /**
     * @group responseCollector
     * @testdox registerPostUrl - does nothing when disabled
     */
    public function testRegisterPostUrlDoesNothingWhenDisabled()
    {
        $this->collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->indieDb->expects($this->never())->method('select');
        $this->collector->registerPostUrl('uuid', 'url', 'type');
    }

    /**
     * @group responseCollector
     * @testdox registerPostUrl - inserts when no existing
     */
    public function testRegisterPostUrlInsertsWhenNoExisting()
    {
        $this->collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->indieDb->expects($this->once())
            ->method('select')
            ->willReturn(new class {
                public function count()
                {
                    return 0;
                }
            });

        $this->indieDb->expects($this->once())
            ->method('insert')
            ->with(
                'external_post_urls',
                ['id', 'page_uuid', 'post_url', 'post_type', 'last_fetched'],
                $this->callback(function ($values) {
                    return count($values) === 5;
                })
            );

        $this->collector->registerPostUrl('uuid', 'url', 'type');
    }

    /**
     * @group responseCollector
     * @testdox registerPostUrl - updates when existing
     */
    public function testRegisterPostUrlUpdatesWhenExisting()
    {

        $this->collector->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $existing = new class {
            public $id = 'existing-id';
        };

        $this->indieDb->expects($this->once())
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

        $this->indieDb->expects($this->once())
            ->method('update')
            ->with(
                'external_post_urls',
                ['post_url', 'last_fetched'],
                ['url', '1'],
                $this->stringContains('WHERE id = "existing-id"')
            );

        $this->collector->registerPostUrl('uuid', 'url', 'type');
    }

    /**
     * @group responseCollector
     * @testdox getDuePostUrls - calls parser with correct urls
     */
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

    /**
     * @group responseCollector
     * @testdox getDuePostUrls - returns zero when no results
     */
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

    /**
     * @group responseCollector
     * @testdox getDuePostUrls - aggregates responses correctly
     */
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


    /**
     * @group responseCollector
     * @testdox getPostUrlMetrics - returns zero when no results
     */
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

    /**
     * @group responseCollector
     * @testdox getPostUrlMetrics - returns correct metrics
     */
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

    /**
     * @group responseCollector
     * @testdox getPostUrlMetrics - handles missing mastodon or bluesky results
     */
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

    /**
     * @group responseCollector
     * @testdox parseResponses - returns zero when postUrls is empty
     */
    public function testParseResponsesReturnsZeroWhenPostUrlsEmpty()
    {
        $collector = new ResponseCollector(true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock);

        $result = $collector->parseResponses('', 'mastodon');

        $this->assertEquals(['urls' => 0, 'responses' => 0], $result);
    }

    /**
     * @group responseCollector
     * @testdox parseResponses - returns zero for unknown service
     */
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

    /**
     * @group responseCollector
     * @testdox parseResponses - calls fetchResponseByType for each mastodon response type
     */
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

    /**
     * @group responseCollector
     * @testdox parseResponses - calls fetchResponseByType for each bluesky response type
     */
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

    /**
     * @group responseCollector
     * @testdox parseResponses - disables invalid post urls
     */
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

    /**
     * @group responseCollector
     * @testdox fetchResponseByType - returns zero when no responses
     */
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

    /**
     * @group responseCollector
     * @testdox fetchResponseByType - adds responses to queue and returns count
     */
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

    /**
     * @group responseCollector
     * @testdox fetchResponseByType - skips blocked sources
     */
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

    /**
     * @group responseCollector
     * @testdox fetchResponseByType - skips known responses
     */
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

    /**
     * @group responseCollector
     * @testdox convertToWebmentionHookData - skips responses with no matching page
     */
    public function testConvertToWebmentionHookDataSkipsResponsesWithNoPage()
    {
        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getSourceBaseUrl', 'getPageByUuid'])
            ->getMock();

        $collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $collector->method('getPageByUuid')->willReturn(null);

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

        $result = $collector->convertToWebmentionHookData([$response]);

        $this->assertEquals([], $result);
    }

    /**
     * @group responseCollector
     * @testdox convertToWebmentionHookData - returns correct data structure
     */
    public function testConvertToWebmentionHookDataReturnsCorrectStructure()
    {
        $mockPage = new class {
            public function url()
            {
                return 'https://example.com/my-post';
            }
        };

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getSourceBaseUrl', 'getPageByUuid'])
            ->getMock();

        $collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $collector->method('getPageByUuid')->willReturn($mockPage);

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

        $result = $collector->convertToWebmentionHookData([$response]);

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

    /**
     * @group responseCollector
     * @testdox convertToWebmentionHookData - handles multiple responses
     */
    public function testConvertToWebmentionHookDataHandlesMultipleResponses()
    {
        $mockPage = new class {
            public function url()
            {
                return 'https://example.com/my-post';
            }
        };

        $collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, 10, 60, 50, $this->indieDb, $this->mastodonMock, $this->blueskyMock, $this->urlHandlerMock])
            ->onlyMethods(['getSourceBaseUrl', 'getPageByUuid'])
            ->getMock();

        $collector->method('getSourceBaseUrl')->willReturn('https://example.com/indieconnector/response/');
        $collector->expects($this->exactly(3))
            ->method('getPageByUuid')
            ->willReturnOnConsecutiveCalls($mockPage, null, $mockPage);

        $responses = [
            (object)['id' => 'resp1', 'page_uuid' => 'uuid1', 'response_type' => 'like-of', 'response_date' => '2025-01-01', 'response_text' => '', 'response_source' => 'mastodon', 'author_name' => 'Alice', 'author_avatar' => 'avatar.png', 'author_url' => 'https://mastodon.social/@alice'],
            (object)['id' => 'resp2', 'page_uuid' => 'uuid2', 'response_type' => 'repost-of', 'response_date' => '2025-01-02', 'response_text' => '', 'response_source' => 'mastodon', 'author_name' => 'Bob', 'author_avatar' => 'avatar2.png', 'author_url' => 'https://mastodon.social/@bob'],
            (object)['id' => 'resp3', 'page_uuid' => 'uuid3', 'response_type' => 'in-reply-to', 'response_date' => '2025-01-03', 'response_text' => 'Nice!', 'response_source' => 'bluesky', 'author_name' => 'Carol', 'author_avatar' => 'avatar3.png', 'author_url' => 'https://bsky.app/carol'],
        ];

        $result = $collector->convertToWebmentionHookData($responses);

        $this->assertCount(2, $result);
        $this->assertEquals('resp1', $result[0]['id']);
        $this->assertEquals('resp3', $result[1]['id']);
    }
}
