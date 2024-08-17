<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

class ExternalPostSender extends Sender
{
    public function __construct(
        public ?array $textfields = null,
        public ?string $imagefield = null,
        private ?int $maxPostLength = null,
        public ?UrlChecks $urlChecks = null,
        public ?PageChecks $pageChecks = null
    ) {
        parent::__construct();

        $this->textfields = $textfields ?? option('mauricerenck.indieConnector.post.textfields', ['description']);
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);
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

    public function getTextFieldContent($page, $trimTextPosition) {
        if(is_array($this->textfields)) {
            foreach($this->textfields as $field) {
                if($page->$field()->exists() && $page->$field()->isNotEmpty()) {
                    return $page->$field()->value();
                }
            }
        }

        $field = $this->textfields;
        if(!is_array($this->textfields) && $page->$field()->isNotEmpty()) {
            return $page->$field()->value();
        }

        return Str::short($page->title(), $trimTextPosition);
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
