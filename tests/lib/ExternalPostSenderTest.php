<?php

use mauricerenck\IndieConnector\ExternalPostSender;
use mauricerenck\IndieConnector\TestCaseMocked;

final class ExternalPostSenderTest extends TestCaseMocked
{
    private $sender;

    public function setUp(): void
    {
        parent::setUp();

        $this->sender = $this->getMockBuilder(ExternalPostSender::class)
            ->onlyMethods(['calculatePostTextLength'])
            ->getMock();
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - trim message with url and tags
     */
    public function testTrimsMessageWithUrlAndTags()
    {
        $message = str_repeat('A', 1000);
        $url = 'https://example.com';
        $tags = '#tag1 #tag2';

        $result = $this->sender->getTrimmedFullMessage(message: $message, url: $url, tags: $tags, service: 'mastodon');

        $this->assertStringContainsString($url, $result);
        $this->assertStringContainsString($tags, $result);
        $this->assertEquals(500, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - trim message with url only
     */
    public function testTrimsMessageWithUrlOnly()
    {
        $message = str_repeat('A', 1000);
        $url = 'https://example.com';
        $tags = '';

        $result = $this->sender->getTrimmedFullMessage(message: $message, url: $url, tags: $tags, service: 'mastodon');

        $this->assertStringContainsString($url, $result);
        $this->assertEquals(500, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should not trim at all
     */
    public function testShouldNotTrimMessage()
    {
        $message = str_repeat('A', 200);
        $url = 'https://example.com'; // 19 chars
        $tags = '#tag1 #tag2'; // 12 chars

        $result = $this->sender->getTrimmedFullMessage(message: $message, url: $url, tags: $tags, service: 'mastodon');

        $this->assertStringContainsString($url, $result);
        $this->assertEquals(233, strlen($result)); // chars plus line breaks
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should have exact length (mastodon)
     */
    public function testShouldHaveExactLength()
    {
        $message = str_repeat('A', 500);
        $url = '';
        $tags = '';

        $result = $this->sender->getTrimmedFullMessage(message: $message, url: $url, tags: $tags, service: 'mastodon');
        $this->assertEquals(500, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should have exact length (bluesky)
     */
    public function testShouldHaveExactLengthForBluesky()
    {
        $message = str_repeat('A', 500);
        $url = '';
        $tags = '';

        $result = $this->sender->getTrimmedFullMessage(message: $message, url: $url, tags: $tags, service: 'bluesky');
        $this->assertEquals(300, strlen($result));
    }
}
