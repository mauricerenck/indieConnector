<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;

use IndieWeb\MentionClient;
use Exception;
use Throwable;

enum WebmentionStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Failed = 'FAILED';
}

class WebmentionHandler extends Sender
{
    private IndieConnectorDatabase $indieDb;
    private PageHandler $pageHandler;
    protected OptionHandler $options;

    public function __construct(
        ?IndieConnectorDatabase $indieDatabase = null,
        ?PageHandler $pageHandler = null,
        ?OptionHandler $options = null,
    ) {
        parent::__construct();

        $this->options = $options ?? new OptionHandler();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->pageHandler = $pageHandler ?? new PageHandler();
    }

    public function sendWebmentionsNew(Page $page)
    {
        if ($this->options->get('send.enabled', true) === false) {
            return false;
        }

        if ($this->pageChecks->pageHasEnabledWebmentions($page) === false) {
            return false;
        }

        if ($this->pageChecks->pageFullfillsCriteria($page, 'webmention') === false) {
            return false;
        }

        $urls = $this->getUnprocessedUrls($page);
        if (empty($urls)) {
            return;
        }
    }

    public function sendSingleWebmention() {}

    public function registerPageUrls(Page $page): array
    {
        try {
            // find all URLs in the configured fields
            $urls = $this->pageHandler->findUrls($page);

            $registeredUrls = $this->indieDb->select(
                table: 'webmentions_sent',
                fields: ['target_url'],
                where: 'page_uuid = :uuid',
                bindings: ['uuid' => $page->uuid()->id()]
            );

            if (!$registeredUrls) {
                return [];
            }

            // Get all target URLs from the database results
            $registeredUrlsArray = array_column($registeredUrls->toArray(), 'target_url');

            // Fill $diff with all URLs found that were not registered
            $newUrls = array_diff($urls, $registeredUrlsArray);

            foreach ($newUrls as $url) {
                $this->indieDb->insert(
                    table: 'webmentions_sent',
                    values: [
                        'page_uuid' => $page->uuid()->id(),
                        'target_url' => $url,
                        'source_url' => $page->url(),
                        'status' => WebmentionStatus::Pending->value,
                    ]
                );
            }
        } catch (Exception $e) {
            return [];
        }
    }
}
