<?php

use mauricerenck\IndieConnector\BlueskyReceiver;
use mauricerenck\IndieConnector\TestCaseMocked;

final class BlueskyReceiverTest extends TestCaseMocked
{
    private $receiver;

    public function setUp(): void
    {
        parent::setUp();

        $mockBlueskyApi = $this->getMockBuilder(\cjrasmussen\BlueskyApi\BlueskyApi::class)
            ->onlyMethods(['auth', 'request'])
            ->getMock();

        // Mock only the methods you want to control
        $this->receiver = $this->getMockBuilder(BlueskyReceiver::class)
            ->setConstructorArgs([true, 'handle', 'password', $mockBlueskyApi])
            ->onlyMethods(['paginateResponses', 'responsesIncludeKnownId'])
            ->getMock();
    }

    /**
     * @group blueskyReceiver
     * @testdox get responses - return responses when known id in first page
     */
    public function testGetResponsesReturnsWhenKnownIdInFirstPage()
    {
        $this->receiver->method('paginateResponses')->willReturn(['data' => ['a', 'b'], 'next' => null]);
        $this->receiver->method('responsesIncludeKnownId')->willReturn(true);

        $result = $this->receiver->getResponses('did', 'likes', ['a']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group blueskyReceiver
     * @testdox get responses - return responses when known id in second page
     */
    public function testReturnsWhenKnownIdInSecondPage()
    {
        $this->receiver->method('paginateResponses')
            ->willReturnOnConsecutiveCalls(
                ['data' => ['a'], 'next' => 'token'],
                ['data' => ['b'], 'next' => null]
            );

        $callCount = 0;
        $this->receiver->method('responsesIncludeKnownId')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                return $callCount === 2; // false on first, true on second
            });

        $result = $this->receiver->getResponses('did', 'likes', ['b']);
        $this->assertSame(['a', 'b'], $result);
    }

    /**
     * @group blueskyReceiver
     * @testdox get responses - handle errors
     */
    public function testThrowsException()
    {
        $this->receiver->method('paginateResponses')->will($this->throwException(new Exception('fail')));
        $this->expectException(Exception::class);
        $this->receiver->getResponses('did', 'likes', []);
    }

    /**
     * @group blueskyReceiver
     * @testdox known Ids - return true when match exists
     */
    public function testResponsesIncludeKnownIdReturnsTrueWhenMatchExists()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $responses = [
            (object)['indieConnectorId' => 'id1'],
            (object)['indieConnectorId' => 'id2'],
        ];
        $knownIds = ['id2', 'id3'];

        $this->assertTrue($receiver->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group blueskyReceiver
     * @testdox known Ids - return false when no match
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenNoMatch()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $responses = [
            (object)['indieConnectorId' => 'id1'],
            (object)['indieConnectorId' => 'id2'],
        ];
        $knownIds = ['id3', 'id4'];

        $this->assertFalse($receiver->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group blueskyReceiver
     * @testdox known Ids - return false when empty responses
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenResponsesEmpty()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $responses = [];
        $knownIds = ['id1'];

        $this->assertFalse($receiver->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group blueskyReceiver
     * @testdox known Ids - return false when no known ids
     */
    public function testResponsesIncludeKnownIdReturnsFalseWhenKnownIdsEmpty()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $responses = [
            (object)['indieConnectorId' => 'id1'],
        ];
        $knownIds = [];

        $this->assertFalse($receiver->responsesIncludeKnownId($responses, $knownIds));
    }

    /**
     * @group blueskyReceiver
     * @testdox set id - set id for likes
     */
    public function testAppendIndieConnectorIdLikes()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $response = (object)[
            'actor' => (object)['did' => 'did:example:123'],
            'createdAt' => '2024-01-01T00:00:00Z'
        ];
        $result = $receiver->appendIndieConnectorId([$response], 'likes');
        $expectedId = md5('did:example:1232024-01-01T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group blueskyReceiver
     * @testdox set id - set id for reposts
     */
    public function testAppendIndieConnectorIdReposts()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $response = (object)[
            'did' => 'did:example:456',
            'createdAt' => '2024-01-02T00:00:00Z'
        ];
        $result = $receiver->appendIndieConnectorId([$response], 'reposts');
        $expectedId = md5('did:example:4562024-01-02T00:00:00Z');
        $this->assertEquals($expectedId, $result[0]->indieConnectorId);
    }

    /**
     * @group blueskyReceiver
     * @testdox set id - set id for quotes
     */
    public function testAppendIndieConnectorIdQuotes()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $response = (object)[
            'cid' => 'cid-789'
        ];
        $result = $receiver->appendIndieConnectorId([$response], 'quotes');
        $this->assertEquals('cid-789', $result[0]->indieConnectorId);
    }

    /**
     * @group blueskyReceiver
     * @testdox set id - set id for replies
     */
    public function testAppendIndieConnectorIdReplies()
    {
        $receiver = new class extends \mauricerenck\IndieConnector\BlueskyReceiver {
            public function __construct() {}
        };

        $response = (object)[
            'post' => (object)['cid' => 'cid-101112']
        ];
        $result = $receiver->appendIndieConnectorId([$response], 'replies');
        $this->assertEquals('cid-101112', $result[0]->indieConnectorId);
    }
}
