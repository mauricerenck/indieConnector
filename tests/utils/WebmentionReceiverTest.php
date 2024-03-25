<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\WebmentionReceiver;

final class WebmentionReceiverTest extends TestCaseMocked
{
    private $microformatsJSON;

    public function setUp(): void
    {
        parent::setUp();

        $this->microformatsJSON = file_get_contents(__DIR__ . '/../fixtures/mf.json');
    }

    /**
     * @group receiveWebmentions
     * @testdox getDataFromSource - should get and parse mf2 data from source
     */
    public function testShouldSendWebmention()
    {
        $webmentionReceiver = new WebmentionReceiver();
        $sourceUrl = 'https://maurice-renck.de/de/blog/2024/kirby-podcaster-transcripts';

        $result = $webmentionReceiver->getDataFromSource($sourceUrl);
        // NOTE this is for getting mf2 json from a real source
        // $mf2Result = $webmentionReceiver->getDataFromSource('http://indieconnector.mauricerenck.de/');
        // file_put_contents('detailed-mf2.json', json_encode($mf2Result));
        $this->assertTrue(false);
    }

    /**
     * @group receiveWebmentions
     * @testdox getHEntry - get h-entry from mf2
     */
    public function testGetHEntry()
    {
        $item = [
            'type' => ['h-entry'],
            'properties' => [
                'category' => ['Kirby CMS'],
                'summary' => ['This is a summary'],
                'published' => ['2024-02-01 09:30:00'],
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
        ];

        $mf2 = [
            'items' => [$item],
        ];

        $webmentionReceiver = new WebmentionReceiver();

        $result = $webmentionReceiver->getHEntry($mf2);
        $this->assertEquals($item, $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getHCard - get h-card from mf2
     */
    public function testGetHCard()
    {
        $item = [
            'type' => ['h-card', 'h-card-footer'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'note' => [
                    'Maurice entwickelt Ideen & Tools f\u00fcr digitales Storytelling und ein offenes Web. Er schreibt seit \u00fcber 25 Jahren ins Internet, podcastet seit 2005. Maurice gr\u00fcndete ein Startup und ein Print-Magazin. Er schreibt Texte, Musik und Code.',
                ],
                'photo' => [
                    [
                        'value' => 'https://indieconnector.tld/profile.jpg',
                        'alt ' => 'A photo of Maurice Renck',
                    ],
                ],
                'url' => ['https://maurice-renck.de'],
            ],
        ];

        $mf2 = [
            'items' => [$item],
        ];

        $webmentionReceiver = new WebmentionReceiver();

        $result = $webmentionReceiver->getHCard($mf2);
        $this->assertEquals($item, $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getContent - get the summary from h-entry
     */
    public function testGetContent()
    {
        $item = [
            'type' => ['h-entry'],
            'properties' => [
                'category' => ['Kirby CMS'],
                'summary' => ['This is a summary'],
                'published' => ['2024-02-01 09:30:00'],
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
        ];

        $webmentionReceiver = new WebmentionReceiver();
        $result = $webmentionReceiver->getContent($item);

        $this->assertEquals('This is a summary', $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getContent - get the content from h-entry
     */
    public function testGetContentContent()
    {
        $item = [
            'type' => ['h-entry'],
            'properties' => [
                'category' => ['Kirby CMS'],
                'summary' => [],
                'published' => ['2024-02-01 09:30:00'],
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
        ];

        $webmentionReceiver = new WebmentionReceiver();
        $result = $webmentionReceiver->getContent($item);

        $this->assertEquals('This is a test.', $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getContent - handle empty content
     */
    public function testGetContentNoContent()
    {
        $item = [
            'type' => ['h-entry'],
            'properties' => [
                'category' => ['Kirby CMS'],
                'summary' => [],
                'published' => ['2024-02-01 09:30:00'],
                'content' => [],
                'author' => [
                    [
                        'type' => ['h-card'],
                        'properties' => ['name' => ['Maurice Renck'], 'url' => ['https://maurice-renck.de']],
                        'value' => 'Maurice Renck',
                    ],
                ],
            ],
        ];

        $webmentionReceiver = new WebmentionReceiver();
        $result = $webmentionReceiver->getContent($item);

        $this->assertNull($result);
    }
}
