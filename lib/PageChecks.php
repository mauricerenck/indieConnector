<?php

namespace mauricerenck\IndieConnector;

// TODO delete
class PageChecks
{
    public function __construct(
        private ?array $allowedTemplates = null,
        private ?array $blockedTemplates = null,
        private ?array $allowedPostTemplates = null,
        private ?array $blockedPostTemplates = null
    ) {}
}
