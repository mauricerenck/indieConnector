<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;

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
     * Checks if a single page has enabled or disabled sending webmentions
     *
     * This is mostly used to simplify testing with phpunit
     * @param $page Kirby\Cms\Page a Kirby Page Object.
     * @return bool
     */
    public function pageHasEnabledWebmentions(Page $page): bool
    {
        $status = $page->webmentionsStatus();

        if (!isset($status) || $status->isEmpty()) {
            return true;
        }

        return $status->toBool();
    }

    /**
     * Checks if a single page has enabled or disabled creating posts
     *
     * This is mostly used to simplify testing with phpunit
     * @param $page Kirby\Cms\Page a Kirby Page Object.
     * @return bool
     */
    public function pageHasEnabledExternalPosting(Page $page): bool
    {
        $status = $page->enableExternalPosting();
        return !isset($status) || $status->isEmpty() || $status->toBool();
    }

    /**
     * Checks if a page fullfills a criteria to send a webmention
     *
     * @param $page Kirby\Cms\Page a Kirby Page Object
     * @param $type string webmention or post - default 'webmention'
     * @return bool
     */
    public function pageFullfillsCriteria(Page $page, ?string $type = 'webmention'): bool
    {
        if (!$this->pageHasNeededStatus($page)) {
            return false;
        }

        if ($this->templateIsBlocked($page->intendedTemplate()->name(), $type)) {
            return false;
        }

        if (!$this->templateIsAllowed($page->intendedTemplate()->name(), $type)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a template is blocked from sending
     *
     * @param $template string the intended template
     * @param $type webmention or post
     * @return bool
     */
    public function templateIsBlocked(string $template, ?string $type = 'webmention'): bool
    {
        $opt_blockedTemplates = $type === 'post'
            ? $this->options->get('post.blockedTemplates', [])
            : $this->options->get('send.blockedTemplates', []);

        return in_array($template, $opt_blockedTemplates);
    }

    /**
     * Checks if a template is allowed to send
     *
     * @param $template string the intended template
     * @param $type webmention or post
     * @return bool
     */
    public function templateIsAllowed(string $template, ?string $type = 'webmention'): bool
    {
        $opt_allowedTemplates = $type === 'post'
            ? $this->options->get('post.allowedTemplates', [])
            : $this->options->get('send.allowedTemplates', []);

        return in_array($template, $opt_allowedTemplates);
    }

    /**
     * Checks the page status
     *
     * This is mostly used to simplify testing with phpunit
     * @param $page Kirby\Cms\Page a Kirby Page Object.
     * @return bool
     */
    public function pageHasNeededStatus(Page $page): bool
    {
        return !$page->isDraft();
    }

    /**
     * Finds URLs in content fields of a Kirby Page
     *
     * @param $page Kirby\Cms\Page a Kirby Page Object.
     * @return array array of URLs.
     */
    public function findUrls(Page $page)
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
     * Scans outgoing links in a block of a $html string.
     *
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
     *
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
