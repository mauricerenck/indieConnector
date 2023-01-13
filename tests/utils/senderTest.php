<?php

use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Data\Data;
use mauricerenck\IndieConnector\Sender;
use mauricerenck\IndieConnector\TestCaseMocked;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;


final class senderTest extends TestCaseMocked
{

    public function testPageHasNeededStatus()
    {
        $page = $this->getPageMock();
        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($page);

        $this->assertTrue($result);
    }

    public function testStopsOnWrongNeededStatus()
    {
        $page = $this->getPageMock(true);

        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($page);

        $this->assertFalse($result);
    }

    public function testPageHasAllowedTemplate()
    {
        $senderUtils = new Sender();
        $result = $senderUtils->templateIsAllowed('phpunit');

        $this->assertTrue($result);
    }

    public function testPageHasNotAllowedTemplate()
    {
        $senderUtils = new Sender();
        $result = $senderUtils->templateIsAllowed('nope');

        $this->assertFalse($result);
    }

    public function testPageHasBlockedTemplate()
    {
        $senderUtils = new Sender();
        $result = $senderUtils->templateIsBlocked('blocked-template');

        $this->assertTrue($result);
    }

    public function testPageHasNotBlockedTemplate()
    {
        $senderUtils = new Sender();
        $result = $senderUtils->templateIsBlocked('phpunit');

        $this->assertFalse($result);
    }

    public function testPageDisabledWebmentions()
    {
        $page = $this->getPageMock(false, ['webmentionsstatus' => false]);

        $senderUtils = new Sender();
        $result = $senderUtils->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    public function testPageEnabledWebmentions()
    {
        $page = $this->getPageMock(false, ['webmentionsstatus' => true]);

        $senderUtils = new Sender();
        $result = $senderUtils->pageFullfillsCriteria($page);

        $this->assertTrue($result);
    }

    public function testPageNotSetWebmentions()
    {
        $page = $this->getPageMock();

        $senderUtils = new Sender();
        $result = $senderUtils->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    public function testShouldFindUrls()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://text-field-url.tld',
            'https://www.text-field-url.tld',
            'http://www.text-field-url.tld',
            'https://text-field-url.tld/a-linked-text',
            'www.block-test-url.tld',
            'www.block-url.tld'
        ];

        $senderUtils = new Sender();
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
        $this->assertContains($expectedUrls[2], $urls);
        $this->assertContains($expectedUrls[3], $urls);
    }

    public function testShouldReturnProcessedUrls()
    {
        $page = $this->getPageMock();

        $fileMock = File::factory([
            'parent' => $page,
            'filename' => 'indieConnector.json',
            'content' => ['["https://processed-url.tld"]']
        ]);

        $senderUtilsMock = Mockery::mock('mauricerenck\IndieConnector\Sender')->makePartial();
        $senderUtilsMock->shouldReceive('readOutbox')->andReturn($fileMock);

        $urls = $senderUtilsMock->getProcessedUrls($page);

        $this->assertCount(1, $urls);
        $this->assertEquals(['https://processed-url.tld'], $urls);
    }

    public function testShouldCleanupUrls()
    {
        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld'
        ];

        $senderUtils = new Sender();
        $processedUrls = ["https://processed-url.tld"];
        $urls = $senderUtils->cleanupUrls($sampleUrls, $processedUrls);

        $this->assertCount(1, $urls);
        $this->assertContains($sampleUrls[0], $urls);
        $this->assertNotContains($sampleUrls[1], $urls);
    }

    public function testShouldStoreProcessedUrls()
    {
        $page = $this->getPageMock();

        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld'
        ];

        $senderUtilsMock = Mockery::mock('mauricerenck\IndieConnector\Sender')->makePartial();
        $senderUtilsMock->shouldReceive('writeOutbox')->andReturn(true);

        $result = $senderUtilsMock->storeProcessedUrls($sampleUrls, $sampleUrls, $page);
        $this->assertTrue($result);
    }
}
