<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\WebmentionReceiver;

final class WebmentionReceiverTest extends TestCaseMocked
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @group receiveWebmentions
     * @testdox convertToHookData - should create an array with the correct keys
     */
    public function testShouldConvertToHookData()
    {
        $targetUrl = $this->localUrl . '/home';

        $expected = [
            'type' => 'in-reply-to',
            'target' => $targetUrl,
            'source' => 'https://sender.tld',
            'published' => '2024-02-01 09:30:00',
            'title' => 'This is my blog post',
            'content' => 'This is a summary',
            'author' => [
                'type' => 'card',
                'name' => 'Maurice Renck',
                'avatar' => null,
                'url' => 'https://maurice-renck.de',
            ],
            'service' => 'web',
        ];

        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'name' => ['This is my blog post'],
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'in-reply-to' => ['https://unknown.url', $targetUrl],
                        'content' => [
                            [
                                'html' => 'This is a <strong>test</strong>.',
                                'value' => 'This is a test.',
                            ],
                        ],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => ['name' => ['Maurice Renck'], 'url' => ['https://maurice-renck.de']],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $webmentionReceiver = new WebmentionReceiver(
            'https://indieconnector.dev/tests/reply.php?replyto=https://indie-connector.test:8890/home',
            $targetUrl
        );
        $webmentionData = $webmentionReceiver->getWebmentionData($mf2);
        $webmentions = $webmentionReceiver->splitWebmentionDataIntoHooks($webmentionData);

        $result = $webmentionReceiver->convertToHookData($webmentions[0], [
            'source' => 'https://sender.tld',
            'target' => $targetUrl,
        ]);

        $this->assertCount(1, $webmentions);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox splitWebmentionDataIntoHooks - should have two hooks
     */
    public function testShouldSplitIntoHooks()
    {
        $targetUrl = $this->localUrl . '/home';

        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'name' => ['This is my blog post'],
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'in-reply-to' => ['https://unknown.url', $targetUrl],
                        'like-of' => ['https://unknown.url', $targetUrl],
                        'content' => [
                            [
                                'html' => 'This is a <strong>test</strong>.',
                                'value' => 'This is a test.',
                            ],
                        ],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => ['name' => ['Maurice Renck'], 'url' => ['https://maurice-renck.de']],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $urls = [
            'source' => 'https://sender.tld',
            'target' => $targetUrl,
        ];

        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', $targetUrl);
        $webmentionData = $webmentionReceiver->getWebmentionData($mf2);
        $webmentions = $webmentionReceiver->splitWebmentionDataIntoHooks($webmentionData);
        $result1 = $webmentionReceiver->convertToHookData($webmentions[0], $urls);
        $result2 = $webmentionReceiver->convertToHookData($webmentions[1], $urls);

        $this->assertCount(2, $webmentions);
        $this->assertContains('in-reply-to', $result1);
        $this->assertContains('like-of', $result2);
    }

    /**
     * @group receiveWebmentions
     * @testdox getWebmentionData - should create data for hooks
     */
    public function testGetWebmentionData()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'name' => ['This is my blog post'],
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'in-reply-to' => ['https://unknown.url', 'https://indie-connector.tld'],
                        'like-of' => ['https://unknown.url', 'https://indie-connector.tld'],
                        'category' => ['ic-src-mastodon'],
                        'content' => [
                            [
                                'html' => 'This is a <strong>test</strong>.',
                                'value' => 'This is a test.',
                            ],
                        ],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => ['name' => ['Maurice Renck'], 'url' => ['https://maurice-renck.de']],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedAuthor = [
            'name' => 'Maurice Renck',
            'photo' => null,
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', 'https://indie-connector.tld');
        $webmentionData = $webmentionReceiver->getWebmentionData($mf2);

        $this->assertCount(2, $webmentionData['types']);
        $this->assertContains('in-reply-to', $webmentionData['types']);
        $this->assertContains('like-of', $webmentionData['types']);
        $this->assertEquals('This is a summary', $webmentionData['content']);
        $this->assertEquals($expectedAuthor, $webmentionData['author']);
        $this->assertEquals('This is my blog post', $webmentionData['title']);
        $this->assertEquals('mastodon', $webmentionData['service']);
    }

    /**
     * @group receiveWebmentions
     * @testdox getWebmentionData - returns false when items are empty
     */
    public function testGetWebmentionDataReturnsFalseWhenEmpty()
    {
        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', 'https://target.tld');
        $result = $webmentionReceiver->getWebmentionData(['items' => []]);

        $this->assertFalse($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getWebmentionData - returns false when no items key
     */
    public function testGetWebmentionDataReturnsFalseWhenNoItems()
    {
        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', 'https://target.tld');
        $result = $webmentionReceiver->getWebmentionData([]);

        $this->assertFalse($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox splitWebmentionDataIntoHooks - returns one hook per type
     */
    public function testSplitWebmentionDataIntoHooksReturnsOneHookPerType()
    {
        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', 'https://target.tld');

        $webmentionData = [
            'types' => ['like-of', 'repost-of'],
            'content' => 'test',
            'published' => '2025-01-01',
            'author' => ['name' => 'Alice'],
            'title' => 'Test',
            'service' => 'web',
        ];

        $result = $webmentionReceiver->splitWebmentionDataIntoHooks($webmentionData);

        $this->assertCount(2, $result);
        $this->assertEquals('like-of', $result[0]['type']);
        $this->assertEquals('repost-of', $result[1]['type']);
    }

    /**
     * @group receiveWebmentions
     * @testdox splitWebmentionDataIntoHooks - returns empty array when no types
     */
    public function testSplitWebmentionDataIntoHooksReturnsEmptyWhenNoTypes()
    {
        $webmentionReceiver = new WebmentionReceiver('https://sender.tld', 'https://target.tld');

        $webmentionData = [
            'types' => [],
            'content' => '',
            'published' => '',
            'author' => [],
            'title' => '',
            'service' => '',
        ];

        $result = $webmentionReceiver->splitWebmentionDataIntoHooks($webmentionData);

        $this->assertCount(0, $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox processWebmention - returns error when no webmention data
     */
    public function testProcessWebmentionReturnsErrorWhenNoData()
    {
        $webmentionReceiver = $this->getMockBuilder(WebmentionReceiver::class)
            ->setConstructorArgs(['https://sender.tld', 'https://target.tld'])
            ->onlyMethods(['getDataFromSource', 'getWebmentionData'])
            ->getMock();

        $webmentionReceiver->method('getDataFromSource')->willReturn([]);
        $webmentionReceiver->method('getWebmentionData')->willReturn(false);

        $result = $webmentionReceiver->processWebmention('https://sender.tld', 'https://target.tld');

        $this->assertEquals(['status' => 'error', 'message' => 'no webmention data'], $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox processWebmention - returns error when no target page found
     */
    public function testProcessWebmentionReturnsErrorWhenNoTargetPage()
    {
        $webmentionReceiver = $this->getMockBuilder(WebmentionReceiver::class)
            ->setConstructorArgs(['https://sender.tld', 'https://target.tld'])
            ->onlyMethods(['getDataFromSource', 'getWebmentionData', 'getPageFromUrl'])
            ->getMock();

        $webmentionReceiver->method('getDataFromSource')->willReturn([]);
        $webmentionReceiver->method('getWebmentionData')->willReturn([
            'types' => ['like-of'],
            'content' => '',
            'published' => '',
            'author' => [],
            'title' => '',
            'service' => 'web',
        ]);
        $webmentionReceiver->method('getPageFromUrl')->willReturn(false);

        $result = $webmentionReceiver->processWebmention('https://sender.tld', 'https://target.tld');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('no target page found', $result['message']);
    }
}
