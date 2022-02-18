<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\V;
use Kirby\Data\Data;
use \IndieWeb\MentionClient;
use file_exists;
use implode;
use in_array;
use Exception;
use DomDocument;

class SenderUtils
{
    private $page;
    private $outbox;
    private $outboxPath;
    private $processed;
    private $mentionClient;
    private $fieldsToParseUrls;

    public function __construct($page)
    {
        $this->page = $page; // TODO REMOVE
        $this->outboxPath = $page->root() . '/' . option('mauricerenck.indieConnector.outboxFilename', 'indieConnector.json');
        $this->outbox = $this->getOutbox();
        $this->mentionClient = new MentionClient();
        $this->processed = [];
        $this->fieldsToParseUrls = option('mauricerenck.indieConnector.send-mention-url-fields');
    }

    public function sendingIsEnabled(): boolean
    {
        return (option('mauricerenck.indieConnector.send.webmention', false) || option('mauricerenck.indieConnector.send.mastodon', false) || option('mauricerenck.indieConnector.send.archiveorg', false));
    }

    public function pageFullfillsCriteria($page)
    {
        // check page status
        if (!$this->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->templateIsBlocked($page->intendedTemplate())) {
            return false;
        }

        if (!$this->templateIsAllowed($page->intendedTemplate())) {
            return false;
        }

        // TODO make turning on/off sendmention from the panel page possible

        return true;
    }

    public function shouldSendWebmention(): boolean
    {
        // option('mauricerenck.komments.send-mention-on-update', false)

        return true;
    }

    public function pageHasNeededStatus($page)
    {
        return !$page->isDraft();
    }

    public function templateIsAllowed($template)
    {
        $allowList = option('mauricerenck.indieConnector.allowedTemplates', []);
        return (in_array($template, $allowList) || count($allowList) === 0);
    }

    public function templateIsBlocked($template)
    {
        $blockList = option('mauricerenck.indieConnector.blockedTemplates', []);
        return (in_array($template, $blockList));
    }

    private function getOutbox()
    {
        if (!file_exists($this->outboxPath)) {
            return [];
        }

        try {
            $outbox = Data::read($this->outboxPath);
        } catch (Exception $e) {
            return [];
        }

        return $outbox;
    }

    private function writeOutbox()
    {
        Data::write($this->outboxPath, $this->processed);
    }

    private function addToProcessed($targetUrl)
    {
        $this->processed[] = $targetUrl;
    }

    public function findUrls($page)
    {
        // FOREACH sourceFields
        foreach ($this->fieldsToParseUrls as $field) {
            $test = $page->content()->get($field);

            if (!$test->exists() || $test->isEmpty()) {
                return;
            }
            var_dump($test->model());
            print_r($test);
        }
        $detectedUrls = [];
        /*
                // FIXME blocks/layouts richtig auslesen
                $parseText = ($fieldType == 'blockeditor') ? $page->content()->$sourceField()->block() : $page->content()->$sourceField()->kirbytext();

                if (!empty($parseText)) {
                    $dom = new DomDocument();
                    $dom->loadHTML('<div>' . $parseText . '</div>');

                    foreach ($dom->getElementsByTagName('a') as $item) {
                        $id = $item->getAttribute('href');
                        $url = $item->getAttribute('href');

                        if (strpos($url, '/') === 0) {
                            $url = (strpos($url, '/de/') === 0 || strpos($url, '/en/') === 0) ? substr($url, 3) : $url;
                            $linkedPage = page($url);

                            if (!is_null($linkedPage)) {
                                $url = $linkedPage->url();
                            }
                        }
                        $detectedUrls[] = ['url' => $url, 'id' => $id];
                    }
                }
        */
        return $detectedUrls;
    }

    // credits to: https://github.com/sebastiangreger/kirby3-sendmentions/blob/master/src/SendMentions.php
    // TODO REPLACE WITH findUrls
    private function parseUrls()
    {
        $contentFields = [];
        foreach ($this->fieldsToParseUrls as $fieldName) {
            $contentFields[] = $this->page->content()->$fieldName()->html();
        }

        $parseText = implode(' ', $contentFields);

        $regexUrlPattern = "#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#iS";
        if (preg_match_all($regexUrlPattern, (string) $parseText, $allUrlsInContent)) {
            return $allUrlsInContent[0];
        } else {
            return [];
        }
    }

    private function shouldPingUrl($targetUrl)
    {
        if (!V::url($targetUrl)) {
            return false;
        }

        if (in_array($targetUrl, $this->processed)) {
            return false;
        }

        foreach ($this->outbox as $outboxUrl) {
            if ($outboxUrl === $targetUrl) {
                return false;
            }
        }

        return true;
    }
}
