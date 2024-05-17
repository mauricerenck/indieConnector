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

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with path blocked in config
     */
    public function testIsBlockedSourceConfig()
    {
        $senderUtilsMock = new UrlChecks(null, ['https://blocked-source.tld/my-spam-page']);
        $result = $senderUtilsMock->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with blocked host in config
     */
    public function testIsBlockedSourceHostConfig()
    {
        $senderUtilsMock = new UrlChecks(null, ['https://blocked-source.tld']);
        $result = $senderUtilsMock->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should allow source with path not blocked in config
     */
    public function testIsUnBlockedSourceHostConfig()
    {
        $senderUtilsMock = new UrlChecks(null, ['https://blocked-source.tld']);
        $result = $senderUtilsMock->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }
}
