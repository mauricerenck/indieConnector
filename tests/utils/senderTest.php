<?php

use mauricerenck\IndieConnector\SenderUtils;
use PHPUnit\Framework\TestCase;
use Kirby\Cms;
use c;

final class senderTest extends TestCase
{
    public function testShouldFindUrls()
    {
        $page = page('phpunit');
        $senderUtils = new SenderUtils($page);
        $urls = $senderUtils->findUrls($page);

        // $this->assertEquals(false, $baseUtils->getPageFromSlug('fake/page'));
    }
}
