<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\WebmentionHandler;

use Kirby\Http\Remote;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

final class WebmentionHandlerTest extends TestCase
{
    private $webmentionHandlerMock;
    private $webmentionHandler;

    private $indieDb;
    private static $defaultOptions;
    private static string $dir;
    private static IndieConnectorDatabase $db;

    private function clientWithResponse(int $code, string $html, array $headers = []): WebmentionHandler
    {
        $response = $this->createStub(Remote::class);
        $response->method('code')->willReturn($code);
        $response->method('content')->willReturn($html);
        $response->method('headers')->willReturn($headers);

        $client = $this->getMockBuilder(WebmentionHandler::class)
            ->onlyMethods(['request'])
            ->getMock();
        $client->method('request')->willReturn($response);

        return $client;
    }

    public static function linkHeaderProvider(): array
    {
        $htmlWithEndpoint = '<html><head><link rel="webmention" href="https://example.com/from-html"></head></html>';
        $emptyHtml = '<html><head></head></html>';

        return [
            'absolute URL im Header' => [
                200,
                $emptyHtml,
                ['Link' => '<https://example.com/wm>; rel="webmention"'],
                'https://example.com/wm',
            ],
            'relative URL im Header wird aufgelöst' => [
                200,
                $emptyHtml,
                ['Link' => '</wm>; rel="webmention"'],
                'https://example.com/wm',
            ],
            'rel ohne Anführungszeichen' => [
                200,
                $emptyHtml,
                ['Link' => '<https://example.com/wm>; rel=webmention'],
                'https://example.com/wm',
            ],
            'Header-Name case-insensitive' => [
                200,
                $emptyHtml,
                ['link' => '<https://example.com/wm>; rel="webmention"'],
                'https://example.com/wm',
            ],
            'mehrere rel-Werte' => [
                200,
                $emptyHtml,
                ['Link' => '<https://example.com/wm>; rel="webmention nofollow"'],
                'https://example.com/wm',
            ],
            'mehrere Links in einer Zeile' => [
                200,
                $emptyHtml,
                ['Link' => '<https://example.com/next>; rel="next", <https://example.com/wm>; rel="webmention"'],
                'https://example.com/wm',
            ],
            'mehrere Link-Header als Array' => [
                200,
                $emptyHtml,
                ['Link' => [
                    '<https://example.com/next>; rel="next"',
                    '<https://example.com/wm>; rel="webmention"',
                ]],
                'https://example.com/wm',
            ],
            'Header hat Vorrang vor HTML' => [
                200,
                $htmlWithEndpoint,
                ['Link' => '<https://example.com/from-header>; rel="webmention"'],
                'https://example.com/from-header',
            ],
            'Link-Header ohne webmention: Fallback auf HTML' => [
                200,
                $htmlWithEndpoint,
                ['Link' => '<https://example.com/next>; rel="next"'],
                'https://example.com/from-html',
            ],
            'anderer Header wird ignoriert' => [
                200,
                $emptyHtml,
                ['X-Foo' => '<https://example.com/wm>; rel="webmention"'],
                null,
            ],
            'Header bei 404 wird ignoriert' => [
                404,
                $emptyHtml,
                ['Link' => '<https://example.com/wm>; rel="webmention"'],
                null,
            ],
        ];
    }



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

        $this->webmentionHandlerMock = $this->getMockBuilder(WebmentionHandler::class)
            ->setConstructorArgs([$this->defaultOptions])
            ->getMock();

        $this->webmentionHandler = new WebmentionHandler(options: $this->defaultOptions);
    }

    //     #[Group('webmentionHandler')]
    //     #[TestDox('registerPageUrls - register all urls')]
    //     #[AllowMockObjectsWithoutExpectations]
    //     public function testRegisterAllUrls() {}
    //
    //     #[Group('webmentionHandler')]
    //     #[TestDox('discoverWebmentionEndpoint - register all urls')]
    //     #[AllowMockObjectsWithoutExpectations]
    //     public function testDiscoverWebmentionEndpoint()
    //     {
    //         $this->webmentionHandler->discoverWebmentionEndpoint('https://notiz.blog/');
    //     }

    #[DataProvider('linkHeaderProvider')]
    public function testDiscoveryViaLinkHeader(int $code, string $html, array $headers, ?string $expected): void
    {
        $client = $this->clientWithResponse($code, $html, $headers);

        $this->assertSame(
            $expected,
            $client->discoverWebmentionEndpoint('https://example.com/post')
        );
    }
}
