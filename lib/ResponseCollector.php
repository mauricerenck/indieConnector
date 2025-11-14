<?php

namespace mauricerenck\IndieConnector;

use Kirby\Uuid\Uuid;
use Kirby\Toolkit\Str;

class ResponseCollector
{
    private $indieDb;

    public function __construct(
        private ?bool $enabled = null,
        private ?int $limit = null,
        private ?int $ttl = null,
        private ?int $queueLimit = null,
        private ?IndieConnectorDatabase $indieDatabase = null,
        private ?MastodonReceiver $mastodonReceiver = null,
        private ?BlueskyReceiver $blueskyReceiver = null,
    ) {
        $this->mastodonReceiver = $mastodonReceiver ?? new MastodonReceiver();
        $this->blueskyReceiver = $blueskyReceiver ?? new BlueskyReceiver();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
        $this->enabled = $enabled ?? option('mauricerenck.indieConnector.responses.enabled', false);
        $this->limit = $limit ?? option('mauricerenck.indieConnector.responses.limit', 10);
        $this->ttl = $ttl ?? option('mauricerenck.indieConnector.responses.ttl', 60);
        $this->queueLimit = $queueLimit ?? option('mauricerenck.indieConnector.responses.queue.limit', 50);
    }

    public function registerPostUrl(string $pageUuid, string $postUrl, string $postType): void
    {

        if (!$this->isEnabled()) {
            return;
        }

        $existingPostUrls = $this->indieDb->select(
            'external_post_urls',
            ['id'],
            'WHERE page_uuid = "' . $pageUuid . '" AND post_type = "' . $postType . '"'
        );

        if ($existingPostUrls->count() === 0) {
            $id = Uuid::generate();
            $fields = ['id', 'page_uuid', 'post_url', 'post_type', 'last_fetched'];
            $values = [$id, $pageUuid, $postUrl, $postType, '1'];

            $this->indieDb->insert('external_post_urls', $fields, $values);

            return;
        }

        foreach ($existingPostUrls->toArray() as $existingPostUrl) {
            $this->indieDb->update(
                'external_post_urls',
                ['post_url', 'last_fetched'],
                [$postUrl, '1'],
                'WHERE id = "' . $existingPostUrl->id . '" AND page_uuid = "' . $pageUuid . '" AND post_type = "' . $postType . '"'
            );
        }
    }

    public function getDuePostUrls()
    {
        $currentTimestamp = time();
        $timeToFetchAfter = $currentTimestamp - $this->ttl * 60;
        $limitQuery = $this->limit > 0 ? ' LIMIT ' . $this->limit : '';
        $query = 'SELECT GROUP_CONCAT(post_url, ",") AS post_urls, post_type FROM external_post_urls WHERE active = TRUE AND last_fetched < ' . $timeToFetchAfter . ' GROUP BY post_type ' . $limitQuery . ';';

        $postUrls = $this->indieDb->query($query);

        if (!$postUrls || $postUrls->count() === 0) {
            return [
                'urls' => 0,
                'responses' => 0
            ];
        }

        $countPostUrls = 0;
        $countResponses = 0;

        $mastodonPostUrls = $postUrls->filterBy('post_type', 'mastodon')->first(); // we only get one resultset here, so we use first()
        if (!is_null($mastodonPostUrls)) {
            $result = $this->parseMastodonResponses($mastodonPostUrls->post_urls);
            if (!is_null($result)) {
                $countResponses += $result['responses'];
                $countPostUrls += $result['urls'];
            }
        }

        $blueskyPostUrls = $postUrls->filterBy('post_type', 'bluesky')->first(); // we only get one resultset here, so we use first()
        if (!is_null($blueskyPostUrls)) {
            $result = $this->parseBlueskyResponses($blueskyPostUrls->post_urls); // we only get one resultset here, so we use first()
            if (!is_null($result)) {
                $countResponses += $result['responses'];
                $countPostUrls += $result['urls'];
            }
        }

        return [
            'urls' => $countPostUrls,
            'responses' => $countResponses
        ];
    }

