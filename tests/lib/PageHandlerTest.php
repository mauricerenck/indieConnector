<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\PageHandler;

use Kirby\Cms\Page;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class PageHandlerTest extends TestCase
{
    private static OptionHandlerMock $optionHandlerMock;
    private OptionHandlerMock $optionHandler;
    private PageHandler $pageHandler;

    public static function setUpBeforeClass(): void
    {
        self::$optionHandlerMock = new OptionHandlerMock([]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->optionHandler = self::$optionHandlerMock;
        $this->optionHandler->reset();
        $this->pageHandler = new PageHandler(options: $this->optionHandler);
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsBlocked - returns true when template is blocked for webmention')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsBlocked_WebmentionIsBlocked()
    {
        $this->optionHandler->set('send.blockedTemplates', ['template1', 'template2']);
        $this->assertTrue($this->pageHandler->templateIsBlocked('template1'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsBlocked - returns false when template is not blocked for webmention')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsBlocked_WebmentionIsNotBlocked()
    {
        $this->optionHandler->set('send.blockedTemplates', ['template1']);
        $this->assertFalse($this->pageHandler->templateIsBlocked('template_not_blocked'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsBlocked - returns true when template is blocked for post')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsBlocked_PostIsBlocked()
    {
        $this->optionHandler->set('post.blockedTemplates', ['templateA', 'templateB']);
        $this->assertTrue($this->pageHandler->templateIsBlocked('templateA', 'post'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsBlocked - returns false when template is not blocked for post')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsBlocked_PostIsNotBlocked()
    {
        $this->optionHandler->set('post.blockedTemplates', ['templateA']);
        $this->assertFalse($this->pageHandler->templateIsBlocked('template_not_blocked', 'post'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsBlocked - returns false when blocked templates list is empty')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsBlocked_EmptyOptions()
    {
        // Options are initialized with empty arrays, so this should pass
        $this->assertFalse($this->pageHandler->templateIsBlocked('any_template'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsAllowed - returns true when template is allowed for webmention')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsAllowed_WebmentionIsAllowed()
    {
        $this->optionHandler->set('send.allowedTemplates', ['templateX', 'templateY']);
        $this->assertTrue($this->pageHandler->templateIsAllowed('templateX'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsAllowed - returns false when template is not allowed for webmention')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsAllowed_WebmentionIsNotAllowed()
    {
        $this->optionHandler->set('send.allowedTemplates', ['templateX']);
        $this->assertFalse($this->pageHandler->templateIsAllowed('template_not_allowed'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsAllowed - returns true when template is allowed for post')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsAllowed_PostIsAllowed()
    {
        $this->optionHandler->set('post.allowedTemplates', ['templateA', 'templateB']);
        $this->assertTrue($this->pageHandler->templateIsAllowed('templateA', 'post'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsAllowed - returns false when template is not allowed for post')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsAllowed_PostIsNotAllowed()
    {
        $this->optionHandler->set('post.allowedTemplates', ['templateA']);
        $this->assertFalse($this->pageHandler->templateIsAllowed('template_not_allowed', 'post'));
    }

    #[Group('pageHandler')]
    #[TestDox('templateIsAllowed - returns false when allowed templates list is empty')]
    #[AllowMockObjectsWithoutExpectations]
    public function testTemplateIsAllowed_EmptyOptions()
    {
        // Options are initialized with empty arrays, so this should pass
        $this->assertFalse($this->pageHandler->templateIsAllowed('any_template'));
    }

    #[Group('pageHandler')]
    #[TestDox('pageHasNeededStatus - returns true when the page is not a draft')]
    #[AllowMockObjectsWithoutExpectations]
    public function testPageHasNeededStatus_IsNotDraft()
    {
        // Mock the Kirby\Cms\Page object
        $mockPage = $this->createMock(Page::class);
        $mockPage->expects($this->once())
            ->method('isDraft')
            ->willReturn(false);

        $this->assertTrue($this->pageHandler->pageHasNeededStatus($mockPage));
    }

    #[Group('pageHandler')]
    #[TestDox('pageHasNeededStatus - returns false when the page is a draft')]
    #[AllowMockObjectsWithoutExpectations]
    public function testPageHasNeededStatus_IsDraft()
    {
        // Mock the Kirby\Cms\Page object
        $mockPage = $this->createMock(Page::class);
        $mockPage->expects($this->once())
            ->method('isDraft')
            ->willReturn(true);

        $this->assertFalse($this->pageHandler->pageHasNeededStatus($mockPage));
    }

    #[Group('pageHandler')]
    #[TestDox('findLinks - find links in html string')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFindLinks()
    {
        $htmlString = <<<HTML
        <p>
            Hello <strong><a href="https://example.com">World!</a></strong>
        </p>
        <p>
            I hope, you are <a href="https://example.com/okay">okay</a>,
            let me know, if you are not <a href="https://example.com/okay">okay</a>
        </p>
        HTML;

        $result = $this->pageHandler->findLinks($htmlString);

        $this->assertCount(2, $result);
        $this->assertContains('https://example.com', $result);
        $this->assertContains('https://example.com/okay', $result);
    }

    #[Group('pageHandler')]
    #[TestDox('findLinks - handle trailing slashed')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFindLinksTrailingSlash()
    {
        $htmlString = <<<HTML
        <p>
            I hope, you are <a href="https://example.com/okay/">okay</a>,
            let me know, if you are not <a href="https://example.com/okay">okay</a>
        </p>
        HTML;

        $result = $this->pageHandler->findLinks($htmlString);

        $this->assertCount(1, $result);
        $this->assertContains('https://example.com/okay', $result);
    }

    #[Group('pageHandler')]
    #[TestDox('findLinks - handle broken link')]
    #[AllowMockObjectsWithoutExpectations]
    public function testFindLinksBrokenLink()
    {
        $htmlString = <<<HTML
        <p>
            I hope, you are <a href="httppps://example.com/okay/">okay</a>,
        </p>
        HTML;

        $result = $this->pageHandler->findLinks($htmlString);

        $this->assertCount(0, $result);
    }
}
