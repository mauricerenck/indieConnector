<?php

use Kirby\Toolkit\Collection;
use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\UrlHandler;
use mauricerenck\IndieConnector\IndieConnectorDatabase;

final class UrlHandlerTest extends TestCaseMocked
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
        $urlHandler = new UrlHandler(['localhost', '127.0.0.1']);
        $result = $urlHandler->isLocalUrl('https://localhost/de/my-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isLocalUrl - detect url is not on local host
     */
    public function testShouldDetectUrlNotOnLocalHost()
    {
        $urlHandler = new UrlHandler(['localhost', '127.0.0.1']);
        $result = $urlHandler->isLocalUrl('https://external-host-tld/de/my-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with path blocked in config
     */
    public function testIsBlockedSourceConfig()
    {
        $urlHandler = new UrlHandler(null, ['https://blocked-source.tld/my-spam-page']);
        $result = $urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with blocked host in config
     */
    public function testIsBlockedSourceHostConfig()
    {
        $urlHandler = new UrlHandler(null, ['https://blocked-source.tld']);
        $result = $urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with blocked host from database
     */
    public function testIsBlockedSourceHostDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should deny source with blocked full url from database
     */
    public function testIsBlockedSourceUrlDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld/my-spam-page',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should allow source with full url from database not matching
     */
    public function testIsNotBlockedSourceUrlDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld/my-other-page',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should allow source with host from database not matching
     */
    public function testIsNotBlockedSourceHostDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should handle empty database result
     */
    public function testIsNotBlockedSourceEmptyDatabase()
    {
        $dbResult = new Collection([]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedSource - should allow source with path not blocked in config
     */
    public function testIsUnBlockedSourceHostConfig()
    {
        $urlHandler = new UrlHandler(null, ['https://blocked-source.tld']);
        $result = $urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should deny target with path blocked in config
     */
    public function testIsBlockedTargetConfig()
    {
        $urlHandler = new UrlHandler(null, [], ['https://blocked-source.tld/my-spam-page']);
        $result = $urlHandler->isBlockedTarget('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should deny target with blocked host in config
     */
    public function testIsBlockedTargetHostConfig()
    {
        $urlHandler = new UrlHandler(null, [], ['https://blocked-source.tld']);
        $result = $urlHandler->isBlockedTarget('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should deny target with blocked host from database
     */
    public function testIsBlockedTargetHostDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedTarget('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should deny target with blocked full url from database
     */
    public function testIsBlockedTargetUrlDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld/my-spam-page',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedTarget('https://blocked-source.tld/my-spam-page');
        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should allow target with full url from database not matching
     */
    public function testIsNotBlockedTargetUrlDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld/my-other-page',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedTarget('https://blocked-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should allow target with host from database not matching
     */
    public function testIsNotBlockedTargetHostDatabase()
    {
        $dbResult = new Collection([
            [
                'url' => 'https://blocked-source.tld',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        ]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedTarget('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should handle empty database result
     */
    public function testIsNotBlockedTargetEmptyDatabase()
    {
        $dbResult = new Collection([]);

        // Mock the IndieConnectorDatabase
        $dbMock = $this->getMockBuilder(IndieConnectorDatabase::class)
            ->onlyMethods(['select'])
            ->getMock();

        $dbMock->method('select')->willReturn($dbResult);

        // Pass the mock to UrlHandler
        $urlHandler = new UrlHandler(
            null,
            [],
            [],
            $dbMock
        );

        $result = $urlHandler->isBlockedTarget('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    /**
     * @group urlHandling
     * @testdox isBlockedTarget - should allow target with path not blocked in config
     */
    public function testIsUnBlockedTargetHostConfig()
    {
        $urlHandler = new UrlHandler(null, ['https://blocked-source.tld']);
        $result = $urlHandler->isBlockedTarget('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }


    /**
     * @group urlHandling
     * @testdox skipSameHost - should detect own host
     */
    public function testShouldDetectOwnHost()
    {
        $urlHandler = new UrlHandler();

        $hostname = kirby()->environment()->host();
        $result = $urlHandler->skipSameHost('https://' . $hostname . '/de/my-page');

        $this->assertTrue($result);
    }

    /**
     * @group urlHandling
     * @testdox skipSameHost - should detect external host
     */
    public function testShouldDetectExternalHost()
    {
        $urlHandler = new UrlHandler();
        $result = $urlHandler->skipSameHost('https://external-host.tld/de/my-page');

        $this->assertFalse($result);
    }
}
