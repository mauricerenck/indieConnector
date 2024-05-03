<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\PageChecks;

final class PageChecksTest extends TestCaseMocked
{
    private $pageCheckMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->pageCheckMock = Mockery::mock('mauricerenck\IndieConnector\PageChecks')->makePartial();
    }

    /**
     * @group pageChecksTests
     * @testdox testPageHasNeededStatus - should have valid status
     */
    public function testPageHasNeededStatus()
    {
        $page = $this->getPageMock(false);

        $pageChecks = new PageChecks();
        $result = $pageChecks->pageHasNeededStatus($page);

        $this->assertTrue($result);
    }

    /**
     * @group pageChecksTests
     * @testdox testPageHasNeededStatus - should handle invalid status
     */
    public function testStopsOnWrongNeededStatus()
    {
        $page = $this->getPageMock(true);

        $pageChecks = new PageChecks();
        $result = $pageChecks->pageHasNeededStatus($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageChecksTests
     * @testdox pageFullfillsCriteria - should fullfill criteria for Webmention
     */
    public function testPageFullfillsCriteria()
    {
        $page = $this->getPageMock();

        $this->pageCheckMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->pageCheckMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->pageCheckMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->pageCheckMock->pageFullfillsCriteria($page);

        $this->assertTrue($result);
    }

    /**
     * @group pageChecksTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when status is draft
     */
    public function testPageNotFullfillsCriteriaStatus()
    {
        $page = $this->getPageMock();

        $this->pageCheckMock->shouldReceive('pageHasNeededStatus')->andReturn(false);
        $this->pageCheckMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->pageCheckMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->pageCheckMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageChecksTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when tpl blocked
     */
    public function testPageNotFullfillsCriteria()
    {
        $page = $this->getPageMock();

        $this->pageCheckMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->pageCheckMock->shouldReceive('templateIsBlocked')->andReturn(true);
        $this->pageCheckMock->shouldReceive('templateIsAllowed')->andReturn(true);

        $result = $this->pageCheckMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageChecksTests
     * @testdox pageFullfillsCriteria - should not fullfill criteria when tpl not allowed
     */
    public function testPageNotFullfillsCriteriaNotAllowed()
    {
        $page = $this->getPageMock();

        $this->pageCheckMock->shouldReceive('pageHasNeededStatus')->andReturn(true);
        $this->pageCheckMock->shouldReceive('templateIsBlocked')->andReturn(false);
        $this->pageCheckMock->shouldReceive('templateIsAllowed')->andReturn(false);

        $result = $this->pageCheckMock->pageFullfillsCriteria($page);

        $this->assertFalse($result);
    }

    /**
     * @group pageChecksTests
     * @testdox templateIsAllowed - should detect allowed template
     */
    public function testPageHasAllowedTemplate()
    {
        $pageChecks = new PageChecks(['phpunit']);
        $result = $pageChecks->templateIsAllowed('phpunit');

        $this->assertTrue($result);
    }

    /**
     * @group pageChecksTests
     * @testdox templateIsAllowed - should handle invalid template
     */
    public function testPageHasNotAllowedTemplate()
    {
        $pageChecks = new PageChecks(['phpunit']);
        $result = $pageChecks->templateIsAllowed('not-allowed-template');

        $this->assertFalse($result);
    }

    /**
     * @group pageChecksTests
     * @testdox templateIsBlocked - should handle blocked template
     */
    public function testPageHasBlockedTemplate()
    {
        $pageChecks = new PageChecks(null, ['blocked-template']);
        $result = $pageChecks->templateIsBlocked('blocked-template');

        $this->assertTrue($result);
    }

    /**
     * @group pageChecksTests
     * @testdox templateIsBlocked - should handle unblocked template
     */
    public function testPageHasNotBlockedTemplate()
    {
        $pageChecks = new PageChecks(null, ['blocked-template']);
        $result = $pageChecks->templateIsBlocked('phpunit');

        $this->assertFalse($result);
    }
}
