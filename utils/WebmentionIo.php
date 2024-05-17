<?php

namespace mauricerenck\IndieConnector;

class WebmentionIo extends Receiver
{
    private $mentionTypes = ['like-of', 'repost-of', 'bookmark-of', 'in-reply-to', 'rsvp', 'mention-of', 'invite'];

    public function __construct(private $sourceUrl = null, private $targetUrl = null)
    {
        parent::__construct();
    }

    public function getWebmentionType($postBody)
    {
        if (!isset($postBody['post']['wm-property'])) {
            return 'in-reply-to';
        }

        if (!in_array($postBody['post']['wm-property'], $this->mentionTypes)) {
            return 'in-reply-to';
        }

        return $postBody['post']['wm-property'];
    }

    public function getAuthor($postBody)
    {
        $authorInfo = $postBody['post']['author'];
        $author = [
            'type' => isset($authorInfo['type']) && !empty($authorInfo['type']) ? $authorInfo['type'] : null,
            'name' => isset($authorInfo['name']) && !empty($authorInfo['name']) ? $authorInfo['name'] : null,
            'photo' => isset($authorInfo['photo']) && !empty($authorInfo['photo']) ? $authorInfo['photo'] : '',
            'url' => isset($authorInfo['url']) && !empty($authorInfo['url']) ? $authorInfo['url'] : null,
        ];

        if ($this->getWebmentionType($postBody) === 'mention-of') {
            $urls = $this->getPostDataUrls($postBody);

            if (is_null($author['name'])) {
                $author['name'] = $urls['source'];
            }
            if (is_null($author['url'])) {
                $author['url'] = $urls['source'];
            }
        }

        return $author;
    }

    public function getContent($postBody)
    {
        return isset($postBody['post']['content']) && isset($postBody['post']['content']['text'])
            ? $postBody['post']['content']['text']
            : '';
    }

    public function getPubDate($response)
    {
        return !is_null($response['post']['published'])
            ? $response['post']['published']
            : $response['post']['wm-received'];
    }
}
