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
    private PageHandler $pageHandler;
    private Outbox $outbox;
    protected UrlHandler $urlHandler;
    private string $correlationId;

    public function __construct(
        ?PageHandler $pageHandler = null,
        ?OptionHandler $options = null,
        ?Outbox $outbox = null,
        ?UrlHandler $urlHandler = null,
    ) {
        parent::__construct();

        $this->options = $options ?? new OptionHandler();
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
    public function sendWebmentions(Page $page): void
    {
        $this->correlationId = $page->uuid()->id();
        FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'info');

        if ($this->options->get('send.enabled', true) === false) {
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'info', 'Sending disabled via options');
            return;
        }

        if ($this->pageHandler->pageHasEnabledWebmentions($page) === false) {
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'info', 'Sending disabled via page blueprint');
            return;
        }

        if ($this->pageHandler->pageFullfillsCriteria($page, 'webmention') === false) {
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'info', 'Page does not fullfill criteria');
            return;
        }

        $unprocessedUrls = $this->outbox->getPendingWebmentionTargets(pageUuid: $page->uuid()->id());
        if (count($unprocessedUrls) === 0) {
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'ok', 'No unprocessed URLs stopping');
            return;
        }

        FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'ok', 'Found unprocessed URLs', count($unprocessedUrls));
        foreach ($unprocessedUrls as $url) {
            // if  queue is enabled, do not send directly
            if ($this->options->get('queue.webmentions.outgoing', false) === true) {
                FlowDebug::step('sendWebmentions', $this->correlationId, 'sendWebmentions', 'ok', 'Queue enabled stopping');
                continue;
            }

            $this->sendSingleWebmention($url, $page->url(), $page->uuid()->id());
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
        FlowDebug::step('sendWebmentions', $this->correlationId, 'sendSingleWebmention', 'info');

        if (!$this->urlHandler->isValidWebmentionTarget($targetUrl)) {
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendSingleWebmention', 'warn', 'invalid target, setting aborted status');

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
            FlowDebug::step('sendWebmentions', $this->correlationId, 'sendSingleWebmention', 'info', 'discovering endpoint', ['endpoint' => $endpoint]);

            if (is_null($endpoint)) {
                FlowDebug::step('sendWebmentions', $this->correlationId, 'sendSingleWebmention', 'warn', 'no endpoint found, setting FAILED status', ['endpoint' => $endpoint]);

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
            FlowDebug::step('sendWebmentions', $page->uuid()->id(), 'WebmentionHandler:registerPageUrls', 'info');

            // find all URLs in the configured fields
            $urls = $this->pageHandler->findUrls($page);

            FlowDebug::step('sendWebmentions', $page->uuid()->id(), 'WebmentionHandler:registerPageUrls', 'info', 'found urls', $urls);

            // Get all target URLs from the database results
            $registeredUrlsArray = $this->outbox->getRegisteredWebmentionTargets(pageUuid: $page->uuid()->id());
            FlowDebug::step('sendWebmentions', $page->uuid()->id(), 'WebmentionHandler:registerPageUrls', 'info', 'already registered urls', $registeredUrlsArray);

            // Fill $diff with all URLs found that were not registered
            $newUrls = array_diff($urls, $registeredUrlsArray);
            FlowDebug::step('sendWebmentions', $page->uuid()->id(), 'WebmentionHandler:registerPageUrls', 'info', 'new urls', $newUrls);

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
            $response = $this->request($url, [
                'method'  => 'GET',
                'timeout' => 5,
            ]);

            if ($response->code() >= 200 && $response->code() < 300) {
                // HTTP Link-Header is preferred
                $fromHeader = $this->endpointFromLinkHeaders($response->headers(), $url);
                if ($fromHeader !== null) {
                    return $fromHeader;
                }

                $parsed = Mf2\parse($response->content(), $url);
                $endpoints = $parsed['rels']['webmention'] ?? [];
                return $endpoints[0] ?? null;
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Checks the request header for a webmention endpoint
     *
     * @param array $headers
     * @param string $url
     * @return ?string endpoint url or null
     */
    protected function endpointFromLinkHeaders(array $headers, string $url): ?string
    {
        foreach ($headers as $name => $value) {
            // case insensitive
            if (strtolower((string)$name) !== 'link') {
                continue;
            }

            // multiple header as array
            foreach ((array)$value as $headerLine) {
                $endpoint = $this->parseLinkHeader((string)$headerLine, $url);
                if ($endpoint !== null) {
                    return $endpoint;
                }
            }
        }

        return null;
    }

    /**
     * Parse single header and check for webmention endpoint
     *
     * @param string $header
     * @param string $url
     * @return ?string endpoint url or null
     */
    protected function parseLinkHeader(string $header, string $url): ?string
    {
        // Each entry begins with <url>, followed by ; parameters.
        if (!preg_match_all('/<([^>]*)>([^<]*)/', $header, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            [, $href, $params] = $match;

            // rel="a b" oder rel=a
            if (!preg_match('/;\s*rel\s*=\s*(?:"([^"]*)"|([^\s;,]+))/i', $params, $rel)) {
                continue;
            }

            $relValue = strtolower($rel[1] !== '' ? $rel[1] : ($rel[2] ?? ''));
            $rels = preg_split('/\s+/', trim($relValue));

            if (in_array('webmention', $rels, true)) {
                return \Mf2\resolveUrl($url, $href);
            }
        }

        return null;
    }

    /**
     * Creates and returns a Kirby Request
     *
     * This is used for easier unit testing, as we can mock this
     * @param $url string
     * @return Remote
     */
    protected function request(string $url, array $params): Remote
    {
        return Remote::request($url, $params);
    }
}
