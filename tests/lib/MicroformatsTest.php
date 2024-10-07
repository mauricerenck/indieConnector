<?php

use mauricerenck\IndieConnector\Microformats;
use mauricerenck\IndieConnector\TestCaseMocked;

final class MicroformatsTest extends TestCaseMocked
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @group microformats
     * @testdox getTypes - like
     */
    public function testGetTypeLike()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertEquals(['like-of'], $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - repost
     */
    public function testGetTypeRepost()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'repost-of' => ['https://unknown.url', 'https://indie-connector.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertEquals(['repost-of'], $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - bookmark
     */
    public function testGetTypeBookmark()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'bookmark-of' => ['https://unknown.url', 'https://indie-connector.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertEquals(['bookmark-of'], $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - reply
     */
    public function testGetTypeReply()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'in-reply-to' => ['https://unknown.url', 'https://indie-connector.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertEquals(['in-reply-to'], $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - handle multiple types
     */
    public function testGetTypeMultiple()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'in-reply-to' => ['https://unknown.url', 'https://indie-connector.tld'],
                        'bookmark-of' => ['https://unknown.url', 'https://indie-connector.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertCount(2, $result);
        $this->assertContains('in-reply-to', $result);
        $this->assertContains('bookmark-of', $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - handle type without matching url
     */
    public function testGetTypeLikeUnknownUrls()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'like-of' => ['https://unknown.url', 'https://fake.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertCount(0, $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - handle unknown type
     */
    public function testGetTypeUnknownType()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'spam-of' => ['https://unknown.url', 'https://indie-connector.tld'],
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

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertCount(0, $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - event from children when invited via hcard url
     */
    public function testGetTypeEventFromChildUrl()
    {
        $mf2 = [
            'items' => [
                [
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
                    'children' => [
                        [
                            'type' => ['h-event'],
                            'properties' => [
                                'name' => ['IndieWeb Summit'],
                                'url' => ['https://indieweb.org/2017'],
                                'location' => [],
                                'invitee' => [
                                    [
                                        'type' => ['h-card'],
                                        'properties' => ['name' => ['Indie'], 'url' => ['https://indie-connector.tld']],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertCount(1, $result);
        $this->assertEquals(['invite'], $result);
    }

    /**
     * @group microformats
     * @testdox getTypes - event from root
     */
    public function testGetTypeEventUrl()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-event'],
                    'properties' => [
                        'name' => ['IndieWeb Summit'],
                        'url' => ['https://indieweb.org/2017'],
                        'location' => [],
                        'invitee' => [
                            [
                                'type' => ['h-card'],
                                'properties' => ['name' => ['Indie'], 'url' => ['https://indie-connector.tld']],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->getTypes($mf2);

        $this->assertCount(1, $result);
        $this->assertEquals(['invite'], $result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - true when invited via hcard url
     */
    public function testIsInvitedToEventValue()
    {
        $item = [
            'type' => ['h-event'],
            'properties' => [
                'name' => ['IndieWeb Summit'],
                'url' => ['https://indieweb.org/2017'],
                'location' => [],
                'invitee' => [
                    [
                        'type' => ['h-card'],
                        'properties' => ['name' => ['Indie'], 'url' => ['https://indie-connector.tld']],
                        'value' => 'https://indie-connector.tld',
                    ],
                ],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - true when invited via value
     */
    public function testIsInvitedToEvent()
    {
        $item = [
            'type' => ['h-event'],
            'properties' => [
                'name' => ['IndieWeb Summit'],
                'url' => ['https://indieweb.org/2017'],
                'location' => [],
                'invitee' => [
                    [
                        'type' => ['h-card'],
                        'properties' => ['name' => ['Indie'], 'url' => ['https://indie-connector.tld']],
                    ],
                ],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - false when no properties
     */
    public function testIsInvitedToEventNoProperties()
    {
        $item = [
            'type' => ['h-event'],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - false when no invitees
     */
    public function testIsInvitedToEventNoInvitees()
    {
        $item = [
            'type' => ['h-event'],
            'properties' => [
                'name' => ['IndieWeb Summit'],
                'url' => ['https://indieweb.org/2017'],
                'location' => [],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - false when empty invitees
     */
    public function testIsInvitedToEventEmptyInvitees()
    {
        $item = [
            'type' => ['h-event'],
            'properties' => [
                'name' => ['IndieWeb Summit'],
                'url' => ['https://indieweb.org/2017'],
                'location' => [],
                'invitee' => [],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox isInvitedToEvent - false when no urls
     */
    public function testIsInvitedToEventNoUrls()
    {
        $item = [
            'type' => ['h-event'],
            'properties' => [
                'name' => ['IndieWeb Summit'],
                'url' => ['https://indieweb.org/2017'],
                'location' => [],
                'invitee' => [
                    [
                        'type' => ['h-card'],
                        'properties' => ['name' => ['Indie']],
                    ],
                ],
            ],
        ];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->isInvitedToEvent($item);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox includesPageUrl - should find matching url
     */
    public function testIncludesPageUrl()
    {
        $urls = ['https://unknown.url', 'https://indie-connector.tld'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesPageUrl($urls);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox includesPageUrl - should find no matching url
     */
    public function testIncludesPageUrlNoMatching()
    {
        $urls = ['https://unknown.url', 'https://another.url'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesPageUrl($urls);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox includesPageUrl - should find matching url with query params
     */
    public function testIncludesPageUrlWithParams()
    {
        $urls = ['https://unknown.url', 'https://indie-connector.tld?param=value'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesPageUrl($urls);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox includesPageUrl - should handle mastodon tag
     */
    public function testIncludesPageUrlWithTags()
    {
        $urls = ['tag:uuid', 'https://indie-connector.tld?param=value'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesPageUrl($urls);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox includesBaseUrl - should find matching url
     */
    public function testIncludesBaseUrl()
    {
        $urls = ['https://unknown.url', 'https://indie-connector.tld/indie-connector'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesBaseUrl($urls);

        $this->assertTrue($result);
    }

    /**
     * @group microformats
     * @testdox includesBaseUrl - should find no matching url
     */
    public function testIncludesBaseUrlNoMatching()
    {
        $urls = ['https://unknown.url', 'https://another.url'];

        $microformats = new Microformats('https://indie-connector.tld');
        $result = $microformats->includesBaseUrl($urls);

        $this->assertFalse($result);
    }

    /**
     * @group microformats
     * @testdox getAuthor - get author data from h-card
     */
    public function testGetAuthorDataHCard()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => ['Maurice Renck'],
                        'url' => ['https://maurice-renck.de'],
                        'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
                    ],
                    'value' => 'Maurice Renck',
                ],
            ],
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => 'https://example.org/photo.png',
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthor($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthor - get author data from h-entry author
     */
    public function testGetAuthorDataHEntryAuthor()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => ['https://maurice-renck.de'],
                                    'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => 'https://example.org/photo.png',
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthor($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthor - get author data from h-entry h-card child
     */
    public function testGetAuthorDataHEntryChildHcard()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'children' => [
                        [
                            'type' => ['h-card'],
                            'properties' => [
                                'name' => ['Maurice Renck'],
                                'url' => ['https://maurice-renck.de'],
                                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
                            ],
                            'value' => 'Maurice Renck',
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => 'https://example.org/photo.png',
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthor($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthor - mix author data from sources
     */
    public function testGetAuthorDataMixed()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                    'children' => [
                        [
                            'type' => ['h-card'],
                            'properties' => [
                                'name' => null,
                                'url' => ['https://maurice-renck.de'],
                                'photo' => null,
                            ],
                            'value' => 'Maurice Renck',
                        ],
                    ],
                ],
                [
                    'type' => ['h-card'],
                    'properties' => [
                        'name' => null,
                        'url' => null,
                        'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
                    ],
                    'value' => 'Maurice Renck',
                ],
            ],
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => 'https://example.org/photo.png',
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthor($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorFromHCard - get author data from h-card
     */
    public function testGetAuthorFromHCard()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
            ],
            'value' => 'Maurice Renck',
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => 'https://example.org/photo.png',
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthorFromHCard($hCard);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorFromHCard - get author data from h-card data missing
     */
    public function testGetAuthorFromHCardMissingValue()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['alt' => '']],
            ],
            'value' => 'Maurice Renck',
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => null,
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthorFromHCard($hCard);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorFromHCard - get author data from h-card data corrupt
     */
    public function testGetAuthorFromHCardCorruptValue()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
            ],
            'value' => 'Maurice Renck',
        ];

        $expected = [
            'name' => 'Maurice Renck',
            'photo' => null,
            'url' => 'https://maurice-renck.de',
            'note' => null,
        ];

        $microformats = new Microformats();

        $result = $microformats->getAuthorFromHCard($hCard);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorName - get author name from h-card
     */
    public function testGetAuthorNameFromHCard()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
            ],
            'value' => 'Maurice Renck',
        ];

        $microformats = new Microformats();
        $result = $microformats->getAuthorName($hCard);

        $this->assertEquals('Maurice Renck', $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorUrl - get author url from h-card
     */
    public function testGetAuthorUrlFromHCard()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
            ],
            'value' => 'Maurice Renck',
        ];

        $microformats = new Microformats();
        $result = $microformats->getAuthorUrl($hCard);

        $this->assertEquals('https://maurice-renck.de', $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorPhoto - get author photo from h-card
     */
    public function testGetAuthorPhotoFromHCard()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
            ],
            'value' => 'Maurice Renck',
        ];

        $microformats = new Microformats();
        $result = $microformats->getAuthorPhoto($hCard);

        $this->assertEquals('https://example.org/photo.png', $result);
    }

    /**
     * @group microformats
     * @testdox getAuthorNote - get author note from h-card
     */
    public function testGetAuthorNoteFromHCard()
    {
        $hCard = [
            'type' => ['h-card'],
            'properties' => [
                'name' => ['Maurice Renck'],
                'url' => ['https://maurice-renck.de'],
                'photo' => [['value' => 'https://example.org/photo.png', 'alt' => '']],
                'note' => ['This is a note'],
            ],
            'value' => 'Maurice Renck',
        ];

        $microformats = new Microformats();
        $result = $microformats->getAuthorNote($hCard);

        $this->assertEquals('This is a note', $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - get summary from h-entry
     */
    public function testGetSummaryFromHEntry()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = 'This is a summary';

        $microformats = new Microformats();

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - get content from h-entry
     */
    public function testGetContentFromHEntry()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => [],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [['html' => 'This is the content.', 'value' => 'This is the content']],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = 'This is the content';

        $microformats = new Microformats();

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - get summary of both filled
     */
    public function testGetSummaryFromHEntryBothFilled()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [['html' => 'This is the content.', 'value' => 'This is the content']],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = 'This is a summary';

        $microformats = new Microformats();

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - get content of summary null
     */
    public function testGetContentFromHEntrySummaryNull()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => null,
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [['html' => 'This is the content.', 'value' => 'This is the content']],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = 'This is the content';

        $microformats = new Microformats();

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - get content as html
     */
    public function testGetContentFromHEntryHtml()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => null,
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [
                            ['html' => 'This is <strong>the</strong> content', 'value' => 'This is the content'],
                        ],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = 'This is <strong>the</strong> content';

        $microformats = new Microformats('', true);

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getSummaryOrContent - handly empty summary and content
     */
    public function testGetContentFromHEntryBothEmpty()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => [],
                        'published' => ['2024-02-01 09:30:00'],
                        'content' => [],
                        'author' => [
                            [
                                'type' => ['h-card'],
                                'properties' => [
                                    'name' => ['Maurice Renck'],
                                    'url' => null,
                                    'photo' => null,
                                ],
                                'value' => 'Maurice Renck',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $microformats = new Microformats();

        $result = $microformats->getSummaryOrContent($mf2);
        $this->assertNull($result);
    }

    /**
     * @group microformats
     * @testdox getPublishDate - get publish date from h-entry
     */
    public function testGetPublishDateFromHEntry()
    {
        $mf2 = [
            'items' => [
                [
                    'type' => ['h-entry'],
                    'properties' => [
                        'category' => ['Kirby CMS'],
                        'summary' => ['This is a summary'],
                        'published' => ['2024-02-01 09:30:00'],
                    ],
                ],
            ],
        ];

        $expected = '2024-02-01 09:30:00';

        $microformats = new Microformats();

        $result = $microformats->getPublishDate($mf2);
        $this->assertEquals($expected, $result);
    }

    /**
     * @group microformats
     * @testdox getTitle - get name h-entry
     */
    public function testGetTitle()
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
                    ],
                ],
            ],
        ];

        $expected = 'This is my blog post';

        $microformats = new Microformats();

        $result = $microformats->getTitle($mf2);
        $this->assertEquals($expected, $result);
    }
}
