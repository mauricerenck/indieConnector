<?php

use mauricerenck\IndieConnector\Sender;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;

final class senderTest extends TestCase
{
    public function testPageHasNeededStatus()
    {
        $page = page('phpunit');

        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($page);

        $this->assertTrue($result);
    }

    public function testStopsOnWrongNeededStatus()
    {
        $page = page('phpunit');
        kirby()->impersonate('kirby');
        $unpublishedPage = $page->duplicate('phpunit-unpublished');
        $unpublishedPage->unpublish();

        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($unpublishedPage);

        $this->assertFalse($result);

        kirby()->impersonate('kirby');
        $unpublishedPage->delete();
    }

    public function testPageHasAllowedTemplate()
    {
        $page = page('phpunit');

        $senderUtils = new Sender();
        $result = $senderUtils->templateIsAllowed('phpunit');

        $this->assertTrue($result);
    }

    public function testPageHasNotAllowedTemplate()
    {
        $page = page('phpunit');

        $senderUtils = new Sender();
        $result = $senderUtils->templateIsAllowed('nope');

        $this->assertFalse($result);
    }

    public function testPageHasBlockedTemplate()
    {
        $page = page('phpunit');

        $senderUtils = new Sender();
        $result = $senderUtils->templateIsBlocked('blocked-template');

        $this->assertTrue($result);
    }

    public function testPageHasNotBlockedTemplate()
    {
        $page = page('phpunit');

        $senderUtils = new Sender();
        $result = $senderUtils->templateIsBlocked('phpunit');

        $this->assertFalse($result);
    }

    public function testShouldFindUrls()
    {
        $page = page('phpunit');

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
        $page = page('phpunit');

        $senderUtils = new Sender();
        $urls = $senderUtils->getProcessedUrls($page);

        $this->assertCount(1, $urls);
        $this->assertEquals(['https://processed-url.tld'], $urls);
    }

    public function testShouldCleanupUrls()
    {
        $page = page('phpunit');

        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld'
        ];

        $senderUtils = new Sender();
        $urls = $senderUtils->cleanupUrls($sampleUrls, $page);

        $this->assertCount(1, $urls);
        $this->assertContains($sampleUrls[0], $urls);
        $this->assertNotContains($sampleUrls[1], $urls);
    }

    public function testShouldStoreProcessedUrls()
    {
        $page = page('phpunit');

        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld'
        ];

        $senderUtils = new Sender();
        $result = $senderUtils->storeProcessedUrls($sampleUrls, $page);
        $this->assertTrue($result);

        // restore original state of file
        $outboxFilePath = $page->root() . '/' . option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json');
        Data::write($outboxFilePath, ['https://processed-url.tld']);
    }
}
