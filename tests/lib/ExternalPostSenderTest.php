<?php

use mauricerenck\IndieConnector\ExternalPostSender;
use mauricerenck\IndieConnector\UrlHandler;
use mauricerenck\IndieConnector\PageChecks;
use mauricerenck\IndieConnector\TestCaseMocked;

final class ExternalPostSenderTest extends TestCaseMocked
{
    private $urlHandlerMock;
    private $pageChecksMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlHandlerMock = $this->createMock(UrlHandler::class);
        $this->pageChecksMock = $this->createMock(PageChecks::class);
    }

    private function createSender(array $options = []): ExternalPostSender
    {
        return $this->getMockBuilder(ExternalPostSender::class)
            ->setConstructorArgs([
                $options['textfields'] ?? ['description'],
                $options['imagefield'] ?? null,
                $options['imageAltField'] ?? 'alt',
                $options['tagsField'] ?? null,
                $options['prefereLanguage'] ?? null,
                $options['usePermalinkUrl'] ?? false,
                $options['skipUrl'] ?? false,
                $options['skipUrlTemplates'] ?? [],
                $options['maxPostLength'] ?? 300,
                $options['neverTrimTags'] ?? true,
                $this->urlHandlerMock,
                $this->pageChecksMock,
            ])
            ->onlyMethods(['getPostUrl', 'getPostTags', 'getTextFieldContent'])
            ->getMock();
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - trim message with url and tags
     */
    public function testTrimsMessageWithUrlAndTags()
    {
        $sender = $this->createSender();
        $sender->method('getPostUrl')->willReturn('https://example.com');
        $sender->method('getPostTags')->willReturn('#tag1 #tag2');
        $sender->method('getTextFieldContent')->willReturn(str_repeat('A', 1000));

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'mastodon');

        $this->assertStringContainsString('https://example.com', $result);
        $this->assertStringContainsString('#tag1 #tag2', $result);
        $this->assertLessThanOrEqual(300, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - trim message with url only
     */
    public function testTrimsMessageWithUrlOnly()
    {
        $sender = $this->createSender();
        $sender->method('getPostUrl')->willReturn('https://example.com');
        $sender->method('getPostTags')->willReturn('');
        $sender->method('getTextFieldContent')->willReturn(str_repeat('A', 1000));

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'mastodon');

        $this->assertStringContainsString('https://example.com', $result);
        $this->assertLessThanOrEqual(300, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should not trim short message
     */
    public function testShouldNotTrimMessage()
    {
        $sender = $this->createSender();
        $sender->method('getPostUrl')->willReturn('https://example.com');
        $sender->method('getPostTags')->willReturn('#tag1 #tag2');
        $sender->method('getTextFieldContent')->willReturn(str_repeat('A', 50));

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'mastodon');

        $this->assertStringContainsString('https://example.com', $result);
        $this->assertStringContainsString(str_repeat('A', 50), $result);
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should have exact length for mastodon
     */
    public function testShouldHaveExactLengthForMastodon()
    {
        $sender = $this->createSender(['maxPostLength' => 300]);
        $sender->method('getPostUrl')->willReturn('');
        $sender->method('getPostTags')->willReturn('');
        $sender->method('getTextFieldContent')->willReturn(str_repeat('A', 500));

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'mastodon');

        $this->assertLessThanOrEqual(300, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - should have exact length for bluesky
     */
    public function testShouldHaveExactLengthForBluesky()
    {
        $sender = $this->createSender();
        $sender->method('getPostUrl')->willReturn('');
        $sender->method('getPostTags')->willReturn('');
        $sender->method('getTextFieldContent')->willReturn(str_repeat('A', 500));

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'bluesky');

        $this->assertLessThanOrEqual(300, strlen($result));
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getTrimmedFullMessage - uses manual text message when provided
     */
    public function testUsesManualTextMessage()
    {
        $sender = $this->createSender();
        $sender->method('getPostUrl')->willReturn('');
        $sender->method('getPostTags')->willReturn('');

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getTrimmedFullMessage($pageMock, 'mastodon', 'My manual message');

        $this->assertStringContainsString('My manual message', $result);
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getPostTags - returns empty string when no tags field configured
     */
    public function testGetPostTagsReturnsEmptyWhenNoTagsField()
    {
        $sender = new ExternalPostSender(
            ['description'],
            null,
            'alt',
            null,
            null,
            false,
            false,
            [],
            300,
            true,
            $this->urlHandlerMock,
            $this->pageChecksMock
        );

        $fieldMock = $this->getMockBuilder(\Kirby\Content\Field::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fieldMock->method('isEmpty')->willReturn(true);

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $pageMock->method('__call')->willReturn($fieldMock);

        $result = $sender->getPostTags($pageMock);

        $this->assertEquals('', $result);
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox getPostUrl - returns empty string when skipUrl is true
     */
    public function testGetPostUrlReturnsEmptyWhenSkipUrl()
    {
        $sender = new ExternalPostSender(
            null,
            null,
            null,
            null,
            null,
            false,
            true,
            [],
            null,
            null,
            $this->urlHandlerMock,
            $this->pageChecksMock
        );

        $pageMock = $this->createMock(\Kirby\Cms\Page::class);
        $result = $sender->getPostUrl($pageMock);

        $this->assertEquals('', $result);
    }

    /**
     * @group ExternalPostSenderTest
     * @testdox calculatePostTextLength - returns correct length
     */
    public function testCalculatePostTextLengthReturnsCorrectLength()
    {
        $sender = new ExternalPostSender(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            300,
            null,
            $this->urlHandlerMock,
            $this->pageChecksMock
        );

        $result = $sender->calculatePostTextLength('https://example.com'); // 19 chars

        $this->assertEquals(279, $result); // 300 - 19 - 2
    }
}
