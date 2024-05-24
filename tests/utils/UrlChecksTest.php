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
        $urlChecks = new UrlChecks(['localhost', '127.0.0.1']);
        $result = $urlChecks->isLocalUrl('https://localhost/de/my-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect url is not on local host
     */
    public function testShouldDetectUrlNotOnLocalHost()
    {
        $urlChecks = new UrlChecks(['localhost', '127.0.0.1']);
        $result = $urlChecks->isLocalUrl('https://external-host-tld/de/my-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with path blocked in config
     */
    public function testIsBlockedSourceConfig()
    {
        $urlChecks = new UrlChecks(null, ['https://blocked-source.tld/my-spam-page']);
        $result = $urlChecks->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with blocked host in config
     */
    public function testIsBlockedSourceHostConfig()
    {
        $urlChecks = new UrlChecks(null, ['https://blocked-source.tld']);
        $result = $urlChecks->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should allow source with path not blocked in config
     */
    public function testIsUnBlockedSourceHostConfig()
    {
        $urlChecks = new UrlChecks(null, ['https://blocked-source.tld']);
        $result = $urlChecks->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox skipSameHost - should detect own host
     */
    public function testShouldDetectOwnHost()
    {
        $urlChecks = new UrlChecks();

        $hostname = kirby()->environment()->host();
        $result = $urlChecks->skipSameHost('https://' . $hostname . '/de/my-page');

        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox skipSameHost - should detect external host
     */
    public function testShouldDetectExternalHost()
    {
        $urlChecks = new UrlChecks();
        $result = $urlChecks->skipSameHost('https://external-host.tld/de/my-page');

        $this->assertFalse($result);
    }
}
