<?php

use mauricerenck\IndieConnector\Receiver;
use mauricerenck\IndieConnector\TestCaseMocked;

final class ReceiverTest extends TestCaseMocked
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
     * @group receiveWebmentions
     * @testdox hasValidSecret - should return true on valid secret
     */
    public function testResponseHasValidSecret()
    {
        $webmentionIo = new Receiver();
        $result = $webmentionIo->hasValidSecret($this->responseMock);

        $this->assertTrue($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox hasValidSecret - should return false on invalid secret
     */
    public function testResponseHasInvalidSecret()
    {
        $invalidMock = $this->responseMock;
        $invalidMock['secret'] = 'INVALID';

        $webmentionIo = new Receiver();
        $result = $webmentionIo->hasValidSecret($invalidMock);

        $this->assertFalse($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox hasValidSecret - should return false on invalid hook data
     */
    public function testResponseHasNoSecret()
    {
        $invalidMock = $this->responseMock;
        unset($invalidMock['secret']);

        $webmentionIo = new Receiver();
        $result = $webmentionIo->hasValidSecret($invalidMock);

        $this->assertFalse($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getPostDataUrls - should get source and target urls
     */
    public function testGetPostDataUrls()
    {
        $receive = new Receiver();
        $result = $receive->getPostDataUrls($this->responseMock);

        $expected = [
            'source' => 'https://brid-gy.appspot.com/like/twitter/mauricerenck/DUMMY/DUMMY',
            'target' => $this->localUrl . '/en/phpunit',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getPostDataUrls - should return false on missing urls
     */
    public function testGetPostDataUrlsMissing()
    {
        $receive = new Receiver();
        $result = $receive->getPostDataUrls([]);

        $this->assertFalse($result);
    }

    /**
     * @group receiveWebmentions
     * @testdox getPageFromUrl - should get phpunit page
     */
    public function testGetPageFromUrl()
    {
        $receiverUtils = new Receiver();
        $result = $receiverUtils->getPageFromUrl($this->localUrl . '/en/phpunit');

        $this->assertEquals('phpunit', $result->slug());
    }

    /**
     * @group receiveWebmentions
     * @testdox getPageFromUrl - should get translated phpunit page
     */
    public function testGetPageFromUrlWithoutTranslatedLanguage()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getPageFromUrl($this->localUrl . '/de/phpunit');

        $this->assertEquals('phpunit', $result->slug());
    }

    /**
     * @group receiveWebmentions
     * @testdox getPageFromUrl - should get phpunit page without language
     */
    public function testGetPageFromUrlWithoutLanguage()
    {
        $senderUtils = new Receiver();
        $result = $senderUtils->getPageFromUrl($this->localUrl . '/phpunit');

        $this->assertEquals('phpunit', $result->slug());
    }

    /**
     * @group receiveWebmentions
     * @testdox getPageFromUrl - should handle unknown page
     */
    public function testHandleUnkownPage()
    {
        $receiverUtils = new Receiver();
        $result = $receiverUtils->getPageFromUrl($this->localUrl . '/invalid');

        $this->assertFalse($result);
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
}
