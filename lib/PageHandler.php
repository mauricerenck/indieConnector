<?php

namespace mauricerenck\IndieConnector;

use Exception;

class PageHandler
{
    protected OptionHandler $options;

    public function __construct(
        ?OptionHandler $options = null,
    ) {
        $this->options = $options ?? new OptionHandler();
    }


    /**
     * Finds URLs in content fields of a Kirby Page
     * @param $page Kirby\Cms\Page a Kirby Page Object.
     * @return array array of URLs.
     */
    public function findUrls($page)
    {
        $opt_fieldsToParseUrls = $this->options->get('send.urlFields', ['text:text', 'description:text', 'intro:text']);

        $htmlParts = [];

        foreach ($opt_fieldsToParseUrls as $field) {
            $fieldInfo = explode(':', $field);
            $content = $page->content()->get($fieldInfo[0]);

            // field is not correctly configured if it couldnt be splitted into two parts
            // -> skip and continue loop
            if (count($fieldInfo) !== 2) {
                continue;
            }

            // field does not exists or is empty
            // -> skip and continue loop
            if (!$content->exists() || $content->isEmpty()) {
                continue;
            }

            // get content of different field types
            switch ($fieldInfo[1]) {
                case 'block':
                    $htmlParts[] = $content->toBlocks();
                    break;
                case 'layout':
                    $htmlParts[] = $this->parseLayoutFields($content);
                    break;
                default:
                    $htmlParts[] = $content->kirbytext();
                    break;
            }
        }

        $html = join('', $htmlParts);
        $detectedUrls = $this->findLinks($html);

        return $detectedUrls;
    }

    /**
     * Scans outgoing links in block of a $html string.
     * @param $html string html string.
     * @return array array of unique links or empty.
     */
    public static function findLinks(string $html): array
    {
        try {
            if (!is_string($html)) {
                return [];
            }

            preg_match_all("/<a[^>]+href=.(https?:\/\/[^'\"]+)/i", $html, $matches);
            $links = $matches[1];
            $normalizedLinks = array_map(function ($url) {

                $url = rtrim($url, '/'); // Remove trailing slash
                $url = str_replace('http://', 'https://', $url); // always use https

                return $url;
            }, $links);

            return array_unique($normalizedLinks);
        } catch (Exception $_e) {
            return [];
        }
    }

    /**
     * Parses a Kirby Layout field and returns its HTML
     * @param $content Kirby layout field
     * @return string of html
     */
    private function parseLayoutFields($content)
    {
        $htmlParts = [];
        foreach ($content->toLayouts() as $layout) {
            foreach ($layout->columns() as $column) {
                $htmlParts[] = $column->blocks();
            }
        }

        return join('', $htmlParts);
    }
}
