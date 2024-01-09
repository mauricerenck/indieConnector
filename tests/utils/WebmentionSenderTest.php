<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\WebmentionSender;

final class WebmentionSenderTest extends TestCaseMocked
{
    private $senderUtilsMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->senderUtilsMock = Mockery::mock('mauricerenck\IndieConnector\WebmentionSender[pageFullfillsCriteria,shouldSendWebmentionToTarget,send,storeProcessedUrls]');
    }


    /**
     * @group webmentions
     * @testdox sendWebmentions - should send to one url
     */
    public function testShouldSendWebmention()
    {
        $urls = ['https://text-field-url.tld'];


        $pageMock = $this->getPageMock(false, ['webmentionsStatus' => true]);
        $this->senderUtilsMock->shouldReceive('pageFullfillsCriteria')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('shouldSendWebmentionToTarget')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('send')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('storeProcessedUrls')->andReturn(true);

        $result = $this->senderUtilsMock->sendWebmentions($pageMock, $urls);
        $this->assertContains($urls[0], $result);
    }

    /**
     * @group webmentions
     * @testdox sendWebmentions - should not send wm when disabled on page
     */
    public function testShouldSendWebmentionSendNone()
    {
        $urls = ['https://processed-url.tld'];


        $pageMock = $this->getPageMock(false, ['webmentionsStatus' => false]);
        $this->senderUtilsMock->shouldReceive('pageFullfillsCriteria')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('shouldSendWebmentionToTarget')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('send')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('storeProcessedUrls')->andReturn(true);

        $result = $this->senderUtilsMock->sendWebmentions($pageMock, $urls);
        $this->assertFalse($result);
    }

    // public function testShouldNotSend()
    // {
    //     $sendWebmention = new WebmentionSender();
    //     $result = $sendWebmention->send('https://text-field-url.tld', site()->url());
    //     $this->assertFalse($result);
    // }
}
