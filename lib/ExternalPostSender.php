<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

class ExternalPostSender extends Sender
{
    public function __construct(
        public ?array $textfields = null,
        public ?string $imagefield = null,
        public ?string $forceLanguage = null,
        public ?bool $usePermalinkUrl = null,
        private ?int $maxPostLength = null,
        public ?UrlChecks $urlChecks = null,
        public ?PageChecks $pageChecks = null
    ) {
        parent::__construct();

        $this->textfields = $textfields ?? option('mauricerenck.indieConnector.post.textfields', ['description']);
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);
        $this->forceLanguage = $forceLanguage ?? option('mauricerenck.indieConnector.post.forceLanguage', false);
        $this->usePermalinkUrl = $usePermalinkUrl ?? option('mauricerenck.indieConnector.post.usePermalinkUrl', false);
        $this->maxPostLength = $maxPostLength ?? 300;

        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();

        // backwards compatibility
        $singleTextfield = option('mauricerenck.indieConnector.post.textfield', false);
        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$maxPostLength && option('mauricerenck.indieConnector.mastodon-text-length', false)) {
            $this->maxPostLength = option('mauricerenck.indieConnector.mastodon-text-length');
        }
    }

    public function getTextFieldContent($page, $trimTextPosition)
    {
        $pageOfLanguage = $page->translation($this->forceLanguage);
        $content = !is_null($pageOfLanguage) ? $pageOfLanguage->content() : $page->content()->toArray();

        if (is_array($this->textfields)) {
            foreach ($this->textfields as $field) {
                if (isset($content[$field]) && !empty($content[$field])) {
                    return Str::short($content[$field], $trimTextPosition);
                }
            }
        }

        $field = $this->textfields;
        if (!is_array($this->textfields) && isset($content[$field]) && !empty($content[$field])) {
            return Str::short($content[$field], $trimTextPosition);
        }

        return Str::short($content['title'], $trimTextPosition);
    }

    public function getPostUrl($page)
    {
        $url = $page->url($this->forceLanguage);

        if ($this->usePermalinkUrl) {
            $url = $page->permalink();
        }

        return $url;
    }

    public function calculatePostTextLength(string $url)
    {
        $urlLength = Str::length($url);
        return $this->maxPostLength - $urlLength - 2;
    }

    public function updatePosts($url, $statusCode, $page, $target)
    {
        return $this->updateExternalPosts($url, $statusCode, $target, $page);
    }
}
