<?php

use mauricerenck\IndieConnector\Bluesky;
use mauricerenck\IndieConnector\Sender;
use mauricerenck\IndieConnector\TestCaseMocked;

final class BlueskyTest extends TestCaseMocked
{
    private $bluesky;

    public function setUp(): void
    {
        parent::setUp();

        $this->bluesky = new Bluesky();
    }

    /**
     * @group bluesky
     * @testdox getBlueskyUrl - return http url from did
     */
    public function testGetBlueskyUrl()
    {
        // Arrange
        $expectedUrl = 'https://bsky.app/profile/mauricerenck.de/post/3mccdhzgpwd25';
        $expectedAt = 'at://did:plc:6abz5pqslrrnyawq6i53t7ui/app.bsky.feed.post/3mccdhzgpwd25';

        // Mock the Sender class
        $senderMock = $this->createMock(Sender::class);
        $senderMock->method('getPostTargetUrl')
            ->with('bluesky', $this->anything())
            ->willReturn($expectedUrl);

        // Partial mock Bluesky to inject the mocked Sender
        $bluesky = $this->getMockBuilder(Bluesky::class)
            ->setConstructorArgs([$senderMock])
            ->onlyMethods(['postExists'])
            ->getMock();

        // Act
        $result = $bluesky->getBlueskyUrl('dummyPage');

        // Assert
        $this->assertEquals([
            'at' => $expectedAt,
            'http' => $expectedUrl
        ], $result);
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
}
