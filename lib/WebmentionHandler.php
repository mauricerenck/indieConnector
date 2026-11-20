<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Http\Response;
use Kirby\Http\Url;

use Mf2;
use Exception;

enum WebmentionOutgoingStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Failed = 'FAILED';
    case Aborted = 'ABORTED';
}

enum WebmentionIncomingStatus: string
{
    case Pending = 'PENDING';
    case Processed = 'PROCESSED';
    case Published = 'PUBLISHED';
    case Deleted = 'DELETED';
    case PublishedDeleted = 'PUBLISHED_AND_DELETED';
    case Failed = 'FAILED';
    case Blocked = 'BLOCKED';
}

class WebmentionHandler
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
        $this->options = $options ?? new OptionHandler();
        $this->pageHandler = $pageHandler ?? new PageHandler();
        $this->outbox = $outbox ?? new Outbox();
        $this->urlHandler = $urlHandler ?? new UrlHandler();
        $this->correlationId = '';
    }


    /** SENDING **/

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
                newStatus: WebmentionOutgoingStatus::Aborted
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
                    newStatus: WebmentionOutgoingStatus::Failed
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
                    newStatus: WebmentionOutgoingStatus::Aborted
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
                    'User-Agent: IndieConnector',
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
                    newStatus: WebmentionOutgoingStatus::Failed
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
                newStatus: WebmentionOutgoingStatus::Sent
            );

            $response = $this->getDataFromSource($targetUrl);
            if ($response['code'] === 200) {

                $targetData = $this->transformSourceContent($response['html'], $targetUrl, $sourceUrl);
                FlowDebug::step('receiveWebmentions', $sourceUrl, 'sendSingleWebmention', 'info', 'received target information', [$targetData]);

                if (!is_null($targetData)) {
                    $this->outbox->updateWebmentionTarget($pageUuid, $targetUrl, [
                        'target_name' => $targetData['title'] ?? '',
                        'target_avatar' => $targetData['author']['photo'] ?? '',
                    ]);
                }
            }

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
                newStatus: WebmentionOutgoingStatus::Failed
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
     * @param Page $page as source
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
     * @param string $url
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


    /** RECEIVING **/

    /**
     * Starts processing incoming webmentions
     *
     * Performs URL and page checks and returns a Response for the webmention endpoint route
     * @param array $postData
     * @return Response response for route
     */
    public function receiveWebmention(array $postData)
    {
        if ($this->options->get('receive.enabled', true) === false) {
            FlowDebug::step('receiveWebmentions', '', 'receiveWebmention', 'info', 'Receiving disabled via options');
            return new Response('Receiving Webmentions is disabled', 'text/plain', 410); // Gone
        }

        $urls = $this->getPostDataUrls($postData);
        if ($urls === false) {
            FlowDebug::step('receiveWebmentions', '', 'receiveWebmention', 'error', 'Invalid source/target URL');
            return new Response('Receiving Webmentions is disabled', 'text/plain', 400); // Not Acceptable
        }

        $targetPage = $this->getPageFromUrl($urls['target']);
        if ($targetPage === false) {
            FlowDebug::step('receiveWebmentions', '', 'receiveWebmention', 'error', 'Target page not found');
            return new Response('Target page not found', 'text/plain', 404);
        }

        if (!$this->pageHandler->pageHasNeededStatus($targetPage)) {
            return new Response('Target page not found', 'text/plain', 404);
        }


        // check if this webmention is already registered, if not create it
        // otherwise update it
        $registeredWebmentions = $this->outbox->getIncomingWebmentionSourceUrlsByPageUuid($targetPage->uuid()->id());
        if (!in_array($urls['source'], $registeredWebmentions)) {
            FlowDebug::step('receiveWebmentions', '', 'receiveWebmention', 'info', 'creating new webmention');
            $result = $this->createEmptyWebmention($targetPage->uuid()->id(), $urls['target'], $urls['source']);

            if (!$result) {
                return new Response('Could not store webmention', 'text/plain', 500);
            }
        } else {
            FlowDebug::step('receiveWebmentions', '', 'receiveWebmention', 'info', 'Webmention already exists, updating');
            $result = $this->updateExistingWebmention($targetPage->uuid()->id(), $urls['source']);

            if (!$result) {
                return new Response('Could not store webmention', 'text/plain', 500);
            }
        }

        return new Response('Webmention received', 'text/plain', 202); // Accepted
    }

    /**
     * Creates an empty webmention which then can be processed
     *
     * @param string $pageUuid
     * @param string $targetUrl
     * @param string $sourceUrl
     * @return bool
     */
    public function createEmptyWebmention(string $pageUuid, string $targetUrl, string $sourceUrl): bool
    {
        try {
            FlowDebug::step('receiveWebmentions', '', 'createEmptyWebmention', 'info', 'Creating empty webmention');
            $result = $this->outbox->addWebmentionSource($pageUuid, $targetUrl, $sourceUrl);

            if ($result === false) {
                FlowDebug::step('receiveWebmentions', '', 'createEmptyWebmention', 'error', 'Webmention not stored');
                return false;
            }

            // if  queue is enabled, do not process directly
            if ($this->options->get('queue.webmentions.incoming', false) === true) {
                FlowDebug::step('receiveWebmentions', $result, 'createEmptyWebmention', 'ok', 'Queue enabled stopping');
                // TODO hook?
            } else {
                // TODO
                $this->processIncomingWebmention($result);
            }

            return true;
        } catch (Exception $e) {
            FlowDebug::step('receiveWebmentions', '', 'createEmptyWebmention', 'error', 'could not create empty webmention', [$e]);
            return false;
        }
    }

    /**
     * Creates an an existing webmention which then can be processed
     *
     * @param string $pageUuid
     * @param string $sourceUrl
     * @return bool
     */
    public function updateExistingWebmention(string $pageUuid, string $sourceUrl): bool
    {
        try {
            FlowDebug::step('receiveWebmentions', '', 'updateExistingWebmention', 'info', 'Updating existing webmention');

            $result = $this->outbox->getIncomingWebmentionsByPageUuid($pageUuid);
            if (is_null($result)) {
                FlowDebug::step('receiveWebmentions', '', 'updateExistingWebmention', 'error', 'No Webmentions found', [$pageUuid, $result]);
                return false;
            }

            $webmention = $result->filterBy('source_url', $sourceUrl)->first();
            if (is_null($webmention)) {
                FlowDebug::step('receiveWebmentions', '', 'updateExistingWebmention', 'error', 'Webmention not found');
                return false;
            }

            // if  queue is enabled, do not process directly
            if ($this->options->get('queue.webmentions.incoming', false) === true) {
                FlowDebug::step('receiveWebmentions', $result, 'updateExistingWebmention', 'ok', 'Queue enabled stopping');
                // TODO hook?
            } else {
                $this->processIncomingWebmention($webmention->id);
            }

            return true;
        } catch (Exception $e) {
            FlowDebug::step('receiveWebmentions', '', 'createEmptyWebmention', 'error', 'could not create empty webmention', [$e]);
            return false;
        }
    }

    /**
     * Processes and already stored webmention
     *
     * @param string $webmentionId
     * @return void
     */
    public function processIncomingWebmention(string $webmentionId): void
    {
        FlowDebug::step('receiveWebmentions', $webmentionId, 'processIncomingWebmention', 'info', 'Processing single webmention');

        $webmention = $this->outbox->getIncomingWebmentionById($webmentionId);
        $currentStatus = $webmention->status();

        if (is_null($webmention)) {
            FlowDebug::step('receiveWebmentions', $webmentionId, 'processIncomingWebmention', 'error', 'Webmention not found, aborting');
            return;
        }

        // get the html of the webmention source page
        $response = $this->getDataFromSource($webmention->source_url());

        // The webmention was deleted, flag as deleted or delete
        // then quit processing
        if ($response['code'] === 410 || $response['code'] === 404) {
            FlowDebug::step('receiveWebmentions', $webmentionId, 'processIncomingWebmention', 'info', 'Webmention was deleted');

            $this->handleDeletedWebmention($webmention);
            return;
        }

        // no content received, mark as failed then quit processing
        // but only on pending state, otherwise we are updating and already have the data
        if ($response['code'] !== 200 && $currentStatus === WebmentionIncomingStatus::Pending->value) {
            FlowDebug::step('receiveWebmentions', $webmentionId, 'processIncomingWebmention', 'warn', 'Source returned no content');

            $this->outbox->updateWebmentionSourceStatus($webmentionId, WebmentionIncomingStatus::Failed);
            return;
        }

        $wmData = $this->transformSourceContent($response['html'], $webmention->source_url(), $webmention->target_url());

        // no microformats could be parsed, mark as failed then quit processing
        // but only on pending state, otherwise we are updating and already have the data
        if (is_null($wmData) && $currentStatus === WebmentionIncomingStatus::Pending->value) {
            FlowDebug::step('receiveWebmentions', $webmentionId, 'processIncomingWebmention', 'warn', 'No microformats available');

            $this->outbox->updateWebmentionSourceStatus($webmentionId, WebmentionIncomingStatus::Failed);
            return;
        }

        // if autopublish set status to published
        // otherwise to processed
        $newStatus = ($this->options->get('webmentions.receive.autoPublish', true))
            ? WebmentionIncomingStatus::Published
            : WebmentionIncomingStatus::Processed;

        // this is fine, updating
        $this->outbox->updateWebmentionSource(
            $webmentionId,
            [
                'source_name' => $wmData['author']['name'],
                'source_avatar' => $wmData['author']['photo'],
                'source_profile_url' => $wmData['author']['url'],
                'mention_type' => implode(',', $wmData['types']),
                'mention_content' => $wmData['content'],
                'mention_service' => $wmData['service'],
                'status' => $newStatus->value
            ]
        );
    }

    /**
     * Open the source URL and tries to fetch its html body
     *
     * returns an array with the status code and the html or an empty string
     * @param string $sourceUrl
     * @return array
     */
    public function getDataFromSource(string $sourceUrl): array
    {
        try {
            FlowDebug::step('receiveWebmentions', $sourceUrl, 'getDataFromSource', 'info', 'Trying to fetch source HTML', [$sourceUrl]);
            $response = Remote::request($sourceUrl, [
                'method'  => 'GET',
                'headers' => [
                    'User-Agent: IndieConnector',
                    'Content-Type: application/x-www-form-urlencoded',
                ]
            ]);

            return [
                'code' => $response->code(),
                'html' => $response->content() ?? ''
            ];
        } catch (Exception $e) {
            FlowDebug::step('receiveWebmentions', $sourceUrl, 'getDataFromSource', 'warn', 'Request failed', [$sourceUrl, $e]);

            return [
                'code' => 501,
                'html' => ''
            ];
        }
    }

    /**
     * Transforms microformats2 into an array suitable for further processing
     *
     * @param string $sourceHtml
     * @param string $sourceUrl
     * @param string $targetUrl
     * @return array|null
     */
    public function transformSourceContent(string $sourceHtml, string $sourceUrl, string $targetUrl): array|null
    {
        try {
            FlowDebug::step('receiveWebmentions', $sourceUrl, 'transformSourceContent', 'info', 'transforming');
            $mf = Mf2\parse($sourceHtml, $sourceUrl);

            if (empty($mf['items'])) {
                FlowDebug::step('receiveWebmentions', $sourceUrl, 'transformSourceContent', 'info', 'no items in microformats', [$mf]);
                return null;
            }


            // TODO der handler muss überarbeitet werden
            $mf2 = new Microformats($targetUrl);
            $data = [
                'types' => $mf2->getTypes($mf),
                'content' => $mf2->getSummaryOrContent($mf),
                'published' => $mf2->getPublishDate($mf),
                'author' => $mf2->getAuthor($mf),
                'title' => $mf2->getTitle($mf),
                'service' => $mf2->getService($mf),
            ];

            return $data;
        } catch (Exception $e) {
            FlowDebug::step('receiveWebmentions', $sourceUrl, 'transformSourceContent', 'error', 'parsing microformats failed', [$e]);
            return null;
        }
    }


    /**
     * Handles deleted webmentions
     *
     * If a webmention was deleted after it was sent, handle it appropriately
     * The webmention can be flagged as delete or be deleted
     * @param mixed $webmention
     * @return void
     */
    public function handleDeletedWebmention(mixed $webmention)
    {
        if ($this->options->get('webmentions.receive.delete', false) === true) {
            // always delete webmentions from db if source is gone
            $this->outbox->deleteIncomingWebmentionById($webmention->id);
            return;
        }

        // delete if no data was processed until now
        if ($webmention->status == WebmentionIncomingStatus::Pending) {
            $this->outbox->deleteIncomingWebmentionById($webmention->id);
            return;
        }

        // mark as deleted
        $newStatus = $webmention->status == WebmentionIncomingStatus::Published
            ? WebmentionIncomingStatus::PublishedDeleted
            : WebmentionIncomingStatus::Deleted;

        $this->outbox->updateWebmentionSourceStatus($webmention->id, $newStatus);

        kirby()->trigger('indieConnector.webmention.deleted', [
            'targetUrl' => $webmention->target_url,
            'sourceUrl' => $webmention->source_url,
        ]);
    }

    /**
     * Checks the target and source URLs of an incoming Webmention
     *
     * @param array $postData
     * @return array|bool array of URLs or false
     */
    public function getPostDataUrls(array $postData): array|bool
    {
        if (!isset($postData['source'])) {
            FlowDebug::step('receiveWebmentions', '', 'getPostDataUrls', 'error', 'Source URL not set');
            return false;
        }

        if (!isset($postData['target'])) {
            FlowDebug::step('receiveWebmentions', '', 'getPostDataUrls', 'error', 'Target URL not set');
            return false;
        }

        if (!$this->urlHandler->urlIsValid($postData['source'])) {
            FlowDebug::step('receiveWebmentions', '', 'getPostDataUrls', 'warn', 'Invalid source URL');
            return false;
        }

        if (!$this->urlHandler->urlIsValid($postData['target'])) {
            FlowDebug::step('receiveWebmentions', '', 'getPostDataUrls', 'warn', 'Invalid target URL');
            return false;
        }

        if ($this->urlHandler->isBlockedSource($postData['source'])) {
            FlowDebug::step('receiveWebmentions', '', 'getPostDataUrls', 'info', 'Source is blocked');
            return false;
        }

        return [
            'source' => $postData['source'],
            'target' => $postData['target'],
        ];
    }

    /**
     * Tries to find the kirby page matching the target URL
     *
     * @param string $url
     * @return Page|bool Kirby page or false
     */
    protected function getPageFromUrl(string $url): Page|bool
    {
        $path = Url::path($url);

        if ($path == '') {
            $page = page(site()->homePageId());
        } elseif (!($page = page($path))) {
            $page = kirby()->router()->call($path, 'GET');

            if (!$page) {
                return false;
            }

            if ($page->isHomeOrErrorPage()) {
                return false;
            }
        }

        if (is_null($page)) {
            return false;
        }

        return $page;
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
