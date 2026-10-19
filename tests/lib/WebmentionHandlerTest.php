<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\WebmentionHandler;

use Kirby\Cms\Page;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class WebmentionHandlerTest extends TestCase
{
    private $webmentionHandlerMock;
    private $webmentionHandler;

    private $indieDb;
    private static $defaultOptions;
    private static string $dir;
    private static IndieConnectorDatabase $db;

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
            '
            CREATE TABLE "webmentions_sent" ("id" integer,"page_uuid" text NOT NULL,"target_url" text NOT NULL,"source_url" text,"updates" int DEFAULT \'1\',"status" text,"created_at" text,"updated_at" text, PRIMARY KEY (id))
            '
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


        $this->webmentionHandlerMock = $this->getMockBuilder(WebmentionHandler::class)
            ->setConstructorArgs([$this->indieDb, $this->defaultOptions])
            ->getMock();

        $this->webmentionHandler = new WebmentionHandler(indieDatabase: $this->indieDb, options: $this->defaultOptions);
    }

    #[Group('webmentionHandler')]
    #[TestDox('registerPageUrls - register all urls')]
    #[AllowMockObjectsWithoutExpectations]
    public function testRegisterAllUrls() {}
}