    public function getPostUrlMetrics()
    {
        $query = 'SELECT COUNT(post_url) as urls, post_type FROM external_post_urls WHERE active = TRUE GROUP BY post_type;';
        $postUrls = $this->indieDb->query($query);

        if (!$postUrls || $postUrls->count() === 0) {
            return [
                'total' => 0,
                'mastodon' => 0,
                'bluesky' => 0,
                'due' => 0
            ];
        }

        $mastodonUrls = $postUrls->filterBy('post_type', 'mastodon')->first(); // we only get one resultset here, so we use first()
        $blueskyUrls = $postUrls->filterBy('post_type', 'bluesky')->first(); // we only get one resultset here, so we use first()

        $mastodonUrlCount = $mastodonUrls ? $mastodonUrls->urls : 0;
        $blueskyUrlCount = $blueskyUrls ? $blueskyUrls->urls : 0;


        $currentTimestamp = time();
        $timeToFetchAfter = $currentTimestamp - $this->ttl * 60;
        $query = 'SELECT COUNT(post_url) as urls FROM external_post_urls WHERE active = TRUE AND last_fetched < ' . $timeToFetchAfter . ';';
        $dueUrls = $this->indieDb->query($query);

        $dueUrls = $dueUrls->first();
        $dueUrlsCount = $dueUrls ? $dueUrls->urls : 0;

        return [
            'total' => $mastodonUrlCount + $blueskyUrlCount,
            'mastodon' => $mastodonUrlCount,
            'bluesky' => $blueskyUrlCount,
            'due' => $dueUrlsCount,
        ];
    }

    public function parseMastodonResponses(string $postUrls)
    {
        if (empty($postUrls)) {
            return [
                'urls' => 0,
                'responses' => 0
            ];
        }

        // get known responses
        $postUrls = explode(',', $postUrls);
        $lastResponses = $this->indieDb->query(
            'SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("' . implode('", "', $postUrls) . '") GROUP BY post_type;'
        );

        $cleanedPostUrls = $this->cleanPostUrls($postUrls, $this->mastodonReceiver);

        if (count($cleanedPostUrls['invalid']) > 0) {
            $this->disablePostUrls($cleanedPostUrls['invalid']);
        }

        $count = 0;
        $count += $this->fetchMastodonLikes($cleanedPostUrls['valid'], $lastResponses);
        $count += $this->fetchMastodonReblogs($cleanedPostUrls['valid'], $lastResponses);
        $count += $this->fetchMastodonReplies($cleanedPostUrls['valid'], $lastResponses);

        $this->updateLastFetched($postUrls);

        return [
            'urls' => count($postUrls),
            'responses' => $count
        ];
    }

    public function parseBlueskyResponses(string $postUrls)
    {

        if (empty($postUrls)) {
            return [
                'urls' => 0,
                'responses' => 0
            ];
        }

        // get known responses
        $postUrls = explode(',', $postUrls);
        $lastResponses = $this->indieDb->query(
            'SELECT GROUP_CONCAT(id, ",") AS ids, post_type FROM known_responses WHERE post_url IN ("' . implode('", "', $postUrls) . '") GROUP BY post_type;'
        );

        $cleanedPostUrls = $this->cleanPostUrls($postUrls, $this->blueskyReceiver);

        if (count($cleanedPostUrls['invalid']) > 0) {
            $this->disablePostUrls($cleanedPostUrls['invalid']);
        }

        $count = 0;
        $count += $this->fetchBlueskyLikes($cleanedPostUrls['valid'], $lastResponses);
        $count += $this->fetchBlueskyReposts($cleanedPostUrls['valid'], $lastResponses);
        $count += $this->fetchBlueskyQuotes($cleanedPostUrls['valid'], $lastResponses);
        $count += $this->fetchBlueskyReplies($cleanedPostUrls['valid'], $lastResponses);

        $this->updateLastFetched($postUrls);

        return [
            'urls' => count($postUrls),
            'responses' => $count
        ];
    }

