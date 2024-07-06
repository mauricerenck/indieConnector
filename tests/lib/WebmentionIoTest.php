<?php

use mauricerenck\IndieConnector\TestCaseMocked;
use mauricerenck\IndieConnector\WebmentionIo;

final class WebmentionIoTest extends TestCaseMocked
{
    private $responseMock;
    public function setUp(): void
    {
        parent::setUp();

        $postBody =
            '{
            "secret": "my-secret",
            "source": "https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY",
            "target": "' .
            $this->localUrl .
            '/en/phpunit",
            "private": false,
            "post": {
                "type": "entry",
                "author": {
                    "type": "card",
                    "name": "phpunit",
                    "photo": "https://webmention.io/avatar/pbs.twimg.com/ad4c64fb82892fa64f39b4aeecc5671b3e8b51f9265e28f4b020a49e33fce529.jpg",
                    "url": "https://twitter.com/mauricerenck"
                },
                "url": "https://twitter.com/mauricerenck/status/DUMMY#DUMMY-by-14597236",
                "published": "2022-22-02T22:22:22Z",
                "wm-received": "2022-22-02T22:22:22Z",
                "wm-id": 777837,
                "wm-source": "https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY",
                "wm-target": "' .
            $this->localUrl .
            '/en/phpunit",
                "like-of": "https://indie-connector.test:8890/en/phpunit",
                "wm-property": "like-of",
                "wm-private": false,
                "content": {
                    "text": "Hello World!"
                }
            }
        }';

        $this->responseMock = json_decode($postBody, true, 512, JSON_OBJECT_AS_ARRAY);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return like-of
     */
    public function testDetectWebmentionTypeLike()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'like-of';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('like-of', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return mention-of
     */
    public function testDetectWebmentionTypeMention()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'mention-of';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('mention-of', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return invite
     */
    public function testDetectWebmentionTypeInvite()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'invite';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('invite', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return rsvp
     */
    public function testDetectWebmentionTypeRsvp()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'rsvp';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('rsvp', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return repost-of
     */
    public function testDetectWebmentionTypeRepost()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'repost-of';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('repost-of', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return bookmark-of
     */
    public function testDetectWebmentionTypeBookmark()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'bookmark-of';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('bookmark-of', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should return in-reply-to
     */
    public function testDetectWebmentionTypeReply()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'in-reply-to';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('in-reply-to', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should handle missing property
     */
    public function testDetectWebmentionTypeMissing()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = null;

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('in-reply-to', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getWebmentionType - should handle unknown property
     */
    public function testDetectWebmentionTypeUnknown()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'confused-of';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getWebmentionType($modifiedMock);

        $this->assertEquals('in-reply-to', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getAuthor - should get author data
     */
    public function testShouldCreateAuthorArray()
    {
        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getAuthor($this->responseMock);

        $expected = [
            'type' => 'card',
            'name' => 'phpunit',
            'photo' =>
                'https://webmention.io/avatar/pbs.twimg.com/ad4c64fb82892fa64f39b4aeecc5671b3e8b51f9265e28f4b020a49e33fce529.jpg',
            'url' => 'https://twitter.com/mauricerenck',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @group webmentionIo
     * @testdox getAuthor - should get author data when mention-of
     */
    public function testShouldCreateAuthorWithSourceUrls()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['wm-property'] = 'mention-of';
        $modifiedMock['post']['author']['name'] = '';
        $modifiedMock['post']['author']['url'] = '';

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getAuthor($modifiedMock);

        $expected = [
            'type' => 'card',
            'name' => 'https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY',
            'photo' =>
                'https://webmention.io/avatar/pbs.twimg.com/ad4c64fb82892fa64f39b4aeecc5671b3e8b51f9265e28f4b020a49e33fce529.jpg',
            'url' => 'https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @group webmentionIo
     * @testdox getContent - should get content string
     */
    public function testShouldReturnContent()
    {
        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getContent($this->responseMock);

        $this->assertEquals('Hello World!', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getContent - should handle missing content
     */
    public function testShouldReturnEmptyContent()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['content'] = null;

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getContent($modifiedMock);

        $this->assertEquals('', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getPubDate - should get pub date
     */
    public function testShouldGetPublicationDate()
    {
        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getPubDate($this->responseMock);

        $this->assertEquals('2022-22-02T22:22:22Z', $result);
    }

    /**
     * @group webmentionIo
     * @testdox getPubDate - should get pub date from received param
     */
    public function testShouldGetPublicationDateFromReceivedParam()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock['post']['published'] = null;

        $webmentionIo = new WebmentionIo();
        $result = $webmentionIo->getPubDate($this->responseMock);

        $this->assertEquals('2022-22-02T22:22:22Z', $result);
    }
}
