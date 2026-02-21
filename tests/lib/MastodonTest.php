<?php

namespace mauricerenck\IndieConnector;

use PHPUnit\Framework\TestCase;

class MastodonTest extends TestCase
{
    private $outboxMock;
    private $externalPostSenderMock;

    public function setUp(): void
    {
        $this->outboxMock = $this->createMock(Outbox::class);
        $this->externalPostSenderMock = $this->createMock(ExternalPostSender::class);
    }

    // -------------------------
    // extractMastodonNextPageUrl
    // -------------------------

    /**
     * @group mastodon
     * @testdox extractMastodonNextPageUrl - returns next page url
     */
    public function testExtractMastodonNextPageUrlReturnsNextPageUrl()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);
        $link = '<https://mastodon.social/api/v1/statuses/123/favourited_by?max_id=456>; rel="next", <https://mastodon.social/api/v1/statuses/123/favourited_by?min_id=789>; rel="prev"';

        $result = $mastodon->extractMastodonNextPageUrl($link);

        $this->assertEquals('https://mastodon.social/api/v1/statuses/123/favourited_by?max_id=456', $result);
    }

    /**
     * @group mastodon
     * @testdox extractMastodonNextPageUrl - returns null when no next page
     */
    public function testExtractMastodonNextPageUrlReturnsNullWhenNoNextPage()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);
        $link = '<https://mastodon.social/api/v1/statuses/123/favourited_by?min_id=789>; rel="prev"';

        $result = $mastodon->extractMastodonNextPageUrl($link);

        $this->assertNull($result);
    }

    // -------------------------
    // responsesIncludeKnownId
    // -------------------------

    /**
     * @group mastodon
     * @testdox responsesIncludeKnownId - returns true when known id is in responses
     */
    public function testResponsesIncludeKnownIdReturnsTrueWhenKnownIdFound()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);
        $responses = [['id' => 'abc'], ['id' => 'def']];
        $knownIds = ['def', 'ghi'];

        $result = $mastodon->responsesIncludeKnownId($responses, $knownIds);

        $this->assertTrue($result);
    }

    /**
     * @group mastodon
     * @testdox responsesIncludeKnownId - returns false when no known id in responses
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenNoKnownIdFound()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);
        $responses = [['id' => 'abc'], ['id' => 'def']];
        $knownIds = ['xyz'];

        $result = $mastodon->responsesIncludeKnownId($responses, $knownIds);

        $this->assertFalse($result);
    }

    /**
     * @group mastodon
     * @testdox responsesIncludeKnownId - returns false when responses are empty
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenResponsesEmpty()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        $result = $mastodon->responsesIncludeKnownId([], ['abc']);

        $this->assertFalse($result);
    }

    // -------------------------
    // getPostUrlData
    // -------------------------

    /**
     * @group mastodon
     * @testdox getPostUrlData - returns host and post id from valid url
     */
    public function testGetPostUrlDataReturnsHostAndPostId()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        [$host, $postId] = $mastodon->getPostUrlData('https://mastodon.social/@alice/123456789');

        $this->assertEquals('mastodon.social', $host);
        $this->assertEquals('123456789', $postId);
    }

    /**
     * @group mastodon
     * @testdox getPostUrlData - returns null for invalid url
     */
    public function testGetPostUrlDataReturnsNullForInvalidUrl()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        [$host, $postId] = $mastodon->getPostUrlData('not-a-url');

        $this->assertNull($host);
        $this->assertNull($postId);
    }

    // -------------------------
    // fetchResponseByType
    // -------------------------

    /**
     * @group mastodon
     * @testdox fetchResponseByType - calls getLikes for like-of
     */
    public function testFetchResponseByTypeCallsGetLikes()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getLikes'])
            ->getMock();

        $mastodon->expects($this->once())
            ->method('getLikes')
            ->with(knownIds: ['known1'], postUrl: 'https://mastodon.social/@alice/123')
            ->willReturn([]);

        $mastodon->fetchResponseByType('https://mastodon.social/@alice/123', ['known1'], 'like-of');
    }

    /**
     * @group mastodon
     * @testdox fetchResponseByType - calls getReposts for repost-of
     */
    public function testFetchResponseByTypeCallsGetReposts()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getReposts'])
            ->getMock();

        $mastodon->expects($this->once())
            ->method('getReposts')
            ->with(knownIds: ['known1'], postUrl: 'https://mastodon.social/@alice/123')
            ->willReturn([]);

        $mastodon->fetchResponseByType('https://mastodon.social/@alice/123', ['known1'], 'repost-of');
    }

    /**
     * @group mastodon
     * @testdox fetchResponseByType - calls getReplies for in-reply-to
     */
    public function testFetchResponseByTypeCallsGetReplies()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getReplies'])
            ->getMock();

        $mastodon->expects($this->once())
            ->method('getReplies')
            ->with(knownIds: ['known1'], postUrl: 'https://mastodon.social/@alice/123')
            ->willReturn([]);

        $mastodon->fetchResponseByType('https://mastodon.social/@alice/123', ['known1'], 'in-reply-to');
    }

    /**
     * @group mastodon
     * @testdox fetchResponseByType - returns empty array for unknown type
     */
    public function testFetchResponseByTypeReturnsEmptyForUnknownType()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        $result = $mastodon->fetchResponseByType('https://mastodon.social/@alice/123', [], 'unknown');

        $this->assertEquals([], $result);
    }

    // -------------------------
    // getLikes
    // -------------------------

    /**
     * @group mastodon
     * @testdox getLikes - returns empty array when no favs
     */
    public function testGetLikesReturnsEmptyWhenNoFavs()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([]);

        $result = $mastodon->getLikes([], 'https://mastodon.social/@alice/123');

        $this->assertEquals([], $result);
    }

    /**
     * @group mastodon
     * @testdox getLikes - returns formatted like data
     */
    public function testGetLikesReturnsFormattedData()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses', 'currentDateTime'])
            ->getMock();

        $mastodon->method('currentDateTime')->willReturn('2025-01-01 00:00:00');
        $mastodon->method('getResponses')->willReturn([
            ['id' => 'like1', 'display_name' => 'Alice', 'username' => 'alice', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@alice'],
        ]);

        $result = $mastodon->getLikes([], 'https://mastodon.social/@alice/123');

        $this->assertEquals('like1', $result['latestId']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals([
            'postUrl' => 'https://mastodon.social/@alice/123',
            'responseId' => 'like1',
            'responseType' => 'like-of',
            'responseSource' => 'mastodon',
            'responseDate' => '2025-01-01 00:00:00',
            'responseText' => '',
            'responseUrl' => 'https://mastodon.social/@alice/123',
            'authorId' => 'like1',
            'authorName' => 'Alice',
            'authorUsername' => 'alice',
            'authorAvatar' => 'avatar.png',
            'authorUrl' => 'https://mastodon.social/@alice',
        ], $result['data'][0]);
    }

    /**
     * @group mastodon
     * @testdox getLikes - skips known likes
     */
    public function testGetLikesSkipsKnownLikes()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses', 'currentDateTime'])
            ->getMock();

        $mastodon->method('currentDateTime')->willReturn('2025-01-01 00:00:00');
        $mastodon->method('getResponses')->willReturn([
            ['id' => 'like1', 'display_name' => 'Alice', 'username' => 'alice', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@alice'],
            ['id' => 'like2', 'display_name' => 'Bob', 'username' => 'bob', 'avatar_static' => 'avatar2.png', 'url' => 'https://mastodon.social/@bob'],
        ]);

        $result = $mastodon->getLikes(['like2'], 'https://mastodon.social/@alice/123');

        $this->assertCount(1, $result['data']);
        $this->assertEquals('like1', $result['data'][0]['responseId']);
    }

    // -------------------------
    // getReposts
    // -------------------------

    /**
     * @group mastodon
     * @testdox getReposts - returns empty array when no reposts
     */
    public function testGetRepostsReturnsEmptyWhenNoReposts()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([]);

        $result = $mastodon->getReposts([], 'https://mastodon.social/@alice/123');

        $this->assertEquals([], $result);
    }

    /**
     * @group mastodon
     * @testdox getReposts - returns formatted repost data
     */
    public function testGetRepostsReturnsFormattedData()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses', 'currentDateTime'])
            ->getMock();

        $mastodon->method('currentDateTime')->willReturn('2025-01-01 00:00:00');
        $mastodon->method('getResponses')->willReturn([
            ['id' => 'repost1', 'display_name' => 'Alice', 'username' => 'alice', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@alice'],
        ]);

        $result = $mastodon->getReposts([], 'https://mastodon.social/@alice/123');

        $this->assertEquals('repost1', $result['latestId']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('repost-of', $result['data'][0]['responseType']);
    }

    /**
     * @group mastodon
     * @testdox getReposts - skips known reposts
     */
    public function testGetRepostsSkipsKnownReposts()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses', 'currentDateTime'])
            ->getMock();

        $mastodon->method('currentDateTime')->willReturn('2025-01-01 00:00:00');
        $mastodon->method('getResponses')->willReturn([
            ['id' => 'repost1', 'display_name' => 'Alice', 'username' => 'alice', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@alice'],
            ['id' => 'repost2', 'display_name' => 'Bob', 'username' => 'bob', 'avatar_static' => 'avatar2.png', 'url' => 'https://mastodon.social/@bob'],
        ]);

        $result = $mastodon->getReposts(['repost2'], 'https://mastodon.social/@alice/123');

        $this->assertCount(1, $result['data']);
        $this->assertEquals('repost1', $result['data'][0]['responseId']);
    }

    // -------------------------
    // getReplies
    // -------------------------

    /**
     * @group mastodon
     * @testdox getReplies - returns empty array when no replies
     */
    public function testGetRepliesReturnsEmptyWhenNoReplies()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([]);

        $result = $mastodon->getReplies([], 'https://mastodon.social/@alice/123456789');

        $this->assertEquals([], $result);
    }

    /**
     * @group mastodon
     * @testdox getReplies - returns formatted reply data
     */
    public function testGetRepliesReturnsFormattedData()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([
            [
                'id' => 'reply1',
                'in_reply_to_id' => '123456789',
                'visibility' => 'public',
                'created_at' => '2025-01-01T00:00:00Z',
                'content' => 'Great post!',
                'url' => 'https://mastodon.social/@bob/reply1',
                'account' => [
                    'id' => 'bob1',
                    'display_name' => 'Bob',
                    'username' => 'bob',
                    'avatar_static' => 'avatar.png',
                    'url' => 'https://mastodon.social/@bob',
                ]
            ]
        ]);

        $result = $mastodon->getReplies([], 'https://mastodon.social/@alice/123456789');

        $this->assertEquals('reply1', $result['latestId']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals([
            'postUrl' => 'https://mastodon.social/@alice/123456789',
            'responseId' => 'reply1',
            'responseType' => 'in-reply-to',
            'responseSource' => 'mastodon',
            'responseDate' => '2025-01-01T00:00:00Z',
            'responseText' => 'Great post!',
            'responseUrl' => 'https://mastodon.social/@bob/reply1',
            'authorId' => 'bob1',
            'authorName' => 'Bob',
            'authorUsername' => 'bob',
            'authorAvatar' => 'avatar.png',
            'authorUrl' => 'https://mastodon.social/@bob',
        ], $result['data'][0]);
    }

    /**
     * @group mastodon
     * @testdox getReplies - skips non-public replies
     */
    public function testGetRepliesSkipsNonPublicReplies()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([
            [
                'id' => 'reply1',
                'in_reply_to_id' => '123456789',
                'visibility' => 'private',
                'created_at' => '2025-01-01T00:00:00Z',
                'content' => 'Secret reply',
                'url' => 'https://mastodon.social/@bob/reply1',
                'account' => ['id' => 'bob1', 'display_name' => 'Bob', 'username' => 'bob', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@bob']
            ]
        ]);

        $result = $mastodon->getReplies([], 'https://mastodon.social/@alice/123456789');

        $this->assertCount(0, $result['data']);
    }

    /**
     * @group mastodon
     * @testdox getReplies - skips replies not directed at post
     */
    public function testGetRepliesSkipsRepliesNotDirectedAtPost()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([
            [
                'id' => 'reply1',
                'in_reply_to_id' => '999999999', // different post id
                'visibility' => 'public',
                'created_at' => '2025-01-01T00:00:00Z',
                'content' => 'Reply to someone else',
                'url' => 'https://mastodon.social/@bob/reply1',
                'account' => ['id' => 'bob1', 'display_name' => 'Bob', 'username' => 'bob', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@bob']
            ]
        ]);

        $result = $mastodon->getReplies([], 'https://mastodon.social/@alice/123456789');

        $this->assertCount(0, $result['data']);
    }

    /**
     * @group mastodon
     * @testdox getReplies - skips known replies
     */
    public function testGetRepliesSkipsKnownReplies()
    {
        $mastodon = $this->getMockBuilder(Mastodon::class)
            ->setConstructorArgs(['https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $mastodon->method('getResponses')->willReturn([
            [
                'id' => 'reply1',
                'in_reply_to_id' => '123456789',
                'visibility' => 'public',
                'created_at' => '2025-01-01T00:00:00Z',
                'content' => 'New reply',
                'url' => 'https://mastodon.social/@bob/reply1',
                'account' => ['id' => 'bob1', 'display_name' => 'Bob', 'username' => 'bob', 'avatar_static' => 'avatar.png', 'url' => 'https://mastodon.social/@bob']
            ],
            [
                'id' => 'reply2',
                'in_reply_to_id' => '123456789',
                'visibility' => 'public',
                'created_at' => '2025-01-01T00:00:00Z',
                'content' => 'Known reply',
                'url' => 'https://mastodon.social/@carol/reply2',
                'account' => ['id' => 'carol1', 'display_name' => 'Carol', 'username' => 'carol', 'avatar_static' => 'avatar2.png', 'url' => 'https://mastodon.social/@carol']
            ]
        ]);

        $result = $mastodon->getReplies(['reply2'], 'https://mastodon.social/@alice/123456789');

        $this->assertCount(1, $result['data']);
        $this->assertEquals('reply1', $result['data'][0]['responseId']);
    }

    // -------------------------
    // currentDateTime
    // -------------------------

    /**
     * @group mastodon
     * @testdox currentDateTime - returns current date when no argument given
     */
    public function testCurrentDateTimeReturnsCurrentDate()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        $result = $mastodon->currentDateTime();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    /**
     * @group mastodon
     * @testdox currentDateTime - returns given date when argument provided
     */
    public function testCurrentDateTimeReturnsGivenDate()
    {
        $mastodon = new Mastodon('https://mastodon.social', 'token', true, 0, $this->outboxMock, $this->externalPostSenderMock);

        $result = $mastodon->currentDateTime('2025-01-01 00:00:00');

        $this->assertEquals('2025-01-01 00:00:00', $result);
    }
}
