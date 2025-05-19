<?php

use mauricerenck\IndieConnector\ResponseCollector;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\TestCaseMocked;
use Kirby\Content\Content;
use Kirby\Cms\Collection;

final class ResponseCollectorTest extends TestCaseMocked
{
    private $indieDb;
    private $collector;

    public function setUp(): void
    {
        parent::setUp();

        $this->indieDb = $this->createMock(IndieConnectorDatabase::class);

        $this->collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
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
                ['id', 'page_uuid', 'post_url', 'post_type'],
                $this->callback(function ($values) {
                    return count($values) === 4;
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
                ['post_url'],
                ['url'],
                $this->stringContains('WHERE id = "existing-id"')
            );

        $this->collector->registerPostUrl('uuid', 'url', 'type');
    }

    /**
     * @group responseCollector
     * @testdox getDuePostUrls - calls parsers with correct urls
     */
    public function testGetDuePostUrlsCallsParsersWithCorrectUrls()
    {
        // Mock the result of $this->indieDb->query($query)
        $mockResult = $this->getMockBuilder(stdClass::class)
            ->addMethods(['filterBy', 'first'])
            ->getMock();

        // Simulate filterBy('post_type', 'mastodon')->first()->post_urls
        $mastodonResult = (object)['post_urls' => 'mastodon_url1,mastodon_url2'];
        $blueskyResult = (object)['post_urls' => 'bluesky_url1,bluesky_url2'];

        $mockResult->expects($this->exactly(2))
            ->method('filterBy')
            ->willReturnCallback(function ($field, $type) use ($mastodonResult, $blueskyResult) {
                if ($type === 'mastodon') {
                    return new class($mastodonResult) {
                        private $result;
                        public function __construct($result)
                        {
                            $this->result = $result;
                        }
                        public function first()
                        {
                            return $this->result;
                        }
                    };
                }
                if ($type === 'bluesky') {
                    return new class($blueskyResult) {
                        private $result;
                        public function __construct($result)
                        {
                            $this->result = $result;
                        }
                        public function first()
                        {
                            return $this->result;
                        }
                    };
                }
                // Always return an object with a first() method, even for unexpected types
                return new class {
                    public function first()
                    {
                        return null;
                    }
                };
            });

        $this->indieDb->expects($this->exactly(1))
            ->method('query')
            ->willReturn($mockResult);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
            ->onlyMethods(['parseMastodonResponses', 'parseBlueskyResponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('parseMastodonResponses')
            ->with('mastodon_url1,mastodon_url2');

        $collector->expects($this->once())
            ->method('parseBlueskyResponses')
            ->with('bluesky_url1,bluesky_url2');

        $collector->getDuePostUrls();
    }

    /**
     * @group responseCollector
     * @testdox parseMastodonResponses - calls fetch methods with correct arguments
     */
    public function testParseMastodonResponsesCallsFetchMethodsWithCorrectArguments()
    {
        // Simulate the result of the query for known_responses
        $mockLastResponses = (object)[
            'ids' => 'id1,id2',
            'post_type' => 'mastodon'
        ];

        $this->indieDb->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("url1", "url2") GROUP BY post_type;'))
            ->willReturn($mockLastResponses);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
            ->onlyMethods(['fetchMastodonLikes', 'fetchMastodonReblogs', 'fetchMastodonReplies'])
            ->getMock();

        $collector->expects($this->once())
            ->method('fetchMastodonLikes')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchMastodonReblogs')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchMastodonReplies')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->parseMastodonResponses('url1,url2');
    }

    /**
     * @group responseCollector
     * @testdox parseMastodonResponses - handles empty post urls
     */
    public function testParseMastodonResponsesHandlesEmptyPostUrls()
    {
        // Should still call query, but with empty postUrls
        $mockLastResponses = (object)[
            'ids' => '',
            'post_type' => 'mastodon'
        ];

        $this->indieDb->expects($this->once())
            ->method('query')
            ->with($this->stringContains('IN ("")'))
            ->willReturn($mockLastResponses);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
            ->onlyMethods(['fetchMastodonLikes', 'fetchMastodonReblogs', 'fetchMastodonReplies'])
            ->getMock();

        $collector->expects($this->once())
            ->method('fetchMastodonLikes')
            ->with([''], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchMastodonReblogs')
            ->with([''], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchMastodonReplies')
            ->with([''], $mockLastResponses);

        $collector->parseMastodonResponses('');
    }

    /**
     * @group responseCollector
     * @testdox parseBlueskyResponses - calls fetch methods with correct arguments
     */
    public function testParseBlueskyResponsesCallsFetchMethodsWithCorrectArguments()
    {
        // Simulate the result of the query for known_responses
        $mockLastResponses = (object)[
            'ids' => 'id1,id2',
            'post_type' => 'mastodon'
        ];

        $this->indieDb->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("url1", "url2") GROUP BY post_type;'))
            ->willReturn($mockLastResponses);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
            ->onlyMethods(['fetchBlueskyLikes', 'fetchBlueskyReposts', 'fetchBlueskyQuotes', 'fetchBlueskyReplies'])
            ->getMock();

        $collector->expects($this->once())
            ->method('fetchBlueskyLikes')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyReposts')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyQuotes')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyReplies')
            ->with(['url1', 'url2'], $mockLastResponses);

        $collector->parseBlueskyResponses('url1,url2');
    }

    /**
     * @group responseCollector
     * @testdox parseBlueskyResponses - handles empty post urls
     */
    public function testParseBlueskyResponsesHandlesEmptyPostUrls()
    {
        // Should still call query, but with empty postUrls
        $mockLastResponses = (object)[
            'ids' => '',
            'post_type' => 'mastodon'
        ];

        $this->indieDb->expects($this->once())
            ->method('query')
            ->with($this->stringContains('IN ("")'))
            ->willReturn($mockLastResponses);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb])
            ->onlyMethods(['fetchBlueskyLikes', 'fetchBlueskyReposts', 'fetchBlueskyQuotes', 'fetchBlueskyReplies'])
            ->getMock();

        $collector->expects($this->once())
            ->method('fetchBlueskyLikes')
            ->with([''], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyReposts')
            ->with([''], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyQuotes')
            ->with([''], $mockLastResponses);

        $collector->expects($this->once())
            ->method('fetchBlueskyReplies')
            ->with([''], $mockLastResponses);

        $collector->parseBlueskyResponses('');
    }
}
