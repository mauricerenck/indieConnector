<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
use Kirby\Http\Remote;

use Mf2;
use Exception;

enum WebmentionStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Failed = 'FAILED';
    case Aborted = 'ABORTED';
}

class WebmentionHandler extends Sender
{
    protected OptionHandler $options;
    private IndieConnectorDatabase $indieDb;
    private PageHandler $pageHandler;
    private Outbox $outbox;
    protected UrlHandler $urlHandler;

    public function __construct(
        ?IndieConnectorDatabase $indieDatabase = null,
        ?PageHandler $pageHandler = null,
        ?OptionHandler $options = null,
        ?Outbox $outbox = null,
        ?UrlHandler $urlHandler = null,
    ) {
        parent::__construct();

        $this->options = $options ?? new OptionHandler();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->pageHandler = $pageHandler ?? new PageHandler();
        $this->outbox = $outbox ?? new Outbox();
        $this->urlHandler = $urlHandler ?? new UrlHandler();
    }

    /**
     * Sends webmentions to all found and pending links on a page
     *
     * @param $page Kirby\Cms\Page as source
     * @return void
     */
    public function sendWebmentionsNew(Page $page): void
    {
        if ($this->options->get('send.enabled', true) === false) {
            return;
        }

        if ($this->pageHandler->pageHasEnabledWebmentions($page) === false) {
            return;
        }

        if ($this->pageHandler->pageFullfillsCriteria($page, 'webmention') === false) {
            return;
        }

        $unprocessedUrls = $this->outbox->getPendingWebmentionTargets(pageUuid: $page->uuid()->id());
        if (count($unprocessedUrls) === 0) {
            return;
        }

        foreach ($unprocessedUrls as $url) {
            // if  queue is enabled, do not send directly
            if ($this->options->get('queue.webmentions.outgoing', true) === true) {
                continue;
            }

            $this->sendSingleWebmention($url, $page->url(), $page->uuid());
        }
    }

    /**
     * Sends a single webmentions from source to target
     *
     * @param $targetUrl string target url of the webmention
     * @param $sourceUrl string source url of the webmention
     * @param $pageUuid the uuid of the page triggering the webmention
     * @return void
     */
    public function sendSingleWebmention(string $targetUrl, string $sourceUrl, string $pageUuid): void
    {
        if (!$this->urlHandler->isValidWebmentionTarget($targetUrl)) {
            // set aborted status, will never try again
            $this->outbox->updateWebmentionTargetStatus(
                pageUuid: $pageUuid,
                targetUrl: $targetUrl,
                newStatus: WebmentionStatus::Aborted
            );

            kirby()->trigger('indieConnector.webmention.sent.error', [
                'pageUuid' => $pageUuid,
                'targetUrl' => $targetUrl,
                'sourceUrl' => $sourceUrl,
                'message' => 'Invalid webmention target'
            ]);
            return;
        }

        try {
            $endpoint = $this->discoverWebmentionEndpoint($targetUrl);

            if (is_null($endpoint)) {
                // set failed status
                $this->outbox->updateWebmentionTargetStatus(
                    pageUuid: $pageUuid,
                    targetUrl: $targetUrl,
                    newStatus: WebmentionStatus::Failed
                );

                kirby()->trigger('indieConnector.webmention.sent.error', [
                    'pageUuid' => $pageUuid,
                    'targetUrl' => $targetUrl,
                    'sourceUrl' => $sourceUrl,
                    'message' => 'No Webmention endpoint found'
                ]);
                return;
            }

            if (!$this->urlHandler->urlIsValid($endpoint)) {
                // set aborted status, will never try again
                $this->outbox->updateWebmentionTargetStatus(
                    pageUuid: $pageUuid,
                    targetUrl: $targetUrl,
                    newStatus: WebmentionStatus::Aborted
                );

                kirby()->trigger('indieConnector.webmention.sent.error', [
                    'pageUuid' => $pageUuid,
                    'targetUrl' => $targetUrl,
                    'sourceUrl' => $sourceUrl,
                    'message' => 'Invalid webmention URL'
                ]);
                return;
            }

            $response = Remote::request($endpoint, [
                'method'  => 'POST',
                'timeout' => 10,
                'headers' => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                'data' => [
                    'source' => $sourceUrl,
                    'target' => $targetUrl,
                ],
            ]);

            $accepted = $response->code() >= 200 && $response->code() < 300;

            if (!$accepted) {
                // set failed status
                $this->outbox->updateWebmentionTargetStatus(
                    pageUuid: $pageUuid,
                    targetUrl: $targetUrl,
                    newStatus: WebmentionStatus::Failed
                );

                kirby()->trigger('indieConnector.webmention.sent.error', [
                    'pageUuid' => $pageUuid,
                    'targetUrl' => $targetUrl,
                    'sourceUrl' => $sourceUrl,
                    'message' => 'Invalid webmention response from target'
                ]);

                return;
            }

            // Update outbox, set the status
            $this->outbox->updateWebmentionTargetStatus(
                pageUuid: $pageUuid,
                targetUrl: $targetUrl,
                newStatus: WebmentionStatus::Sent
            );

            // trigger hook
            kirby()->trigger('indieConnector.webmention.sent:success', [
                'pageUuid' => $pageUuid,
                'targetUrl' => $targetUrl,
                'sourceUrl' => $sourceUrl,
            ]);
        } catch (Exception $e) {

            $this->outbox->updateWebmentionTargetStatus(
                pageUuid: $pageUuid,
                targetUrl: $targetUrl,
                newStatus: WebmentionStatus::Failed
            );

            kirby()->trigger('indieConnector.webmention.sent.error', [
                'pageUuid' => $pageUuid,
                'targetUrl' => $targetUrl,
                'sourceUrl' => $sourceUrl,
                'message' => 'An unknown error occured'
            ]);

            return;
        }
    }

    /**
     * Searches for URLs on a page, registers and returns new ones
     *
     * @param $page Kirby\Cms\Page as source
     * @return array of urls or empty array
     */
    public function registerPageUrls(Page $page): array
    {
        try {
            // find all URLs in the configured fields
            $urls = $this->pageHandler->findUrls($page);

            // Get all target URLs from the database results
            $registeredUrlsArray = $this->outbox->getRegisteredWebmentionTargets(pageUuid: $page->uuid()->id());

            // Fill $diff with all URLs found that were not registered
            $newUrls = array_diff($urls, $registeredUrlsArray);

            foreach ($newUrls as $url) {
                $this->outbox->addWebmentionTarget(
                    pageUuid: $page->uuid()->id(),
                    pageUrl: $page->url(),
                    targetUrl: $url
                );
            }

            return $newUrls;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Looks up a webmention endpoint of a site
     *
     * @param $url string
     * @return ?string endpoint url or null
     */
    public function discoverWebmentionEndpoint(string $url): ?string
    {
        try {
            $response = Remote::request($url, [
                'method'  => 'GET',
                'timeout' => 5,
            ]);

            if ($response->code() >= 200 && $response->code() < 300) {
                $parsed = Mf2\parse($response->content(), $url);

                $endpoints = $parsed['rels']['webmention'] ?? [];
                return $endpoints[0] ?? null;
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }
}
