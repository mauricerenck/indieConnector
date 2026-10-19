<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\WebmentionHandler;

use Kirby\Cms\Page;
use mauricerenck\IndieConnector\PageHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class PageHandlerTest extends TestCase
{
    private static $defaultOptions;
    private $pageHandler;

    public static function setUpBeforeClass(): void
    {
        self::$defaultOptions = new OptionHandlerMock([]);
    }


    public function setUp(): void
    {
        parent::setUp();

        $this->pageHandler = new PageHandler(options: $this->defaultOptions);
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
