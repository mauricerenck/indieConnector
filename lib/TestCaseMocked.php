<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\File;
use Kirby\Cms\Page;
use PHPUnit\Framework\TestCase;

class TestCaseMocked extends TestCase
{
    public $localUrl = 'https://indieconnector.ddev.site';

    public function setUp(): void
    {
        parent::setUp();

        $existingPage = page('phpunit-test');
        if (!is_null($existingPage)) {
            kirby()->impersonate('kirby');
            $existingPage->delete(true);
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $existingPage = page('phpunit-test');
        if (!is_null($existingPage)) {
            kirby()->impersonate('kirby');
            $existingPage->delete(true);
        }
    }

    function getPageMock($draft = false, $content = [])
    {
        $defaultContent = [
            'Textfield' => "This field contains several urls.
https://text-field-url.tld
https://www.text-field-url.tld
http://www.text-field-url.tld
<https://www.text-field-url.tld>
https://processed-url.tld

[A linked text](https://text-field-url.tld/a-linked-text)

This one is a duplicate and should be ignored
https://text-field-url.tld",

            'Layouteditor' =>
                '[{"attrs":[],"columns":[{"blocks":[{"content":{"text":"<p>A text from a block http:\/\/www.layout-test-url.tld<\/p>"},"id":"8009a9d9-91eb-4897-b297-9e2b9c34f5a2","isHidden":false,"type":"text"}],"id":"47b89d0f-6256-429b-bcea-53fe08629652","width":"1\/2"},{"blocks":[{"content":{"text":"<p>A text from a block <a href=\"https:\/\/www.layout-url.tld\" target=\"_blank\" title=\"block url\" rel=\"noopener noreferrer\">https:\/\/www.layout-url.tld<\/a><\/p><p><\/p>"},"id":"3c65a3c5-7b47-4493-bedd-0c949561b180","isHidden":false,"type":"text"}],"id":"a8527e69-f39f-4397-8fea-1219d0875dc5","width":"1\/2"}],"id":"ee0bb9ce-3d9a-4dd7-b71f-ec8c0351f998"}]',
            'Blockeditor' =>
                '[{"content":{"text":"<p>A text from a block <a href=\"https:\/\/www.block-url.tld\" target=\"_blank\" title=\"block url\" rel=\"noopener noreferrer\">https:\/\/www.block-url.tld<\/a><\/p><p><\/p>"},"id":"3c65a3c5-7b47-4493-bedd-0c949561b180","isHidden":false,"type":"text"}]',
            'Webmentionsstatus' => true,
            'enablemastodonposting' => true,
            'Uuid' => 'abcdefghijklmnopqrstuvwxyz',
            'Mastodonimage' => '- file://mXmNs6GZfcxrgI1p',
        ];

        $pageContent = array_merge($defaultContent, $content);

        $pageMock = Page::factory([
            'blueprint' => ['phpunit'],
            'content' => $pageContent,
            'dirname' => 'phpunit-test',
            'slug' => 'phpunit-test',
            'isDraft' => $draft,
            'template' => 'phpunit',
        ]);

        File::factory([
            'parent' => $pageMock,
            'filename' => 'indieConnector.json',
            'content' => ['["https://processed-url.tld"]'],
        ]);

        return $pageMock;
    }
}
