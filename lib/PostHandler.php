<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Content\Field;

use Exception;


enum PostOutgoingStatus: string
{
    case Scheduled = "SCHEDULED";
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

    /**
     * Creates posts for all configured services
     *
     * @param Page $page
     * @return void
     */
    public function createPosts(Page $page): void
    {
        try {
            if (!$this->preconditionsMet($page)) {
                return;
            }

            $pageUuid = $page->uuid()->id();
            $services = $this->options->get('servicesConfig');

            foreach ($services as $service => $default) {

                // only send if service is enabled
                if ($this->options->get($service . '.enabled', false) === false) {
                    continue;
                }

                if ($this->alreadySentToService($pageUuid, $service) === false) {
                    $postData = $this->collectPostData($page, $service);

                    $class = $default['class'];
                    $serviceClass = new $class();
                    $post = $serviceClass->sendPost(
                        postText: $postData['postText'],
                        images: $postData['attachments'],
                        language: $postData['language'],
                    );

                    if ($post !== false) {
                        $this->addToSentPosts($pageUuid, $post);
                    }
                }
            }
        } catch (Exception $e) {
            FlowDebug::step('posts', '', 'createPosts', 'error', '', $e);
            return;
        }
    }

    /**
     * Collects all the data needed for a specific service to post to
     *
     * @param Page $page
     * @param string $service for example 'mastodon'
     * @return array
     */
    public function collectPostData(Page $page, string $service): array
    {

        $messageBody = $this->getScheduledPostData($page, $service);

        $maxLength = $this->options->get($service . '.maxLength', 500);
        $language = $this->getPostLanguage();
        $postUrl = $this->getPostUrl($page, $language);
        $hashTags = $this->getPostTags($page);
        $postText = $this->getTextFieldContent($page, $language);

        if (is_null($messageBody) || empty($messageBody)) {
            $messageBody = $this->buildPostText(
                maxLength: $maxLength,
                text: $postText,
                hashtags: $hashTags,
                url: $postUrl
            );
        }

        $imageAttachments = $this->getImages($page);

        return [
            'postText' => $messageBody,
            'attachments' => $imageAttachments,
            'language' => $language,
        ];
    }

    public function getScheduledPostData(Page $page, string $service): ?string
    {
        $posts = $this->outbox->getPosts($page->uuid()->id());
        $post = $posts->filterBy('post_service', $service);

        if (!$post || $post->count() === 0) {
            return null;
        }

        return $post->post_content();
    }


    /**
     * Stores the data if a post has been created on a sevice
     *
     * @param string $pageUuid
     * @param ?array $postData
     * @return void
     */
    public function addToSentPosts(string $pageUuid, ?array $postData): void
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

