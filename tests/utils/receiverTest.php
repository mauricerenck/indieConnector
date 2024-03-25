<?php

use mauricerenck\IndieConnector\Receiver;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;

final class receiverTest extends TestCase
{
    private $responseMock;

    protected function setUp(): void
    {
        $this->responseMock = json_decode('{
            "secret": "my-secret",
            "source": "https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY",
            "target": "https://indie-connector.test:8890/en/phpunit",
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
                "wm-target": "https://indie-connector.test:8890/en/phpunit",
                "like-of": "https://indie-connector.test:8890/en/phpunit",
                "wm-property": "like-of",
                "wm-private": false,
                "content": {
                    "text": "Hello World!"
                }
            }
            }');
    }

    public function testResponseHasValidSecret()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->hasValidSecret($this->responseMock);

        $this->assertTrue($result);
    }

    public function testResponseHasInvalidSecret()
    {
        $invalidMock = $this->responseMock;
        $invalidMock->secret = 'bogus';

        $senderUtils = new Receiver();
        $result = $senderUtils->hasValidSecret($invalidMock);

        $this->assertFalse($result);
    }

    public function testResponseHasNoSecret()
    {
        $invalidMock = $this->responseMock;
        unset($invalidMock->secret);

        $senderUtils = new Receiver();
        $result = $senderUtils->hasValidSecret($invalidMock);

        $this->assertFalse($result);
    }

    public function testResponseHasPostBody()
    {
        $invalidMock = $this->responseMock;

        $senderUtils = new Receiver();
        $result = $senderUtils->responseHasPostBody($this->responseMock);

        $this->assertTrue($result);
    }

    public function testResponseHasNoPostBody()
    {
        $invalidMock = $this->responseMock;
        unset($invalidMock->post);

        $senderUtils = new Receiver();
        $result = $senderUtils->responseHasPostBody($invalidMock);

        $this->assertFalse($result);
    }

    public function testGetTargetUrl()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getTargetUrl($this->responseMock);

        $this->assertEquals('https://indie-connector.test:8890/en/phpunit', $result);
    }

    public function testHandleInvalidTargetUrl()
    {
        $invalidMock = $this->responseMock;
        $invalidMock->target = 'INVALID';
        $senderUtils = new Receiver();
        $result = $senderUtils->getTargetUrl($invalidMock);

        $this->assertFalse($result);
    }

    public function testGetSourceUrl()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getSourceUrl($this->responseMock);

        $this->assertEquals('https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY', $result);
    }

    public function testHandleInvalidSourceUrl()
    {
        $invalidMock = $this->responseMock;
        $invalidMock->source = 'INVALID';

        $senderUtils = new Receiver();
        $result = $senderUtils->getSourceUrl($invalidMock);

        $this->assertFalse($result);
    }

    public function testGetPageFromUrl()
    {
        $receiverUtils = new Receiver();
        $result = $receiverUtils->getPageFromUrl($this->responseMock->target);

        $this->assertEquals('phpunit', $result->slug());
    }

    public function testGetPageFromUrlWithoutTranslatedLanguage()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getPageFromUrl('https://dummy-url.tld/de/phpunit');

        $this->assertEquals('phpunit', $result->slug());
    }

    public function testGetPageFromUrlWithoutLanguage()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getPageFromUrl('https://dummy-url.tld/phpunit');

        $this->assertEquals('phpunit', $result->slug());
    }

    public function testHandleUnkownPage()
    {
        $receiverUtils = new Receiver();
        $result = $receiverUtils->getPageFromUrl('https://dummy-url.tld/invalid');

        $this->assertFalse($result);
    }

    public function testDetectWebmentionTypeLike()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'like-of';

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('LIKE', $result);
    }

    public function testDetectWebmentionTypeReply()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'in-reply-to';

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('REPLY', $result);
    }

    public function testDetectWebmentionTypeRepost()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'repost-of';

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('REPOST', $result);
    }

    public function testDetectWebmentionTypeMention()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'mention-of';

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('MENTION', $result);
    }

    public function testWebmentionTypeFallback()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'bogus-of';

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('REPLY', $result);
    }

    public function testWebmentionEmptyTypeFallback()
    {
        $modifiedMock = $this->responseMock;
        unset($modifiedMock->post->{'wm-property'});

        $senderUtils = new Receiver();
        $result = $senderUtils->getWebmentionType($modifiedMock);

        $this->assertEquals('MENTION', $result);
    }

    public function testTwitterIsKnownNetwork()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->isKnownNetwork('https://twitter.com/mauricerenck');

        $this->assertTrue($result);
    }

    public function testInstagramIsKnownNetwork()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->isKnownNetwork('https://instagram.com/mauricerenck');

        $this->assertTrue($result);
    }

    public function testMastodonIsKnownNetwork()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->isKnownNetwork('https://mastodon.online/mauricerenck');
        $this->assertTrue($result);

        $result = $senderUtils->isKnownNetwork('https://mastodon.social/mauricerenck');
        $this->assertTrue($result);
    }

    public function testFacebookIsEvilDontUseIt()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->isKnownNetwork('https://facebook.com/mauricerenck');

        $this->assertFalse($result);
    }

    public function testShouldCreateAuthorArray()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getAuthor($this->responseMock);

        $expected = [
            'type' => 'card',
            'name' => 'phpunit',
            'avatar' => 'https://webmention.io/avatar/pbs.twimg.com/ad4c64fb82892fa64f39b4aeecc5671b3e8b51f9265e28f4b020a49e33fce529.jpg',
            'url' => 'https://twitter.com/mauricerenck',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testShouldCreateAuthorWithSourceUrls()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->{'wm-property'} = 'mention-of';
        $modifiedMock->post->author->name = '';
        $modifiedMock->post->author->url = '';

        $senderUtils = new Receiver();
        $result = $senderUtils->getAuthor($this->responseMock);

        $expected = [
            'type' => 'card',
            'name' => 'https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY',
            'avatar' => 'https://webmention.io/avatar/pbs.twimg.com/ad4c64fb82892fa64f39b4aeecc5671b3e8b51f9265e28f4b020a49e33fce529.jpg',
            'url' => 'https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testShouldReturnContent()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getContent($this->responseMock);

        $this->assertEquals('Hello World!', $result);
    }

    public function testShouldReturnEmptyContent()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->content = '';

        $senderUtils = new Receiver();
        $result = $senderUtils->getContent($modifiedMock);

        $this->assertEquals('', $result);
    }

    public function testShouldGetPublicationDate()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getPubDate($this->responseMock);

        $this->assertEquals('2022-22-02T22:22:22Z', $result);
    }

    public function testShouldGetPublicationDateFromReceivedParam()
    {
        $modifiedMock = $this->responseMock;
        $modifiedMock->post->published = null;

        $senderUtils = new Receiver();
        $result = $senderUtils->getPubDate($this->responseMock);

        $this->assertEquals('2022-22-02T22:22:22Z', $result);
    }
}
