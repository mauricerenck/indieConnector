<?php

use mauricerenck\IndieConnector\Bluesky;
use mauricerenck\IndieConnector\ExternalPostSender;
use mauricerenck\IndieConnector\Outbox;
use mauricerenck\IndieConnector\TestCaseMocked;

final class BlueskyTest extends TestCaseMocked
{
    private $bluesky;

    public function setUp(): void
    {
        parent::setUp();

        $mockOutbox = $this->getMockBuilder(Outbox::class)
            ->getMock();

        $externalPostSenderMock = $this->getMockBuilder(ExternalPostSender::class)
            ->getMock();

        $mockBlueskyApi = $this->getMockBuilder(\cjrasmussen\BlueskyApi\BlueskyApi::class)
            ->onlyMethods(['auth', 'request'])
            ->getMock();

        $this->bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([true, 'handle', 'password', null, $mockOutbox, $mockBlueskyApi, $externalPostSenderMock])
            ->onlyMethods(['paginateResponses', 'responsesIncludeKnownId'])
            ->getMock();
    }

    /**
     * @group bluesky
     * @testdox getUrlFromDid - return http url from did
     */
    public function testGetUrlFromDid()
    {
        $result = $this->bluesky->getUrlFromDid('at://did:plc:6abz5pqslrrnyawq6i53t7ui/app.bsky.feed.post/3mccdhzgpwd25');
        $this->assertSame('https://bsky.app/profile/mauricerenck.de/post/3mccdhzgpwd25', $result);
    }

    /**
     * @group bluesky
     * @testdox getDidFromUrl - return did from url
     */
    public function testGetDidFromUrl()
    {
        $result = $this->bluesky->getDidFromUrl('https://bsky.app/profile/mauricerenck.de/post/3mccdhzgpwd25');
        $this->assertSame('at://did:plc:6abz5pqslrrnyawq6i53t7ui/app.bsky.feed.post/3mccdhzgpwd25', $result);
    }

    /**
     * @group bluesky
     * @testdox get responses - return responses when known id in first page
     */
    public function testGetResponsesReturnsWhenKnownIdInFirstPage()
    {
        $this->bluesky->method('paginateResponses')->willReturn(['data' => ['a', 'b'], 'next' => null]);
        $this->bluesky->method('responsesIncludeKnownId')->willReturn(true);

        $result = $this->bluesky->getResponses('did', 'like-of', ['a']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group bluesky
     * @testdox get responses - return responses when known id in second page
     */
    public function testReturnsWhenKnownIdInSecondPage()
    {
        $this->bluesky->method('paginateResponses')
            ->willReturnOnConsecutiveCalls(
                ['data' => ['a'], 'next' => 'token'],
                ['data' => ['b'], 'next' => null]
            );

        $callCount = 0;
        $this->bluesky->method('responsesIncludeKnownId')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                return $callCount === 2; // false on first, true on second
            });

        $result = $this->bluesky->getResponses('did', 'like-of', ['b']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group bluesky
     * @testdox get responses - handle errors
     */
    public function testThrowsException()
    {
        $this->bluesky->method('paginateResponses')->will($this->throwException(new Exception('fail')));
        $this->expectException(Exception::class);
        $this->bluesky->getResponses('did', 'likes', []);
    }

    /**
     * @group bluesky
     * @testdox known Ids - return true when match exists
     */
    // public function testResponsesIncludeKnownIdReturnsTrueWhenMatchExists()
    // {
    //     $bsk = new Bluesky();

    //     $responses = [
    //         (object)['indieConnectorId' => 'id1'],
    //         (object)['indieConnectorId' => 'id2'],
    //     ];
    //     $knownIds = ['id2', 'id3'];

    //     $this->assertTrue($bsk->responsesIncludeKnownId($responses, $knownIds));
    // }

    /**
     * @group bluesky
     * @testdox known Ids - return false when no match
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenNoMatch()
    {

        $responses = [
            (object)['indieConnectorId' => 'id1'],
            (object)['indieConnectorId' => 'id2'],
        ];
        $knownIds = ['id3', 'id4'];

        $this->assertFalse($this->bluesky->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group bluesky
     * @testdox known Ids - return false when empty responses
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenResponsesEmpty()
    {
        $responses = [];
        $knownIds = ['id1'];

        $this->assertFalse($this->bluesky->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group bluesky
     * @testdox known Ids - return false when no known ids
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenKnownIdsEmpty()
    {
        $responses = [
            (object)['indieConnectorId' => 'id1'],
        ];
        $knownIds = [];

        $this->assertFalse($this->bluesky->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group bluesky
     * @testdox set id - set id for likes
     */
    public function testAppendIndieConnectorIdLikes()
    {
        $response = (object)[
            'actor' => (object)['did' => 'did:example:123'],
            'createdAt' => '2024-01-01T00:00:00Z'
        ];
        $result = $this->bluesky->appendIndieConnectorId([$response], 'like-of');
        $expectedId = md5('did:example:1232024-01-01T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox set id - set id for reposts
     */
    public function testAppendIndieConnectorIdReposts()
    {
        $response = (object)[
            'did' => 'did:example:456',
            'createdAt' => '2024-01-02T00:00:00Z'
        ];
        $result = $this->bluesky->appendIndieConnectorId([$response], 'repost-of');
        $expectedId = md5('did:example:4562024-01-02T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox set id - set id for quotes
     */
    public function testAppendIndieConnectorIdQuotes()
    {
        $response = (object)[
            'cid' => 'cid-789'
        ];
        $result = $this->bluesky->appendIndieConnectorId([$response], 'mention-of');
        $this->assertEquals('cid-789', $result[0]->indieConnectorId);
    }

    /**
     * @group bluesky
     * @testdox set id - set id for replies
     */
    public function testAppendIndieConnectorIdReplies()
    {
        $response = (object)[
            'post' => (object)['cid' => 'cid-101112']
        ];
        $result = $this->bluesky->appendIndieConnectorId([$response], 'in-reply-to');
        $this->assertEquals('cid-101112', $result[0]->indieConnectorId);
    }
}
