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
     * @testdox processWebmention - should process webmention
     */
    public function testShouldProcessWebmention()
    {
        $webmentionReceiver = new WebmentionReceiver(
            'https://indieconnector.dev/tests/reply.php?replyto=https://indie-connector.test:8890/home',
            $this->localUrl . '/home'
        );

        $sourceUrl = 'https://indieconnector.dev/tests/reply.php?replyto=https://indie-connector.test:8890/home';
        $targetUrl = $this->localUrl . '/home';

        $expected = [
            'status' => 'success',
            'message' => 'webmention processed',
        ];

        $result = $webmentionReceiver->processWebmention($sourceUrl, $targetUrl);
        $this->assertEquals($expected, $result);
    }

    // /**
    //  * @group receiveWebmentions
    //  * @testdox getDataFromSource - should get and parse mf2 data from source
    //  */
    // public function testShouldSendWebmention()
    // {
    //     $webmentionReceiver = new WebmentionReceiver();
    //     $sourceUrl =
    //         'https://brid.gy/like/mastodon/@mauricerenck@mastodon.online/112474546285378499/111626473261717260';

    //     $result = $webmentionReceiver->getDataFromSource($sourceUrl);
    //     // NOTE this is for getting mf2 json from a real source
    //     $mf2Result = $webmentionReceiver->getDataFromSource(
    //         'https://brid.gy/like/mastodon/@mauricerenck@mastodon.online/112474546285378499/111626473261717260'
    //     );
    //     file_put_contents('bridgy-mf2.json', json_encode($mf2Result));
    //     $this->assertTrue(false);
    // }

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
    }
}