    public function fetchMastodonLikes(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'like-of');

        foreach ($postUrls as $postUrl) {
            $favs = $this->mastodonReceiver->getResponses($postUrl, 'likes', $knownIds);

            if (count($favs) === 0) {
                continue;
            }

            $latestId = $favs[0]['id'];

            foreach ($favs as $fav) {
                if (!in_array($fav['id'], $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $fav['id'], // 'response_id' likes dont have ids use author id instead
                        responseType: 'like-of',
                        responseSource: 'mastodon',
                        responseDate: $this->currentDateTime(),
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $fav['id'],
                        authorName: $fav['display_name'],
                        authorUsername: $fav['username'],
                        authorAvatar: $fav['avatar_static'],
                        authorUrl: $fav['url']
                    );
                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'like-of');
        }

        return $count;
    }

    public function fetchMastodonReblogs(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'repost-of');

        foreach ($postUrls as $postUrl) {
            $reblogs = $this->mastodonReceiver->getResponses($postUrl, 'reposts', $knownIds);

            if (count($reblogs) === 0) {
                continue;
            }

            $latestId = $reblogs[0]['id'];

            foreach ($reblogs as $repost) {
                if (!in_array($repost['id'], $knownIds)) {

                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $repost['id'], // 'response_id' likes dont have ids use author id instead
                        responseType: 'repost-of',
                        responseSource: 'mastodon',
                        responseDate: $this->currentDateTime(),
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $repost['id'],
                        authorName: $repost['display_name'],
                        authorUsername: $repost['username'],
                        authorAvatar: $repost['avatar_static'],
                        authorUrl: $repost['url']
                    );
                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'repost-of');
        }

