<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Http\Response;
use Kirby\Http\Url;

use Exception;


enum PostOutgoingStatus: string
{
    case Pending = 'PENDING';
    case Sent = 'SENT';
    case Failed = 'FAILED';
    case Aborted = 'ABORTED';
}

class PostHandler
{
    protected OptionHandler $options;
    private PageHandler $pageHandler;
    private Outbox $outbox;
    protected UrlHandler $urlHandler;

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
    }

    public function createPosts(Page $page)
    {
        try {
            if (!$this->preconditionsMet($page)) {
                return;
            }

            $pageUuid = $page->uuid()->id();
            $alreadySent = $this->alreadySentToTarget($pageUuid, 'mastodon');
            FlowDebug::step('posts', $pageUuid, 'createPosts', 'info', '$alreadySent', $alreadySent);

            if (!$alreadySent) {
                $mastodon = new Mastodon();
                $mastodonPost = $mastodon->sendPost($page);
                $this->addToSentPosts($pageUuid, $mastodonPost);
            }

            // TODO bluesky
        } catch (Exception $e) {
            FlowDebug::step('posts', '', 'createPosts', 'error', '', $e);
            return;
        }
    }

    public function addToSentPosts(string $pageUuid, array|null $postData): void
    {
        FlowDebug::step('posts', $pageUuid, 'addToSentPosts', 'info', '', $postData);
        if ($postData === false) {
            return;
        }

        $this->outbox->addPost(
            pageUuid: $pageUuid,
            postContent: $postData['text'],
            postUrl: $postData['uri'],
            postService: $postData['target'],
            status: PostOutgoingStatus::Sent
        );
    }

    public function alreadySentToTarget(string $pageUuid, string $service)
    {
        $posts = $this->outbox->getPosts($pageUuid);
        if (!$posts) {
            return false;
        }

        if ($posts->count() === 0) {
            return false;
        }

        return $posts->filterBy('service', $service)->count() > 0;
    }

    public function preconditionsMet(Page $page): bool
    {
        if (!$this->pageHandler->pageFullfillsCriteria($page, 'post')) {
            return false;
        }

        if ($this->urlHandler->isLocalUrl($page->url())) {
            return false;
        }

        if (!$this->pageHandler->pageHasEnabledExternalPosting($page)) {
            return false;
        }

        return true;
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
