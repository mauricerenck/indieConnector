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

    public function setUp(): void
    {
        parent::setUp();

        $this->indieDb = $this->createMock(IndieConnectorDatabase::class);
        $this->mastodonMock = $this->createMock(\mauricerenck\IndieConnector\MastodonReceiver::class);
        $this->blueskyMock = $this->createMock(\mauricerenck\IndieConnector\BlueskyReceiver::class);

        $this->collector = $this->getMockBuilder(ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock, $this->blueskyMock])
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

    /**
     * @group responseCollector
     * @testdox fetchMastodonLikes - adds new likes to queue and updates known responses
     */
    public function testFetchMastodonLikesAddsNewLikesToQueueAndUpdatesKnownResponses()
    {
        // Arrange
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'likes', ['known1'])
            ->willReturn([
                [
                    'id' => 'like1',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'display_name' => 'Alice',
                    'username' => 'alice',
                    'avatar_static' => 'avatar.png',
                    'url' => 'https://mastodon.social/@alice'
                ]
            ]);

        // Patch MastodonReceiver instantiation
        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'like-of')
            ->willReturn(['known1']);

        $collector->expects($this->once())
            ->method('addToQueue')
            ->with(
                postUrl: 'url1',
                responseId: 'like1',
                responseType: 'like-of',
                responseSource: 'mastodon',
                responseDate: '2024-01-01T00:00:00Z',
                authorId: 'like1',
                authorName: 'Alice',
                authorUsername: 'alice',
                authorAvatar: 'avatar.png',
                authorUrl: 'https://mastodon.social/@alice'
            );

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'like1', 'like-of');

        $collector->fetchMastodonLikes(['url1'], (object)[]);
    }

    /**
     * @group responseCollector
     * @testdox fetchMastodonLikes - skips known likes
     */
    public function testFetchMastodonLikesSkipsKnownLikes()
    {
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'likes', ['like1'])
            ->willReturn([
                [
                    'id' => 'like1',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'display_name' => 'Alice',
                    'username' => 'alice',
                    'avatar_static' => 'avatar.png',
                    'url' => 'https://mastodon.social/@alice'
                ]
            ]);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'like-of')
            ->willReturn(['like1']);

        $collector->expects($this->never())
            ->method('addToQueue');

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'like1', 'like-of');

        $collector->fetchMastodonLikes(['url1'], (object)[]);
    }

    /**
     * @group responseCollector
     * @testdox fetchMastodonReblogs - adds new reblogs to queue and updates known responses
     */
    public function testFetchMastodonReblogsAddsNewReblogsToQueueAndUpdatesKnownResponses()
    {
        // Arrange
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'reposts', ['known1'])
            ->willReturn([
                [
                    'id' => 'reblog1',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'display_name' => 'Alice',
                    'username' => 'alice',
                    'avatar_static' => 'avatar.png',
                    'url' => 'https://mastodon.social/@alice'
                ]
            ]);

        // Patch MastodonReceiver instantiation
        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'repost-of')
            ->willReturn(['known1']);

        $collector->expects($this->once())
            ->method('addToQueue')
            ->with(
                postUrl: 'url1',
                responseId: 'reblog1',
                responseType: 'repost-of',
                responseSource: 'mastodon',
                responseDate: '2024-01-01T00:00:00Z',
                authorId: 'reblog1',
                authorName: 'Alice',
                authorUsername: 'alice',
                authorAvatar: 'avatar.png',
                authorUrl: 'https://mastodon.social/@alice'
            );

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'reblog1', 'repost-of');

        $collector->fetchMastodonReblogs(['url1'], (object)[]);
    }

    /**
     * @group responseCollector
     * @testdox fetchMastodonReblogs - skips known reblogs
     */
    public function testFetchMastodonReblogsSkipsKnownReblogs()
    {
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'reposts', ['reblog1'])
            ->willReturn([
                [
                    'id' => 'reblog1',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'display_name' => 'Alice',
                    'username' => 'alice',
                    'avatar_static' => 'avatar.png',
                    'url' => 'https://mastodon.social/@alice'
                ]
            ]);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'repost-of')
            ->willReturn(['reblog1']);

        $collector->expects($this->never())
            ->method('addToQueue');

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'reblog1', 'repost-of');

        $collector->fetchMastodonReblogs(['url1'], (object)[]);
    }

    /**
     * @group responseCollector
     * @testdox fetchMastodonReplies - adds new replies to queue and updates known responses
     */
    public function testFetchMastodonRepliesAddsNewRepliesToQueueAndUpdatesKnownResponses()
    {
        // Arrange
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'replies', ['known1'])
            ->willReturn([
                [
                    'id' => 'reply1',
                    'in_reply_to_id' => "post1",
                    'visibility' => 'public',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'content' => 'hello world!',
                    'url' => 'https://example.com',
                    'account' => [
                        'id' => 'user1',
                        'display_name' => 'Alice',
                        'username' => 'alice',
                        'avatar_static' => 'avatar.png',
                        'url' => 'https://mastodon.social/@alice'
                    ]
                ]
            ]);

        $this->mastodonMock->expects($this->once())
            ->method('getPostUrlData')
            ->with('url1')
            ->willReturn(['host1', 'post1']);

        // Patch MastodonReceiver instantiation
        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();

        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'in-reply-to')
            ->willReturn(['known1']);

        $collector->expects($this->once())
            ->method('addToQueue')
            ->with(
                postUrl: 'url1',
                responseId: 'reply1',
                responseType: 'in-reply-to',
                responseSource: 'mastodon',
                responseDate: '2024-01-01T00:00:00Z',
                authorId: 'user1',
                authorName: 'Alice',
                authorUsername: 'alice',
                authorAvatar: 'avatar.png',
                authorUrl: 'https://mastodon.social/@alice',
                responseText: 'hello world!',
                responseUrl: 'https://example.com',
            );

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'reply1', 'in-reply-to');

        $collector->fetchMastodonReplies(['url1'], (object)[]);
    }

    /**
     * @group responseCollector
     * @testdox fetchMastodonReplies - skips known replies
     */
    public function testFetchMastodonRepliesSkipsKnownReplies()
    {
        $this->mastodonMock->expects($this->once())
            ->method('getResponses')
            ->with('url1', 'replies', ['reply1'])
            ->willReturn([
                [
                    'id' => 'reply1',
                    'in_reply_to_id' => "post1",
                    'visibility' => 'public',
                    'created_at' => '2024-01-01T00:00:00Z',
                    'content' => 'hello world!',
                    'url' => 'https://example.com',
                    'account' => [
                        'id' => 'user1',
                        'display_name' => 'Alice',
                        'username' => 'alice',
                        'avatar_static' => 'avatar.png',
                        'url' => 'https://mastodon.social/@alice'
                    ]
                ]
            ]);

        $this->mastodonMock->expects($this->once())
            ->method('getPostUrlData')
            ->with('url1')
            ->willReturn(['host1', 'post1']);

        $collector = $this->getMockBuilder(\mauricerenck\IndieConnector\ResponseCollector::class)
            ->setConstructorArgs([true, true, true, $this->indieDb, $this->mastodonMock])
            ->onlyMethods(['getKnownIds', 'addToQueue', 'updateKnownReponses'])
            ->getMock();


        $collector->expects($this->once())
            ->method('getKnownIds')
            ->with($this->anything(), 'in-reply-to')
            ->willReturn(['reply1']);

        $collector->expects($this->never())
            ->method('addToQueue');

        $collector->expects($this->once())
            ->method('updateKnownReponses')
            ->with('url1', 'reply1', 'in-reply-to');

        $collector->fetchMastodonReplies(['url1'], (object)[]);
    }
}
