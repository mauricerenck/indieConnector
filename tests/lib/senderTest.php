<?php

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

        $expectedUrls = ['https://www.block-url.tld'];

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

        $expectedUrls = ['https://www.layout-url.tld'];

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

        $expectedUrls = ['https://www.layout-url.tld', 'https://fed.brid.gy/'];

        $senderUtils = new Sender(['layouteditor:layout'], true);
        $urls = $senderUtils->findUrls($page);

        $this->assertCount(count($expectedUrls), $urls);
        $this->assertContains($expectedUrls[0], $urls);
        $this->assertContains($expectedUrls[1], $urls);
    }

    /**
     * @group outbox
     * @testdox createOutbox - should create a new outbox
     */
    public function testShouldCreateEmptyOutbox()
    {
        $page = $this->getPageMock();
        $this->senderUtilsMock->shouldReceive('writeOutbox');

        $expected = [
            'version' => 2,
            'webmentions' => [],
            'posts' => [],
        ];

        $result = $this->senderUtilsMock->createOutbox($page);

        $this->assertEquals($expected, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should convert an array of urls
     */
    public function testConvertProcessedUrlV2()
    {
        $urls = ['https://processed-url.tld', 'https://processed-url-2.tld'];

        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should convert an array of urls in old and new format
     */
    public function testConvertProcessedUrlV2Mixed()
    {
        $urls = [
            'https://processed-url.tld',
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should handle new formats only
     */
    public function testConvertProcessedUrlV2New()
    {
        $expect = [
            [
                'url' => 'https://processed-url.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
            [
                'url' => 'https://processed-url-2.tld',
                'status' => 'success',
                'date' => date('Y-m-d H:i:s'),
                'retries' => 0,
            ],
        ];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($expect);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group sendWebmentions
     * @testdox convertProcessedUrlsToV2 - should handle empty array
     */
    public function testConvertProcessedUrlV2Empty()
    {
        $urls = [];
        $expect = [];

        $result = $this->senderUtilsMock->convertProcessedUrlsToV2($urls);
        $this->assertEquals($expect, $result);
    }

    /**
     * @group mastodonSender
     * @testdox getPostTargetUrl - should get Mastodon URL
     */
    public function testShouldGetMastodonUrl()
    {
        $page = $this->getPageMock();

        $outbox = [
            'version' => 2,
            'webmentions' => [],
            'posts' => [
                [
                    'url' => 'https://mastodon.example.com',
                    'status' => 'success',
                    'target' => 'mastodon',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
                [
                    'url' => 'https://bluesky.example.com',
                    'status' => 'success',
                    'target' => 'bluesky',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
            ],
        ];

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($outbox);

        $result = $this->senderUtilsMock->getPostTargetUrl('mastodon', $page);

        $this->assertEquals($result, 'https://mastodon.example.com');
    }

    /**
     * @group mastodonSender
     * @testdox getPostTargetUrl - should get Bluesky URL
     */
    public function testShouldGetblueskyUrl()
    {
        $page = $this->getPageMock();

        $outbox = [
            'version' => 2,
            'webmentions' => [],
            'posts' => [
                [
                    'url' => 'https://mastodon.example.com',
                    'status' => 'success',
                    'target' => 'mastodon',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
                [
                    'url' => 'https://bluesky.example.com',
                    'status' => 'success',
                    'target' => 'bluesky',
                    'date' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                ],
            ],
        ];

        $this->senderUtilsMock->shouldReceive('readOutbox')->andReturn($outbox);

        $result = $this->senderUtilsMock->getPostTargetUrl('bluesky', $page);

        $this->assertEquals($result, 'https://bluesky.example.com');
    }
}
