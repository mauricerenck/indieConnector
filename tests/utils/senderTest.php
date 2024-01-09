<?php
use Kirby\Cms\File;
use Kirby\Cms\S;
use mauricerenck\IndieConnector\Sender;
use mauricerenck\IndieConnector\TestCaseMocked;

final class senderTest extends TestCaseMocked
{
    private $senderUtilsMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->senderUtilsMock = Mockery::mock('mauricerenck\IndieConnector\Sender')->makePartial();
    }

    /**
     * @group pageTests
     * @testdox testPageHasNeededStatus - should have valid status
     */
    public function testPageHasNeededStatus()
    {
        $page = $this->getPageMock(false);

        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($page);

        $this->assertTrue($result);
    }

    /**
     * @group pageTests
     * @testdox testPageHasNeededStatus - should handle invalid status
     */
    public function testStopsOnWrongNeededStatus()
    {
        $page = $this->getPageMock(true);

        $senderUtils = new Sender();
        $result = $senderUtils->pageHasNeededStatus($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageTests
     * @testdox templateIsAllowed - should detect allowed template
     */
    public function testPageHasAllowedTemplate()
    {
        $senderUtils = new Sender(null, ['phpunit']);
        $result = $senderUtils->templateIsAllowed('phpunit');

        $this->assertTrue($result);
    }

    /**
     * @group pageTests
     * @testdox templateIsAllowed - should handle invalid template
     */
    public function testPageHasNotAllowedTemplate()
    {
        $senderUtils = new Sender(null, ['phpunit']);
        $result = $senderUtils->templateIsAllowed('not-allowed-template');

        $this->assertFalse($result);
    }

    /**
     * @group pageTests
     * @testdox templateIsBlocked - should handle blocked template
     */
    public function testPageHasBlockedTemplate()
    {
        $senderUtils = new Sender(null, null, ['blocked-template']);
        $result = $senderUtils->templateIsBlocked('blocked-template');

        $this->assertTrue($result);
    }

    /**
     * @group pageTests
     * @testdox templateIsBlocked - should handle unblocked template
     */
    public function testPageHasNotBlockedTemplate()
    {
        $senderUtils = new Sender(null, null, ['blocked-template']);
        $result = $senderUtils->templateIsBlocked('phpunit');

        $this->assertFalse($result);
    }

    /**
     * @group pageTests
     * @testdox pageFullfillsCriteria - should fullfill criteria for Webmention
     */
    public function testPageFullfillsCriteria()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->senderUtilsMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->senderUtilsMock->pageFullfillsCriteria($page);

        $this->assertTrue($result);
    }

    /**
     * @group pageTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when status is draft
     */
    public function testPageNotFullfillsCriteriaStatus()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('pageHasNeededStatus')->andReturn(false);
        $this->senderUtilsMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->senderUtilsMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->senderUtilsMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when tpl blocked
     */
    public function testPageNotFullfillsCriteria()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('templateIsBlocked')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->senderUtilsMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when tpl not allowed
     */
    public function testPageNotFullfillsCriteriaNotAllowed()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->senderUtilsMock->shouldReceive('templateIsAllowed')->andReturn(false);

        $result = $this->senderUtilsMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should return urls from text field
     */
    public function testShouldFindUrls()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://text-field-url.tld',
            'https://www.text-field-url.tld',
            'http://www.text-field-url.tld',
            'https://text-field-url.tld/a-linked-text',
            'https://www.layout-url.tld',
            'https://www.block-url.tld',
            'https://processed-url.tld',
        ];

        $senderUtils = new Sender();
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
        $this->assertContains($expectedUrls[2], $urls);
        $this->assertContains($expectedUrls[3], $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should skip on malformed field config
     */
    public function testShouldFindUrlsMalformedFieldConfig()
    {
        $page = $this->getPageMock();

        $expectedUrls = [];

        $senderUtils = new Sender(['malformed-field-config']);
        $urls = $senderUtils->findUrls($page);

        $this->assertEquals($expectedUrls, $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should return urls of one field when one is malformed
     */
    public function testShouldFindUrlsMalformedFieldConfigWithTwoFields()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://text-field-url.tld',
            'https://www.text-field-url.tld',
            'http://www.text-field-url.tld',
            'https://processed-url.tld',
            'https://text-field-url.tld/a-linked-text',
        ];

        $senderUtils = new Sender(['malformed-field-config', 'textfield:text']);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
        $this->assertContains($expectedUrls[2], $urls);
        $this->assertContains($expectedUrls[3], $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should handle non existing field
     */
    public function testShouldFindUrlsMalformedFieldNotExisting()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://text-field-url.tld',
            'https://www.text-field-url.tld',
            'http://www.text-field-url.tld',
            'https://processed-url.tld',
            'https://text-field-url.tld/a-linked-text',
        ];

        $senderUtils = new Sender(['void:text', 'textfield:text']);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
        $this->assertContains($expectedUrls[2], $urls);
        $this->assertContains($expectedUrls[3], $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should find URL in text field
     */
    public function testShouldFindUrlsInTextField()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://text-field-url.tld',
            'https://www.text-field-url.tld',
            'http://www.text-field-url.tld',
            'https://processed-url.tld',
            'https://text-field-url.tld/a-linked-text',
        ];

        $senderUtils = new Sender(['textfield:text']);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should find URL in block field
     */
    public function testShouldFindUrlsInBlockField()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://www.block-url.tld',
        ];

        $senderUtils = new Sender(['blockeditor:block']);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should find URL in layout field
     */
    public function testShouldFindUrlsInLayoutField()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://www.layout-url.tld',
        ];

        $senderUtils = new Sender(['layouteditor:layout']);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
    }

    /**
     * @group urlHandling
     * @testdox findUrls - should append fed.brid.by when activityPubBridge is enabled
     */
    public function testShouldFindUrlsWithFedBridGy()
    {
        $page = $this->getPageMock();

        $expectedUrls = [
            'https://www.layout-url.tld',
            'https://fed.brid.gy/',
        ];

        $senderUtils = new Sender(['layouteditor:layout'], null, null, true);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
    }

    /**
     * @group urlHandling
     * @testdox getProcessedUrls - should return processed urls
     */
    public function testShouldReturnProcessedUrls()
    {
        $page = $this->getPageMock();

        $fileMock = File::factory([
            'parent' => $page,
            'filename' => 'indieConnector.json',
            'content' => ['["https://processed-url.tld"]']
        ]);

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($fileMock);

        $urls = $this->senderUtilsMock->getProcessedUrls($page);

        $this->assertCount(1, $urls);
        $this->assertEquals(['https://processed-url.tld'], $urls);
    }

    /**
     * @group urlHandling
     * @testdox getProcessedUrls - should handle empty processed urls
     */
    public function testShouldHandleEmptyProcessedUrls()
    {
        $page = $this->getPageMock();

        $fileMock = File::factory([
            'parent' => $page,
            'filename' => 'indieConnector.json',
            'content' => ['[]']
        ]);

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($fileMock);

        $urls = $this->senderUtilsMock->getProcessedUrls($page);

        $this->assertCount(0, $urls);
        $this->assertEquals([], $urls);
    }

    public function testShouldHandleMissingProcessedFile()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn(null);

        $urls = $this->senderUtilsMock->getProcessedUrls($page);

        $this->assertCount(0, $urls);
        $this->assertEquals([], $urls);
    }

    /**
     * @group urlHandling
     * @testdox cleanupUrls - should return single url
     */
    public function testShouldCleanupUrls()
    {
        $sampleUrls = [
            'https://text-field-url.tld',
        ];

        $processedUrls = ["https://processed-url.tld"];

        $senderUtils = new Sender();
        $urls = $senderUtils->cleanupUrls($sampleUrls, $processedUrls);

        $this->assertCount(1, $urls);
        $this->assertContains($sampleUrls[0], $urls);
        $this->assertNotContains($sampleUrls[1], $urls);
    }


    /**
     * @group urlHandling
     * @testdox cleanupUrls - should detect own host
     */
    public function testShouldCleanupUrlsWithProcessedUrl()
    {
        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld',
        ];

        $processedUrls = ["https://processed-url.tld"];

        $senderUtils = new Sender();
        $urls = $senderUtils->cleanupUrls($sampleUrls, $processedUrls);

        $this->assertCount(1, $urls);
        $this->assertContains($sampleUrls[0], $urls);
        $this->assertNotContains($sampleUrls[1], $urls);
    }

    /**
     * @group urlHandling
     * @testdox cleanupUrls - should skip own host
     */
    public function testShouldCleanupUrlsWithOwnHostname()
    {
        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld',
            'https://indie-connector.test/de/my-page',
        ];

        $processedUrls = ["https://processed-url.tld"];

        $senderUtils = new Sender();
        $urls = $senderUtils->cleanupUrls($sampleUrls, $processedUrls);

        $this->assertCount(1, $urls);
        $this->assertContains($sampleUrls[0], $urls);
        $this->assertNotContains($sampleUrls[1], $urls);
    }

    /**
     * @group urlHandling
     * @testdox cleanupUrls - should handle empty array of urls
     */
    public function testShouldCleanupUrlsWithEmptyArray()
    {
        $sampleUrls = [];

        $processedUrls = ["https://processed-url.tld"];

        $senderUtils = new Sender();
        $urls = $senderUtils->cleanupUrls($sampleUrls, $processedUrls);

        $this->assertCount(0, $urls);
    }

    /**
     * @group urlValidation
     * @testdox skipSameHost - should detect own host
     */
    public function testShouldDetectOwnHost()
    {
        $hostname = kirby()->environment()->host();
        $result = $this->senderUtilsMock->skipSameHost('https://' . $hostname . '/de/my-page');

        $this->assertTrue($result);
    }

    /**
     * @group urlValidation
     * @testdox skipSameHost - should detect external host
     */
    public function testShouldDetectExternalHost()
    {
        $result = $this->senderUtilsMock->skipSameHost('https://external-host.tld/de/my-page');

        $this->assertFalse($result);
    }


    /**
     * @group urlHandling
     * @testdox mergeExistingProcessedUrls - should merge existing and new urls
     */
    public function testShouldMergeExistingUrls()
    {
        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld',
        ];

        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn(['https://processed-url.tld']);
        $result = $this->senderUtilsMock->mergeExistingProcessedUrls($sampleUrls, $page);

        $this->assertCount(2, $result);
        $this->assertContains($sampleUrls[0], $result);
        $this->assertContains($sampleUrls[1], $result);
    }

    /**
     * @group urlHandling
     * @testdox mergeExistingProcessedUrls - should handle empty array of new urls
     */
    public function testShouldMergeExistingUrlsWithEmptyArray()
    {
        $sampleUrls = [];

        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn(['https://processed-url.tld']);
        $result = $this->senderUtilsMock->mergeExistingProcessedUrls($sampleUrls, $page);

        $this->assertCount(1, $result);
        $this->assertContains('https://processed-url.tld', $result);
    }

    /**
     * @group urlHandling
     * @testdox mergeExistingProcessedUrls - should handle empty array of existing urls
     */
    public function testShouldMergeEmptyExistingUrlsWithNewUrls()
    {
        $sampleUrls = ['https://processed-url.tld'];

        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn([]);
        $result = $this->senderUtilsMock->mergeExistingProcessedUrls($sampleUrls, $page);

        $this->assertCount(1, $result);
        $this->assertContains('https://processed-url.tld', $result);
    }


    /**
     * @group urlHandling
     * @testdox mergeExistingProcessedUrls - should handle empty array of existing and new urls
     */
    public function testShouldMergeEmptyExistingUrlsWithEmptyNewUrls()
    {
        $sampleUrls = [];

        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn([]);
        $result = $this->senderUtilsMock->mergeExistingProcessedUrls($sampleUrls, $page);

        $this->assertCount(0, $result);
    }



    /**
     * @group urlHandling
     * @testdox storeProcessedUrls - should store processed urls in empty file
     */
    public function testShouldStoreProcessedUrls()
    {
        $page = $this->getPageMock();

        $sampleUrls = [
            'https://text-field-url.tld',
            'https://processed-url.tld'
        ];

        $this->senderUtilsMock->shouldReceive('writeOutbox')->andReturn(true);
        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn(['https://processed-url.tld']);

        $result = $this->senderUtilsMock->storeProcessedUrls($sampleUrls, $page);
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox getUnprocessedUrls - should contain only unprocessed urls
     */
    public function testShouldGetUnprocessedUrls()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('findUrls')->andReturn(['https://text-field-url.tld', 'https://processed-url.tld']);
        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn(['https://processed-url.tld']);
        $this->senderUtilsMock->shouldReceive('cleanupUrls')->andReturn(['https://text-field-url.tld']);

        $result = $this->senderUtilsMock->getUnprocessedUrls($page);
        $this->assertCount(1, $result);
        $this->assertContains('https://text-field-url.tld', $result);
    }

    /**
     * @group urlHandling
     * @testdox getUnprocessedUrls - should contain only unprocessed urls
     */
    public function testShouldGetNoUnprocessedUrls()
    {
        $page = $this->getPageMock();

        $this->senderUtilsMock->shouldReceive('findUrls')->andReturn(['https://text-field-url.tld', 'https://processed-url.tld']);
        $this->senderUtilsMock->shouldReceive('getProcessedUrls')->andReturn(['https://text-field-url.tld', 'https://processed-url.tld']);
        $this->senderUtilsMock->shouldReceive('cleanupUrls')->andReturn([]);

        $result = $this->senderUtilsMock->getUnprocessedUrls($page);
        $this->assertCount(0, $result);
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect local host
     */
    public function testShouldDetectLocalHost()
    {
        $senderUtilsMock = new Sender();
        $result = $senderUtilsMock->isLocalUrl('https://localhost/de/my-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect url is not on local host
     */
    public function testShouldDetectUrlNotOnLocalHost()
    {
        $senderUtilsMock = new Sender();
        $result = $senderUtilsMock->isLocalUrl('https://external-host-tld/de/my-page');
        $this->assertTrue($result);
    }
}