        return $count;
    }

    public function fetchMastodonReplies(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'in-reply-to');

        foreach ($postUrls as $postUrl) {
            $replies = $this->mastodonReceiver->getResponses($postUrl, 'replies', $knownIds);

            if (count($replies) === 0) {
                continue;
            }

            list($_urlHost, $postId) = $this->mastodonReceiver->getPostUrlData($postUrl);
            $latestId = $replies[0]['id'];

            foreach ($replies as $reply) {
                if (!in_array($reply['id'], $knownIds)) {

                    if ($reply['in_reply_to_id'] === $postId && $reply['visibility'] === 'public') {
                        $this->addToQueue(
                            postUrl: $postUrl,
                            responseId: $reply['id'],
                            responseType: 'in-reply-to',
                            responseSource: 'mastodon',
                            responseDate: $reply['created_at'],
                            responseText: $reply['content'],
                            responseUrl: $reply['url'],
                            authorId: $reply['account']['id'],
                            authorName: $reply['account']['display_name'],
                            authorUsername: $reply['account']['username'],
                            authorAvatar: $reply['account']['avatar_static'],
                            authorUrl: $reply['account']['url']
                        );

                        $count++;
                    }
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'in-reply-to');
        }

        return $count;
    }

    public function fetchBlueskyLikes(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'like-of');

        foreach ($postUrls as $postUrl) {
            $likes = $this->blueskyReceiver->getResponses($postUrl, 'likes', $knownIds);

            if (count($likes) === 0) {
                continue;
            }

            $latestId = $likes[0]->indieConnectorId;

            foreach ($likes as $like) {
                $displayName = (!empty($like->actor->displayName)) ? $like->actor->displayName : $like->actor->handle;

                if (!in_array($like->indieConnectorId, $knownIds)) {
                    $avatar = isset($like->actor->avatar) ? $like->actor->avatar : '';

                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $like->indieConnectorId,
                        responseType: 'like-of',
                        responseSource: 'bluesky',
                        responseDate: $like->createdAt,
                        responseUrl: $postUrl, // 'response_url' likes don't have a url, use post url instead
                        authorId: $like->actor->did,
                        authorName: $displayName,
                        authorUsername: $like->actor->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $like->actor->handle
                    );
                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'like-of');
        }
        return $count;
    }

    public function fetchBlueskyReposts(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'repost-of');

        foreach ($postUrls as $postUrl) {
            $reposts = $this->blueskyReceiver->getResponses($postUrl, 'reposts', $knownIds);

            if (count($reposts) === 0) {
                continue;
            }

            $latestId = $reposts[0]->indieConnectorId;
            foreach ($reposts as $repost) {
                $displayName = (!empty($repost->displayName)) ? $repost->displayName : $repost->handle;
                $avatar = isset($repost->avatar) ? $repost->avatar : '';

                if (!in_array($repost->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $repost->indieConnectorId,
                        responseType: 'repost-of',
                        responseSource: 'bluesky',
                        responseDate: $repost->createdAt,
                        responseUrl: $postUrl,
                        authorId: $repost->did,
                        authorName: $displayName,
                        authorUsername: $repost->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $repost->handle
                    );
                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'repost-of');
        }

        return $count;
    }

    public function fetchBlueskyQuotes(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'mention-of');

        foreach ($postUrls as $postUrl) {
            $quotes = $this->blueskyReceiver->getResponses($postUrl, 'quotes', $knownIds);

            if (count($quotes) === 0) {
                continue;
            }

            $latestId = $quotes[0]->indieConnectorId;
            foreach ($quotes as $quote) {
                $displayName = (!empty($quote->author->displayName)) ? $quote->author->displayName : $quote->author->handle;
                $avatar = isset($quote->author->avatar) ? $quote->author->avatar : '';

                if (!in_array($quote->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $quote->indieConnectorId, // 'response_id' likes dont have ids
                        responseType: 'mention-of',
                        responseSource: 'bluesky',
                        responseDate: $quote->record->createdAt,
                        responseText: $quote->record->text ?? '',
                        responseUrl: $quote->uri,
                        authorId: $quote->author->did,
                        authorName: $displayName,
                        authorUsername: $quote->author->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $quote->author->handle
                    );

                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'mention-of');
        }

        return $count;
    }

    public function fetchBlueskyReplies(array $postUrls, $lastResponses)
    {
        $count = 0;
        $knownIds = $this->getKnownIds($lastResponses, 'in-reply-to');

        foreach ($postUrls as $postUrl) {
            $replies = $this->blueskyReceiver->getResponses($postUrl, 'replies', $knownIds);

            if (count($replies) === 0) {
                continue;
            }

            $latestId = $replies[0]->indieConnectorId;
            foreach ($replies as $reply) {
                $displayName = (!empty($reply->post->author->displayName)) ? $reply->post->author->displayName : $reply->post->author->handle;
                $avatar = isset($reply->post->author->avatar) ? $reply->post->author->avatar : '';

                if (!in_array($reply->indieConnectorId, $knownIds)) {
                    $this->addToQueue(
                        postUrl: $postUrl,
                        responseId: $reply->indieConnectorId, // 'response_id' likes dont have ids
                        responseType: 'in-reply-to',
                        responseSource: 'bluesky',
                        responseDate: $reply->post->record->createdAt,
                        responseText: $reply->post->record->text ?? '',
                        responseUrl: $reply->post->uri,
                        authorId: $reply->post->author->did,
                        authorName: $displayName,
                        authorUsername: $reply->post->author->handle,
                        authorAvatar: $avatar,
                        authorUrl: 'https://bsky.app/profile/' . $reply->post->author->handle
                    );

                    $count++;
                } else {
                    break;
                }
            }

            $this->updateKnownReponses($postUrl, $latestId, 'in-reply-to');
        }

        return $count;
    }


    public function getKnownIds($lastResponses, $verb): array
    {
        $idList = $lastResponses->filterBy('post_type', $verb)->first();
        return !is_null($idList) ? explode(',', $idList->ids) : [];
    }

    public function addToQueue(
        string $postUrl,
        string $responseId,
        string $responseType,
        string $responseSource,
        string $responseDate,
        string $authorId,
        string $authorName,
        string $authorUsername,
        string $authorAvatar,
        string $authorUrl,
        string $responseText = '',
        string $responseUrl = '',
        string $queueStatus = 'pending',
        int $retries = 0
    ) {

        $pageData = $this->indieDb->select('external_post_urls', ['page_uuid'], 'WHERE post_url = "' . $postUrl . '"')->first();

        $fields = ['id', 'page_uuid', 'response_id', 'response_type', 'response_source', 'response_date', 'response_text', 'response_url', 'author_id', 'author_name', 'author_username', 'author_avatar', 'author_url', 'queueStatus', 'retries'];
        $id = Uuid::generate();
        $content = Str::unhtml($responseText);

        $values = [
            $id,
            $pageData->page_uuid,
            $responseId,
            $responseType,
            $responseSource,
            $responseDate,
            $content,
            $responseUrl,
            $authorId,
            $authorName,
            $authorUsername,
            $authorAvatar,
            $authorUrl,
            $queueStatus,
            $retries
        ];

        $this->indieDb->insert('queue_responses', $fields, $values);
    }

    public function processResponses()
    {
        $responses = $this->indieDb->select('queue_responses', ['*'], 'WHERE queueStatus = "pending" LIMIT ' . $this->queueLimit);
        return $responses;
    }

    public function updateLastFetched(array $postUrls)
    {
        $currentTimestamp = time();
        $this->indieDb->update('external_post_urls', ['last_fetched'], [$currentTimestamp], 'WHERE post_url IN ("' . implode('","', $postUrls) . '")');
    }

    public function disablePostUrls(array $postUrls)
    {
        $this->indieDb->update('external_post_urls', ['active'], [false], 'WHERE post_url IN ("' . implode('","', $postUrls) . '")');
    }

    public function markProcessed($responseIds)
    {
        $this->indieDb->update('queue_responses', ['queueStatus'], ['success'], 'WHERE id IN ("' . implode('","', $responseIds) . '")');
    }

    public function updateKnownReponses($postUrl, $latestId, $verb)
    {
        $selector = str_replace(['https://', 'at://'], ['', ''], $postUrl) . '_' . $verb;
        $this->indieDb->upsert('known_responses', ['id', 'post_url', 'post_type', 'post_selector'], [$latestId, $postUrl, $verb, $selector], 'post_selector', 'id = "' . $latestId . '"');
    }

    public function removeFromQueue($responseId)
    {
        $this->indieDb->update('queue_responses', ['queueStatus'], ['redirecting'], 'WHERE id = "' . $responseId . '" AND queueStatus = "success"');
    }

    public function getSingleResponse($responseId)
    {
        $response = $this->indieDb->select('queue_responses', ['*'], 'WHERE id = "' . $responseId . '"')->first();
        return $response;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    /*
    * this class helps with testing date stuff as we can hand in a fixed date
    */
    public function currentDateTime($dateTime = null)
    {
        return $dateTime ?? date('Y-m-d H:i:s');
    }

    public function cleanPostUrls(array $postUrls, $receiver): array
    {
        $validUrls = [];
        $invalidUrls = [];

        foreach ($postUrls as $url) {
            if (!$receiver->postExists($url)) {
                $invalidUrls[] = $url;
                continue;
            }

            $validUrls[] =  $url;
        }

        return [
            'valid' => $validUrls,
            'invalid' => $invalidUrls
        ];
    }

    public function getMastodonPostResponseStats(string $postUrl)
    {
        $results = ['like-of' => 0, 'repost-of' => 0, 'in-reply-to' => 0, 'mention-of' => 0];
        $stats = $this->indieDb->select('queue_responses', ['count(response_type) as response_count', 'response_type'], 'WHERE response_url = "' . $postUrl . '" GROUP BY response_type');

        if (!is_null($stats)) {
            foreach ($stats as $stat) {
                $results[$stat->response_type()] = $stat->response_count();
            }
        }

        return $results;
    }
}
