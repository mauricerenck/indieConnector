<?php

use mauricerenck\IndieConnector\UrlHandler;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\OptionHandlerMock;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;


final class UrlHandlerTest extends TestCase
{
    private static IndieConnectorDatabase $db;

    private $urlHandler;
    private $indieDb;
    private static $defaultOptions;
    private static string $dir;

    public static function setUpBeforeClass(): void
    {
        // setup temp dir for sqlite test database
        self::$dir = sys_get_temp_dir() . '/indieconnector-test-' . uniqid() . '/';
        mkdir(self::$dir, 0777, true);

        self::$defaultOptions = new OptionHandlerMock([
            'sqlitePath' => self::$dir,
        ]);

        // setup database
        self::$db = new IndieConnectorDatabase(options: self::$defaultOptions);

        self::$db->query(
            'CREATE TABLE blocked_urls (
                url TEXT NOT NULL,
                direction TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$dir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$dir);
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->indieDb = self::$db;
        $this->indieDb->truncate(table: 'blocked_urls');

        $this->urlHandler = new UrlHandler(['localhost', '127.0.0.1'], ['https://blocked-source-1.tld/my-spam-page', 'https://blocked-source-2.tld'], ['https://blocked-target-1.tld/my-spam-page', 'https://blocked-target-2.tld'], $this->indieDb);
    }

    #[Group('urlHandler')]
    #[TestDox('isLocalUrl - detect local host')]
    #[AllowMockObjectsWithoutExpectations]
    public function testShouldDetectLocalHost()
    {
        $result = $this->urlHandler->isLocalUrl('https://localhost/de/my-page');
        $this->assertTrue($result);
    }


    #[Group('urlHandler')]
    #[TestDox('isLocalUrl - detect url is not on local host')]
    #[AllowMockObjectsWithoutExpectations]
    public function testShouldDetectUrlNotOnLocalHost()
    {
        $result = $this->urlHandler->isLocalUrl('https://external-host-tld/de/my-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should deny source with path blocked in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedSourceConfig()
    {
        $result = $this->urlHandler->isBlockedSource('https://blocked-source-1.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should deny source with blocked host in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedSourceHostConfig()
    {
        $result = $this->urlHandler->isBlockedSource('https://blocked-source-2.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should deny source with blocked host from database')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedSourceHostDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-source-3.tld',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedSource('https://blocked-source-3.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should deny source with blocked full url from database')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedSourceUrlDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-source-3.tld/my-spam-page',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedSource('https://blocked-source-3.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should allow source with full url from database not matching')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedSourceUrlDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-source.tld/my-other-page',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedSource('https://blocked-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should allow source with host from database not matching')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedSourceHostDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-source.tld',
                'direction' => 'incoming',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should handle empty database result')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedSourceEmptyDatabase()
    {
        $result = $this->urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedSource - should allow source with path not blocked in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsUnBlockedSourceHostConfig()
    {
        $result = $this->urlHandler->isBlockedSource('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should deny target with path blocked in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedTargetConfig()
    {
        $result = $this->urlHandler->isBlockedTarget('https://blocked-target-1.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should deny target blocked host in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedTargetHostConfig()
    {
        $result = $this->urlHandler->isBlockedTarget('https://blocked-target-2.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should deny target with blocked host from database')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedTargetHostDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-target.tld',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedTarget('https://blocked-target.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should deny target with blocked full url from database')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsBlockedTargetUrlDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-target.tld/my-spam-page',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedTarget('https://blocked-target.tld/my-spam-page');
        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should allow target with full url from database not matching')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedTargetUrlDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-target.tld/my-other-page',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedTarget('https://blocked-target.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should allow target with host from database not matching')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedTargetHostDatabase()
    {
        $this->indieDb->insert(
            table: 'blocked_urls',
            values: [
                'url' => 'https://blocked-target.tld',
                'direction' => 'outgoing',
                'created_at' => '2024-01-01 00:00:00'
            ]
        );

        $result = $this->urlHandler->isBlockedTarget('https://allowed-target.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should handle empty database result')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsNotBlockedTargetEmptyDatabase()
    {
        $result = $this->urlHandler->isBlockedTarget('https://allowed-source.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('isBlockedTarget - should allow target with path not blocked in config')]
    #[AllowMockObjectsWithoutExpectations]
    public function testIsUnBlockedTargetHostConfig()
    {
        $result = $this->urlHandler->isBlockedTarget('https://allowed-target.tld/my-spam-page');
        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('skipSameHost - should detect own host')]
    #[AllowMockObjectsWithoutExpectations]
    public function testShouldDetectOwnHost()
    {
        $hostname = kirby()->environment()->host();
        $result = $this->urlHandler->skipSameHost('https://' . $hostname . '/de/my-page');

        $this->assertTrue($result);
    }

    #[Group('urlHandler')]
    #[TestDox('skipSameHost - should detect external host')]
    #[AllowMockObjectsWithoutExpectations]
    public function testShouldDetectExternalHost()
    {
        $result = $this->urlHandler->skipSameHost('https://external-host.tld/de/my-page');

        $this->assertFalse($result);
    }

    #[Group('urlHandler')]
    #[TestDox('blockUrl - should add url to database - incoming - host only')]
    #[AllowMockObjectsWithoutExpectations]
    public function testBlockUrl()
    {
        $result = $this->urlHandler->blockUrl('https://external-host.tld/de/my-page', 'incoming', true);
        $this->assertTrue($result);

        $results = $this->indieDb->select(table: 'blocked_urls', fields: ['*']);
        $this->assertSame(1, $results->count());
        $this->assertContains('https://external-host.tld', $results->first()->toArray());
        $this->assertContains('incoming', $results->first()->toArray());
    }

    #[Group('urlHandler')]
    #[TestDox('blockUrl - should add url to database - incoming - full url')]
    #[AllowMockObjectsWithoutExpectations]
    public function testBlockUrlFullUrl()
    {
        $result = $this->urlHandler->blockUrl('https://external-host.tld/de/my-page', 'incoming', false);
        $this->assertTrue($result);

        $results = $this->indieDb->select(table: 'blocked_urls', fields: ['*']);
        $this->assertSame(1, $results->count());
        $this->assertContains('https://external-host.tld/de/my-page', $results->first()->toArray());
        $this->assertContains('incoming', $results->first()->toArray());
    }

    #[Group('urlHandler')]
    #[TestDox('blockUrl - should add url to database - outgoing - full url')]
    #[AllowMockObjectsWithoutExpectations]
    public function testBlockUrlFullUrlOutgoing()
    {
        $result = $this->urlHandler->blockUrl('https://external-host.tld/de/my-page', 'outgoing', false);
        $this->assertTrue($result);

        $results = $this->indieDb->select(table: 'blocked_urls', fields: ['*']);
        $this->assertSame(1, $results->count());
        $this->assertContains('https://external-host.tld/de/my-page', $results->first()->toArray());
        $this->assertContains('outgoing', $results->first()->toArray());
    }
}
