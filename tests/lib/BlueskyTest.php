<?php

namespace mauricerenck\IndieConnector;

use PHPUnit\Framework\TestCase;
use cjrasmussen\BlueskyApi\BlueskyApi;

class BlueskyTest extends TestCase
{
    private $outboxMock;
    private $bskClientMock;
    private $externalPostSenderMock;

    public function setUp(): void
    {
        $this->outboxMock = $this->createMock(Outbox::class);
        $this->bskClientMock = $this->createMock(BlueskyApi::class);
        $this->externalPostSenderMock = $this->createMock(ExternalPostSender::class);
    }

    // -------------------------
    // getUrlFromDid
    // -------------------------

    /**
     * @group bluesky
     * @testdox getUrlFromDid - converts at uri with handle to https url
     */
    public function testGetUrlFromDidConvertsAtUriWithHandle()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getUrlFromDid('at://alice.bsky.social/app.bsky.feed.post/abc123');

        $this->assertEquals('https://bsky.app/profile/alice.bsky.social/post/abc123', $result);
    }

    /**
     * @group bluesky
     * @testdox getUrlFromDid - returns original string when no match
     */
    public function testGetUrlFromDidReturnsOriginalWhenNoMatch()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getUrlFromDid('not-an-at-uri');

        $this->assertEquals('not-an-at-uri', $result);
    }

    // -------------------------
    // getDidFromUrl
    // -------------------------

    /**
     * @group bluesky
     * @testdox getDidFromUrl - converts https url to at uri with handle
     */
    public function testGetDidFromUrlConvertsHttpsUrlWithHandle()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['resolveHandleToDid'])
            ->getMock();

        $bluesky->method('resolveHandleToDid')->willReturn('did:plc:abc123');

        $result = $bluesky->getDidFromUrl('https://bsky.app/profile/alice.bsky.social/post/abc123');

        $this->assertEquals('at://did:plc:abc123/app.bsky.feed.post/abc123', $result);
    }

    /**
     * @group bluesky
     * @testdox getDidFromUrl - returns original url when no match
     */
    public function testGetDidFromUrlReturnsOriginalWhenNoMatch()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getDidFromUrl('https://example.com/not-a-bluesky-url');

        $this->assertEquals('https://example.com/not-a-bluesky-url', $result);
    }

    // -------------------------
    // didToData
    // -------------------------

    /**
     * @group bluesky
     * @testdox didToData - parses at uri into parts
     */
    public function testDidToDataParsesAtUri()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->didToData('at://did:plc:abc123/app.bsky.feed.post/rkey456');

        $this->assertEquals([
            'did' => 'did:plc:abc123',
            'collection' => 'app.bsky.feed.post',
            'rkey' => 'rkey456',
        ], $result);
    }

    /**
     * @group bluesky
     * @testdox didToData - returns null for invalid at uri
     */
    public function testDidToDataReturnsNullForInvalidUri()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('not-an-at-uri');

        $result = $bluesky->didToData('https://example.com/invalid');

        $this->assertNull($result);
    }

    // -------------------------
    // responsesIncludeKnownId
    // -------------------------

    /**
     * @group bluesky
     * @testdox responsesIncludeKnownId - returns true when known id found
     */
    public function testResponsesIncludeKnownIdReturnsTrueWhenFound()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $response1 = new \stdClass();
        $response1->indieConnectorId = 'abc';
        $response2 = new \stdClass();
        $response2->indieConnectorId = 'def';

        $result = $bluesky->responsesIncludeKnownId([$response1, $response2], ['def', 'xyz']);

        $this->assertTrue($result);
    }

    /**
     * @group bluesky
     * @testdox responsesIncludeKnownId - returns false when no known id found
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenNotFound()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $response1 = new \stdClass();
        $response1->indieConnectorId = 'abc';

        $result = $bluesky->responsesIncludeKnownId([$response1], ['xyz']);

        $this->assertFalse($result);
    }

    /**
     * @group bluesky
     * @testdox responsesIncludeKnownId - returns false for empty responses
     */
    public function testResponsesIncludeKnownIdReturnsFalseForEmptyResponses()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->responsesIncludeKnownId([], ['abc']);

        $this->assertFalse($result);
    }

    // -------------------------
    // appendIndieConnectorId
    // -------------------------

    /**
     * @group bluesky
     * @testdox appendIndieConnectorId - appends id for like-of
     */
    public function testAppendIndieConnectorIdForLikeOf()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $actor = new \stdClass();
        $actor->did = 'did:plc:abc';
        $response = new \stdClass();
        $response->actor = $actor;
        $response->createdAt = '2025-01-01T00:00:00Z';

        $result = $bluesky->appendIndieConnectorId([$response], 'like-of');

        $expectedId = md5('did:plc:abc' . '2025-01-01T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox appendIndieConnectorId - appends id for repost-of
     */
    public function testAppendIndieConnectorIdForRepostOf()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $response = new \stdClass();
        $response->did = 'did:plc:abc';
        $response->createdAt = '2025-01-01T00:00:00Z';

        $result = $bluesky->appendIndieConnectorId([$response], 'repost-of');

        $expectedId = md5('did:plc:abc' . '2025-01-01T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox appendIndieConnectorId - appends cid for mention-of
     */
    public function testAppendIndieConnectorIdForMentionOf()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $response = new \stdClass();
        $response->cid = 'cid123';

        $result = $bluesky->appendIndieConnectorId([$response], 'mention-of');

        $this->assertEquals('cid123', $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox appendIndieConnectorId - appends post cid for in-reply-to
     */
    public function testAppendIndieConnectorIdForInReplyTo()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $post = new \stdClass();
        $post->cid = 'postcid123';
        $response = new \stdClass();
        $response->post = $post;

        $result = $bluesky->appendIndieConnectorId([$response], 'in-reply-to');

        $this->assertEquals('postcid123', $result[0]->indieConnectorId);
    }

    // -------------------------
    // fetchResponseByType
    // -------------------------

    /**
     * @group bluesky
     * @testdox fetchResponseByType - calls getLikes for like-of
     */
    public function testFetchResponseByTypeCallsGetLikes()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl', 'getLikes'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('at://did:plc:abc/app.bsky.feed.post/123');
        $bluesky->expects($this->once())->method('getLikes')->willReturn([]);

        $bluesky->fetchResponseByType('https://bsky.app/profile/alice/post/123', [], 'like-of');
    }

    /**
     * @group bluesky
     * @testdox fetchResponseByType - calls getReposts for repost-of
     */
    public function testFetchResponseByTypeCallsGetReposts()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl', 'getReposts'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('at://did:plc:abc/app.bsky.feed.post/123');
        $bluesky->expects($this->once())->method('getReposts')->willReturn([]);

        $bluesky->fetchResponseByType('https://bsky.app/profile/alice/post/123', [], 'repost-of');
    }

    /**
     * @group bluesky
     * @testdox fetchResponseByType - calls getQuotes for mention-of
     */
    public function testFetchResponseByTypeCallsGetQuotes()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl', 'getQuotes'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('at://did:plc:abc/app.bsky.feed.post/123');
        $bluesky->expects($this->once())->method('getQuotes')->willReturn([]);

        $bluesky->fetchResponseByType('https://bsky.app/profile/alice/post/123', [], 'mention-of');
    }

    /**
     * @group bluesky
     * @testdox fetchResponseByType - calls getReplies for in-reply-to
     */
    public function testFetchResponseByTypeCallsGetReplies()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl', 'getReplies'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('at://did:plc:abc/app.bsky.feed.post/123');
        $bluesky->expects($this->once())->method('getReplies')->willReturn([]);

        $bluesky->fetchResponseByType('https://bsky.app/profile/alice/post/123', [], 'in-reply-to');
    }

    /**
     * @group bluesky
     * @testdox fetchResponseByType - returns empty for unknown type
     */
    public function testFetchResponseByTypeReturnsEmptyForUnknownType()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getDidFromUrl'])
            ->getMock();

        $bluesky->method('getDidFromUrl')->willReturn('at://did:plc:abc/app.bsky.feed.post/123');

        $result = $bluesky->fetchResponseByType('https://bsky.app/profile/alice/post/123', [], 'unknown');

        $this->assertEquals([], $result);
    }

    // -------------------------
    // getLikes
    // -------------------------

    /**
     * @group bluesky
     * @testdox getLikes - returns empty array when no likes
     */
    public function testGetLikesReturnsEmptyWhenNoLikes()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $bluesky->method('getResponses')->willReturn([]);

        $result = $bluesky->getLikes('at://did:plc:abc/app.bsky.feed.post/123', [], 'https://bsky.app/profile/alice/post/123');

        $this->assertEquals([], $result);
    }

    /**
     * @group bluesky
     * @testdox getLikes - returns formatted like data
     */
    public function testGetLikesReturnsFormattedData()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $actor = new \stdClass();
        $actor->did = 'did:plc:alice';
        $actor->displayName = 'Alice';
        $actor->handle = 'alice.bsky.social';
        $actor->avatar = 'avatar.png';

        $like = new \stdClass();
        $like->indieConnectorId = 'like1';
        $like->createdAt = '2025-01-01T00:00:00Z';
        $like->actor = $actor;

        $bluesky->method('getResponses')->willReturn([$like]);

        $result = $bluesky->getLikes('at://did:plc:abc/app.bsky.feed.post/123', [], 'https://bsky.app/profile/alice/post/123');

        $this->assertEquals('like1', $result['latestId']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals([
            'postUrl' => 'https://bsky.app/profile/alice/post/123',
            'responseId' => 'like1',
            'responseType' => 'like-of',
            'responseSource' => 'bluesky',
            'responseDate' => '2025-01-01T00:00:00Z',
            'responseText' => '',
            'responseUrl' => 'https://bsky.app/profile/alice/post/123',
            'authorId' => 'did:plc:alice',
            'authorName' => 'Alice',
            'authorUsername' => 'alice.bsky.social',
            'authorAvatar' => 'avatar.png',
            'authorUrl' => 'https://bsky.app/profile/alice.bsky.social',
        ], $result['data'][0]);
    }

    /**
     * @group bluesky
     * @testdox getLikes - uses handle as name when displayName is empty
     */
    public function testGetLikesUsesHandleWhenDisplayNameEmpty()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $actor = new \stdClass();
        $actor->did = 'did:plc:alice';
        $actor->displayName = '';
        $actor->handle = 'alice.bsky.social';

        $like = new \stdClass();
        $like->indieConnectorId = 'like1';
        $like->createdAt = '2025-01-01T00:00:00Z';
        $like->actor = $actor;

        $bluesky->method('getResponses')->willReturn([$like]);

        $result = $bluesky->getLikes('at://did:plc:abc/app.bsky.feed.post/123', [], 'https://bsky.app/profile/alice/post/123');

        $this->assertEquals('alice.bsky.social', $result['data'][0]['authorName']);
    }

    /**
     * @group bluesky
     * @testdox getLikes - skips known likes
     */
    public function testGetLikesSkipsKnownLikes()
    {
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock])
            ->onlyMethods(['getResponses'])
            ->getMock();

        $actor = new \stdClass();
        $actor->did = 'did:plc:alice';
        $actor->displayName = 'Alice';
        $actor->handle = 'alice.bsky.social';

        $like1 = new \stdClass();
        $like1->indieConnectorId = 'like1';
        $like1->createdAt = '2025-01-01T00:00:00Z';
        $like1->actor = $actor;

        $like2 = new \stdClass();
        $like2->indieConnectorId = 'like2';
        $like2->createdAt = '2025-01-01T00:00:00Z';
        $like2->actor = $actor;

        $bluesky->method('getResponses')->willReturn([$like1, $like2]);

        $result = $bluesky->getLikes('at://did:plc:abc/app.bsky.feed.post/123', ['like2'], 'https://bsky.app/profile/alice/post/123');

        $this->assertCount(1, $result['data']);
        $this->assertEquals('like1', $result['data'][0]['responseId']);
    }

    // -------------------------
    // getLinks
    // -------------------------

    /**
     * @group bluesky
     * @testdox getLinks - extracts urls from message
     */
    public function testGetLinksExtractsUrls()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getLinks('Check out https://example.com for more info');

        $this->assertCount(1, $result);
        $this->assertEquals('https://example.com', $result[0]['features'][0]['uri']);
        $this->assertEquals('app.bsky.richtext.facet#link', $result[0]['features'][0]['$type']);
    }

    /**
     * @group bluesky
     * @testdox getLinks - returns empty array when no urls
     */
    public function testGetLinksReturnsEmptyWhenNoUrls()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getLinks('No links here');

        $this->assertEquals([], $result);
    }

    // -------------------------
    // getHashtags
    // -------------------------

    /**
     * @group bluesky
     * @testdox getHashtags - extracts hashtags from message
     */
    public function testGetHashtagsExtractsHashtags()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getHashtags('Hello #world and #php');

        $this->assertCount(2, $result);
        $this->assertEquals('world', $result[0]['features'][0]['tag']);
        $this->assertEquals('php', $result[1]['features'][0]['tag']);
    }

    /**
     * @group bluesky
     * @testdox getHashtags - returns empty array when no hashtags
     */
    public function testGetHashtagsReturnsEmptyWhenNoHashtags()
    {
        $bluesky = new Bluesky(true, 'handle', 'password', 0, $this->outboxMock, $this->bskClientMock, $this->externalPostSenderMock);

        $result = $bluesky->getHashtags('No hashtags here');

        $this->assertEquals([], $result);
    }
}