    /**
     * Checks if a post for a specific service already exists
     *
     * @param string $pageUuid
     * @param string $service
     * @return bool
     */
    public function alreadySentToService(string $pageUuid, string $service): bool
    {
        FlowDebug::step('posts', $pageUuid, 'alreadySentToTarget', 'info', '', $service);
        $posts = $this->outbox->getPosts($pageUuid);
        if (!$posts) {
            return false;
        }

        if ($posts->count() === 0) {
            return false;
        }

        $servicePost = $posts->filterBy('post_service', $service);

        if (!$servicePost || $servicePost->count() === 0) {
            return false;
        }

        // check if post is scheduled
        if ($servicePost->status() == PostOutgoingStatus::Scheduled) {
            return false;
        }

        // check if post is still pending
        if ($servicePost->status() == PostOutgoingStatus::Pending) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a page fullfills a the criteray to create a post
     *
     * @param Page $page
     * @return bool
     */
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
     * Combines the text, url and hashtags to a post text
     *
     * Takes into consideration how long a text can be and trims it
     * Keeps markdown if configured, otherwise removes it
     * Tries to cut after a single word not within
     * @param int $maxLength
     * @param string $text
     * @param ?array $hashtags = []
     * @param ?string $url = null
     * @param ?string $ellipsis = '…'
     * @return ?string
     */
    public function buildPostText(int $maxLength, string $text, ?array $hashtags = [], ?string $url = null, ?string $ellipsis = '…'): ?string
    {
        FlowDebug::step('posts', '', 'buildPostText', 'info', 'data', [$text, $hashtags, $url]);
        $protectedLines = [];

        if ($url !== null && $url !== '') {
            $protectedLines[] = $url;
        }

        if (is_array($hashtags)) {
            $protectedLines[] = implode(' ', $hashtags);
        }

        $text = $this->options->get('post.keepMarkdown', false)
            ? $text
            : strip_tags(kirby()->kirbytext($text));

        $text = substr(trim($text), 0, $maxLength);

        $trimmed = strlen($text) > $maxLength;
        $protected = implode("\n", $protectedLines);
        $suffix = $protected !== '' && $text !== ''
            ? "\n" . $protected
            : $protected;


        // when text already trimmed, put ellipsis into calculation
        $textBudget = $trimmed
            ? $maxLength - strlen($suffix) - strlen($ellipsis)
            : $maxLength - strlen($suffix);

        if (strlen($text) <= $textBudget) {
            return $text . $suffix;
        }

        $keepLength = max(0, $textBudget);
        $trimmedText = substr($text, 0, $keepLength);
        $trimmedText = preg_replace('/\s+\S*$/u', '', $trimmedText) ?? $trimmedText; // Prefer ending at a word boundary.

        return rtrim($trimmedText) . $ellipsis . $suffix;
    }


    /**
     * Gets all images of the configured image field
     *
     * @param Page $page
     * @return ?Field
     */
    public function getImages(Page $page): ?Field
    {
        $imageField = $this->options->get('post.imagefield', false);
        if ($imageField) {
            $images = $page->$imageField();

            if ($images->exists() === false || $images->isEmpty()) {
                return null;
            }

            return $images;
        }

        return null;
    }

    /**
     * Returns the post language
     *
     * Checks for a configured prefered language or a kirby default language
     * returns null if no language is configured at all
     * @return ?string
     */
    public function getPostLanguage(): ?string
    {
        $language = null;

        if ($preferedLanguage = $this->options->get('post.prefereLanguage', false)) {
            return $preferedLanguage;
        }

        if ($defaultLanguage = kirby()->defaultLanguage()) {
            return $defaultLanguage->code();
        }

        return $language;
    }

    /**
     * Returns the URL to add to the post
     *
     * Skips URL if configured globally or for template
     * Gets the URL of a page for a the post language
     * Uses the permalink URL if configured
     * @param Page $page
     * @param ?string $language
     * @return string
     */
    public function getPostUrl(Page $page, ?string $language): string
    {
        if ($this->options->get('post.skipUrl', false) === true) {
            return '';
        }

        $normalizedIntendedTemplate = strtolower($page->intendedTemplate()->name());
        if (in_array($normalizedIntendedTemplate, $this->options->get('post.skipUrl', []))) {
            return '';
        }

        if (!is_null($page->icSkipUrl()) && $page->icSkipUrl()->isTrue()) {
            return '';
        }

        $url = $page->url($language);

        if ($this->options->get('post.usePermalinkUrl', false)) {
            $url = $page->permalink();
        }

        return $url;
    }

    /**
     * Returns all tags for the post
     *
     * Reads the tags from the configured tags field
     * Prepends a hash
     * @param Page $page
     * @return array
     */
    public function getPostTags(Page $page): array
    {
        $opt_tagsField = $this->options->get('post.tagsfield', null);
        if (is_null($opt_tagsField)) {
            return [];
        }

        if (empty($opt_tagsField)) {
            return [];
        }

        $lowercaseTagField = strtolower($opt_tagsField);

        if ($page->{$lowercaseTagField}()->isEmpty()) {
            return [];
        }

        $tags = $page->{$lowercaseTagField}()->split();

        return array_map(fn($tag): string => '#' . $tag, $tags);
    }

    /**
     * Returns the text field content for the post
     *
     * Loops through all configured fields
     * Returns content of the first filled field it finds
     * Falls back to the title of no content was found
     * @param Page $page
     * @param ?string $language
     * @return string
     */
    public function getTextFieldContent(Page $page, ?string $language): string
    {
        $textFields = $this->options->get('post.textfields', ['description']);

        $pageOfLanguage = !$language ? null : $page->translation($language);
        $content = !is_null($pageOfLanguage) ? $pageOfLanguage->content() : $page->content()->toArray();

        if (is_array($textFields)) {
            foreach ($textFields as $field) {
                $lowercaseField = strtolower($field);
                if (isset($content[$lowercaseField]) && !empty($content[$lowercaseField])) {
                    return $content[$lowercaseField];
                }
            }
        }

        $field = $textFields;
        if (!is_array($textFields) && isset($content[$field]) && !empty($content[$field])) {
            return $content[$field];
        }

        return isset($content['title']) ? $content['title'] : '';
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
