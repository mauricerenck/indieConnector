<?php

use mauricerenck\IndieConnector\MastodonSender;
use mauricerenck\IndieConnector\TestCaseMocked;

final class MastodonSenderTest extends TestCaseMocked
{
    private $urlCheckMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlCheckMock = Mockery::mock('mauricerenck\IndieConnector\UrlHandler')->makePartial();
    }

    /**
     * @group mastodonSender
     * @testdox post - should send message to Mastodon
     */
    public function testShouldSendPost()
    {
        $page = $this->getPageMock(false, ['description' => 'This is a test description']);
        $this->urlCheckMock->shouldReceive('isLocalUrl')->andReturn(false);

        $sender = new MastodonSender(
            'https://example.com',
            '1234567890',
            true,
            500,
            ['description'],
            null,
            $this->urlCheckMock
        );

        $result = $sender->sendPost($page);

        $this->assertEquals([
            "id" => null,
            "uri" => null,
            "status" => 200,
            "target" => "mastodon"
        ], $result);
    }

    /**
     * @group mastodonSender
     * @testdox post - should stop when disabled
     */
    public function testShouldHandleDisabled()
    {
        $page = $this->getPageMock(false, [
            'description' => 'This is a test description',
            'enableexternalposting' => false,
        ]);
        $this->urlCheckMock->shouldReceive('isLocalUrl')->andReturn(false);

        $sender = new MastodonSender(
            'https://example.com',
            '1234567890',
            true,
            500,
            ['description'],
            null,
            $this->urlCheckMock
        );

        $result = $sender->sendPost($page);

        $this->assertFalse($result);
    }

    /**
     * @group mastodonSender
     * @testdox post - should stop when draft
     */
    public function testShouldHandleDraft()
    {
        $page = $this->getPageMock(true, [
            'description' => 'This is a test description',
        ]);

        $this->urlCheckMock->shouldReceive('isLocalUrl')->andReturn(false);

        $sender = new MastodonSender(
            'https://example.com',
            '1234567890',
            true,
            500,
            ['description'],
            null,
            $this->urlCheckMock
        );

        $result = $sender->sendPost($page);

        $this->assertFalse($result);
    }

    /**
     * @group mastodonSender
     * @testdox post - should stop on local url
     */
    // FIXME: something's wrong with the tests here
    // public function testShouldHandleLocalUrl()
    // {
    //     $page = $this->getPageMock(true, [
    //         'description' => 'This is a test description',
    //     ]);

    //     $sender = new MastodonSender(500, 'description', null, 'https://example.com', '1234567890');

    //     $result = $sender->sendPost($page);

    //     $this->assertFalse($result);
    // }
}
