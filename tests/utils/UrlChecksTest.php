<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\UrlChecks;

final class UrlChecksTest extends TestCaseMocked
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect local host
     */
    public function testShouldDetectLocalHost()
    {
        $senderUtilsMock = new UrlChecks();
        $result = $senderUtilsMock->isLocalUrl('https://localhost/de/my-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect url is not on local host
     */
    public function testShouldDetectUrlNotOnLocalHost()
    {
        $senderUtilsMock = new UrlChecks();
        $result = $senderUtilsMock->isLocalUrl('https://external-host-tld/de/my-page');
        $this->assertTrue($result);
    }
}
