<?php

use mauricerenck\IndieConnector\MastodonReceiver;
use mauricerenck\IndieConnector\TestCaseMocked;

final class MastodonReceiverTest extends TestCaseMocked
{
    private $receiver;

    public function setUp(): void
    {
        parent::setUp();

        $this->receiver = $this->getMockBuilder(MastodonReceiver::class)
            ->setConstructorArgs([true])
            ->onlyMethods(['getPostUrlData', 'paginateResponses', 'responsesIncludeKnownId'])
            ->getMock();
    }

    /**
     * @group mastodonReceiver
     * @testdox pagination - should get next page url
     */
    public function testShouldGetNextPageUrl()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->extractMastodonNextPageUrl('<https://example.com/api/v1/statuses/1234567890/favourited_by?max_id=987654321>; rel="next", <https://example.com/api/v1/statuses/1234567890/favourited_by?since_id=987654321>; rel="prev"');

        $this->assertEquals('https://example.com/api/v1/statuses/1234567890/favourited_by?max_id=987654321', $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox pagination - should not get next page url
     */
    public function testShouldNotGetNextPageUrl()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->extractMastodonNextPageUrl('; rel="next", <https://example.com/api/v1/statuses/1234567890/favourited_by?since_id=987654321>; rel="prev"');

        $this->assertNull($result);
    }

    /**
     * @group mastodonReceiver
     * @testdox response - should get post url data
     */
    public function testShouldGetPostUrlData()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->getPostUrlData('https://example.com/@username/1234567890');

        $this->assertEquals([
            'example.com',
            '1234567890'
        ], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox response - should handle invalid url
     */
    public function testGetPostUrlDataHandleInvalidUrl()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->getPostUrlData('example-com');

        $this->assertEquals([
            null,
            null
        ], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox response - should handle invalid url structure
     */
    public function testGetPostUrlDataHandleInvalidUrlStructure()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->getPostUrlData('https://example.com/');

        $this->assertEquals([
            'example.com',
            null
        ], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox known Ids - should find known Ids
     */
    public function testReponsesIncludeKnownIds()
    {
        $reponses = [
            [
                'id' => '1234567890',
                'username' => 'php unit'
            ],
            [
                'id' => '0987654321',
                'username' => 'php unit'
            ],
        ];

        $receiver = new MastodonReceiver(true);
        $result1 = $receiver->responsesIncludeKnownId($reponses, ['1234567890']);
        $result2 = $receiver->responsesIncludeKnownId($reponses, ['0987654321']);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * @group mastodonReceiver
     * @testdox known Ids - should handle empty known Ids
     */
    public function testReponsesIncludeKnownIdsEmptyKnownIds()
    {
        $reponses = [
            [
                'id' => '1234567890',
                'username' => 'php unit'
            ],
            [
                'id' => '0987654321',
                'username' => 'php unit'
            ],
        ];

        $receiver = new MastodonReceiver(true);
        $result = $receiver->responsesIncludeKnownId($reponses, []);

        $this->assertFalse($result);
    }

    /**
     * @group mastodonReceiver
     * @testdox known Ids - should handle no known Ids
     */
    public function testReponsesIncludeKnownIdsNoKnownIds()
    {
        $reponses = [
            [
                'id' => '1234567890',
                'username' => 'php unit'
            ],
            [
                'id' => '0987654321',
                'username' => 'php unit'
            ],
        ];

        $receiver = new MastodonReceiver(true);
        $result = $receiver->responsesIncludeKnownId($reponses, ['765123098']);

        $this->assertFalse($result);
    }

    /**
     * @group mastodonReceiver
     * @testdox known Ids - should handle empty responses
     */
    public function testReponsesIncludeKnownIdsEmptyResponses()
    {
        $receiver = new MastodonReceiver(true);
        $result = $receiver->responsesIncludeKnownId([], ['765123098']);

        $this->assertFalse($result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - stop if disabled
     */
    public function testGetResponsesIfDisabled()
    {
        $receiver = new MastodonReceiver(false);
        $result = $receiver->getResponses('https://example.com/@username/1234567890', 'likes', []);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - return responses when known id in first page
     */
    public function testReturnsWhenKnownIdInFirstPage()
    {
        $this->receiver->method('getPostUrlData')->willReturn(['host', 'id']);
        $this->receiver->method('paginateResponses')->willReturn(['data' => ['a', 'b'], 'next' => null]);
        $this->receiver->method('responsesIncludeKnownId')->willReturn(true);

        $result = $this->receiver->getResponses('url', 'type', ['a']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - return responses when known id in second page
     */
    public function testReturnsWhenKnownIdInSecondPage()
    {
        $this->receiver->method('getPostUrlData')->willReturn(['host', 'id']);

        $paginateResponsesCalls = 0;
        $this->receiver->method('paginateResponses')
            ->willReturnCallback(function ($host, $id, $type, $next) use (&$paginateResponsesCalls) {
                $paginateResponsesCalls++;
                if ($paginateResponsesCalls === 1) {
                    return ['data' => ['a'], 'next' => 'token'];
                } else {
                    return ['data' => ['b'], 'next' => null];
                }
            });

        $responsesIncludeKnownIdCalls = 0;
        $this->receiver->method('responsesIncludeKnownId')
            ->willReturnCallback(function () use (&$responsesIncludeKnownIdCalls) {
                $responsesIncludeKnownIdCalls++;
                return $responsesIncludeKnownIdCalls === 2; // false, then true
            });

        $result = $this->receiver->getResponses('url', 'type', ['b']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - return [] when no mastodon hostname
     */
    public function testGetResponsesStopWithoutUrlHost()
    {
        $this->receiver->method('getPostUrlData')->willReturn([null, '123467890']);
        $result = $this->receiver->getResponses('url', 'type', ['a']);
        $this->assertSame([], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - return [] when no mastodon postId
     */
    public function testGetResponsesStopWithoutPostid()
    {
        $this->receiver->method('getPostUrlData')->willReturn(['example.com', null]);
        $result = $this->receiver->getResponses('url', 'type', ['a']);
        $this->assertSame([], $result);
    }

    /**
     * @group mastodonReceiver
     * @testdox get responses - handle errors
     */
    public function testGetResponsesThrowsException()
    {
        $this->receiver->method('getPostUrlData')->will($this->throwException(new Exception('fail')));
        $this->expectException(Exception::class);
        $this->receiver->getResponses('url', 'type', []);
    }
}
