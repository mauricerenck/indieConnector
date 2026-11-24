<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\PostHandler;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class PostHandlerTest extends TestCase
{
    private static OptionHandlerMock $optionHandlerMock;
    private OptionHandlerMock $optionHandler;
    private PostHandler $postHandler;

    public static function setUpBeforeClass(): void
    {
        self::$optionHandlerMock = new OptionHandlerMock([]);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->optionHandler = self::$optionHandlerMock;
        $this->optionHandler->reset();
        $this->postHandler = new PostHandler(options: $this->optionHandler);
    }

    // ======================================================================
    // Tests for PostHandler::buildPostText (Updated for 500 char limit)
    // ======================================================================

    #[Group('postHandler')]
    #[TestDox('buildPostText - should return original text')]
    public function testBuildPostTextShortTextNoAttachments()
    {
        $maxLength = 500;
        $shortText = 'Hello world, this is a short post.';

        $result = $this->postHandler->buildPostText($maxLength, $shortText, null, null);

        $this->assertEquals($shortText, $result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should return text with url')]
    public function testBuildPostTextShortTextUrl()
    {
        $maxLength = 500;
        $shortText = 'Hello world, this is a short post.';
        $url = 'https://example.com';
        $exptected = "{$shortText}\n{$url}";

        $result = $this->postHandler->buildPostText($maxLength, $shortText, null, $url);
        $this->assertEquals($exptected, $result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should return text with hashtags')]
    public function testBuildPostTextShortTextHashtags()
    {
        $maxLength = 500;
        $shortText = 'Hello world, this is a short post.';
        $hashtags = '#hash #tag';
        $exptected = "{$shortText}\n{$hashtags}";

        $result = $this->postHandler->buildPostText($maxLength, $shortText, ['#hash', '#tag'], null);
        $this->assertEquals($exptected, $result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should return text with hashtags and url')]
    public function testBuildPostTextShortTextHashtagsAndUrl()
    {
        $maxLength = 500;
        $shortText = 'Hello world, this is a short post.';
        $url = 'https://example.com';
        $hashtags = '#hash #tag';
        $exptected = "{$shortText}\n{$url}\n{$hashtags}";

        $result = $this->postHandler->buildPostText($maxLength, $shortText, ['#hash', '#tag'], $url);
        $this->assertEquals($exptected, $result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should trim original text')]
    public function testBuildPostTextLongTextNoAttachments()
    {
        $maxLength = 500;
        $longText = 'Nostrud adipisicing Lorem tempor sit veniam. Aliqua sit reprehenderit eu eiusmod nisi quis qui pariatur laboris laborum tempor culpa fugiat irure. Cupidatat enim labore commodo adipisicing et excepteur. Ut adipisicing Lorem id tempor ipsum exercitation nisi sint commodo magna pariatur. Culpa nostrud ea qui pariatur deserunt id exercitation ullamco in. Cillum ut laborum incididunt esse tempor adipisicing aliquip veniam. Aliquip ea voluptate ad non quis pariatur. Qui officia velit non Lorem est ullamco elit proident pariatur officia et veniam deserunt. Elit ut ipsum enim nisi est do pariatur ea ullamco. Id dolor ex elit ea consectetur magna consectetur enim.';

        $result = $this->postHandler->buildPostText($maxLength, $longText, null, null);
        dump($result);
        $this->assertLessThanOrEqual($maxLength, strlen($result));
        $this->assertStringEndsWith('…', $result);
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should trim original text with hashtags')]
    public function testBuildPostTextLongTextHashtags()
    {
        $maxLength = 500;
        $longText = 'Nostrud adipisicing Lorem tempor sit veniam. Aliqua sit reprehenderit eu eiusmod nisi quis qui pariatur laboris laborum tempor culpa fugiat irure. Cupidatat enim labore commodo adipisicing et excepteur. Ut adipisicing Lorem id tempor ipsum exercitation nisi sint commodo magna pariatur. Culpa nostrud ea qui pariatur deserunt id exercitation ullamco in. Cillum ut laborum incididunt esse tempor adipisicing aliquip veniam. Aliquip ea voluptate ad non quis pariatur. Qui officia velit non Lorem est ullamco elit proident pariatur officia et veniam deserunt. Elit ut ipsum enim nisi est do pariatur ea ullamco. Id dolor ex elit ea consectetur magna consectetur enim.';
        $hashtags = '#hash #tag';

        $result = $this->postHandler->buildPostText($maxLength, $longText, ['#hash', '#tag'], null);

        $this->assertLessThanOrEqual($maxLength, strlen($result));
        $this->assertStringEndsWith($hashtags, $result);
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should trim original text with url')]
    public function testBuildPostTextLongTextUrl()
    {
        $maxLength = 500;
        $longText = 'Nostrud adipisicing Lorem tempor sit veniam. Aliqua sit reprehenderit eu eiusmod nisi quis qui pariatur laboris laborum tempor culpa fugiat irure. Cupidatat enim labore commodo adipisicing et excepteur. Ut adipisicing Lorem id tempor ipsum exercitation nisi sint commodo magna pariatur. Culpa nostrud ea qui pariatur deserunt id exercitation ullamco in. Cillum ut laborum incididunt esse tempor adipisicing aliquip veniam. Aliquip ea voluptate ad non quis pariatur. Qui officia velit non Lorem est ullamco elit proident pariatur officia et veniam deserunt. Elit ut ipsum enim nisi est do pariatur ea ullamco. Id dolor ex elit ea consectetur magna consectetur enim.';
        $hashtags = '#hash #tag';
        $url = 'https://example.com';

        $result = $this->postHandler->buildPostText($maxLength, $longText, null, $url);

        $this->assertLessThanOrEqual($maxLength, strlen($result));
        $this->assertStringEndsWith($url, $result);
    }

    #[Group('postHandler')]
    #[TestDox('buildPostText - should trim original text with url and hashtags')]
    public function testBuildPostTextLongTextUrlAndHashtags()
    {
        $maxLength = 500;
        $longText = 'Nostrud adipisicing Lorem tempor sit veniam. Aliqua sit reprehenderit eu eiusmod nisi quis qui pariatur laboris laborum tempor culpa fugiat irure. Cupidatat enim labore commodo adipisicing et excepteur. Ut adipisicing Lorem id tempor ipsum exercitation nisi sint commodo magna pariatur. Culpa nostrud ea qui pariatur deserunt id exercitation ullamco in. Cillum ut laborum incididunt esse tempor adipisicing aliquip veniam. Aliquip ea voluptate ad non quis pariatur. Qui officia velit non Lorem est ullamco elit proident pariatur officia et veniam deserunt. Elit ut ipsum enim nisi est do pariatur ea ullamco. Id dolor ex elit ea consectetur magna consectetur enim.';
        $hashtags = '#hash #tag';
        $url = 'https://example.com';

        $result = $this->postHandler->buildPostText($maxLength, $longText, ['#hash', '#tags'], $url);

        $this->assertLessThanOrEqual($maxLength, strlen($result));
        $this->assertStringContainsString($url, $result);
        $this->assertStringContainsString($hashtags, $result);
    }
}
