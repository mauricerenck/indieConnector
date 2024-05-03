<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\File;
use Kirby\Data\Data;
use IndieWeb\MentionClient;
use in_array;
use array_merge;
use Exception;

class PageChecks
{
    public function __construct(private ?array $allowedTemplates = null, private ?array $blockedTemplates = null)
    {
        $this->allowedTemplates = $allowedTemplates ?? option('mauricerenck.indieConnector.allowedTemplates', []);
        $this->blockedTemplates = $blockedTemplates ?? option('mauricerenck.indieConnector.blockedTemplates', []);
    }

    public function pageFullfillsCriteria($page)
    {
        if (!$this->pageHasNeededStatus($page)) {
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

    public function templateIsAllowed($template)
    {
        return in_array($template, $this->allowedTemplates) || count($this->allowedTemplates) === 0;
    }

    public function templateIsBlocked($template)
    {
        return in_array($template, $this->blockedTemplates);
    }
}
