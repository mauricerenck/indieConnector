<?php

namespace mauricerenck\IndieConnector;

class PageChecks
{
    public function __construct(
        private ?array $allowedTemplates = null,
        private ?array $blockedTemplates = null,
        private ?array $allowedPostTemplates = null,
        private ?array $blockedPostTemplates = null
    ) {
        $this->allowedTemplates = $allowedTemplates ?? option('mauricerenck.indieConnector.allowedTemplates', []);
        $this->blockedTemplates = $blockedTemplates ?? option('mauricerenck.indieConnector.blockedTemplates', []);

        $this->allowedPostTemplates =
            $allowedPosseTemplates ?? option('mauricerenck.indieConnector.post.allowedTemplates', []);
        $this->blockedPostTemplates =
            $blockedPosseTemplates ?? option('mauricerenck.indieConnector.post.blockedTemplates', []);
    }

    public function pageFullfillsCriteria($page)
    {
        if (!$this->pageHasNeededStatus($page)) {
            return false;
        }

        if (!$this->pageHasEnabledWebmentions($page)) {
            return false;
        }

        if ($this->templateIsBlocked($page->intendedTemplate())) {
            return false;
        }

        if (!$this->templateIsAllowed($page->intendedTemplate())) {
            return false;
        }

        return true;
    }

    public function pageHasNeededStatus($page)
    {
        return !$page->isDraft();
    }

    public function templateIsAllowed($template, ?string $type = 'webmention')
    {
        if ($type === 'post') {
            return in_array($template, $this->allowedPostTemplates) || count($this->allowedPostTemplates) === 0;
        }

        return in_array($template, $this->allowedTemplates) || count($this->allowedTemplates) === 0;
    }

    public function templateIsBlocked($template, ?string $type = 'webmention')
    {
        if ($type === 'post') {
            return in_array($template, $this->blockedPostTemplates);
        }
        return in_array($template, $this->blockedTemplates);
    }

    public function pageHasEnabledWebmentions($page)
    {
        $status = $page->webmentionsStatus();

        if (!isset($status) || $status->isEmpty()) {
            return true;
        }

        return $status->toBool();
    }

    public function pageHasEnabledExternalPosting($page)
    {
        $status = $page->enableExternalPosting();
        return !isset($status) || $status->isEmpty() || $status->toBool();
    }
}
