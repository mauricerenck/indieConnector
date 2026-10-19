<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Url;
use Kirby\Toolkit\Str;

class WebmentionStats
{
    private $indieDb;

    public function __construct(
        private ?bool $statsEnabled = null,
        private ?array $doNotTrack = null,
        private ?IndieConnectorDatabase $indieDatabase = null
    ) {
        $this->statsEnabled = $statsEnabled ?? option('mauricerenck.indieConnector.stats.enabled', false);
        $this->doNotTrack = $doNotTrack ?? option('mauricerenck.indieConnector.stats.doNotTrack', ['fed.brid.gy']);
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
    }

    public function enabled(): bool
    {
        return $this->statsEnabled;
    }

    public function trackMention(
        string $target,
        string $source,
        string $type,
        ?string $image,
        ?string $author,
        ?string $authorUrl,
        ?string $title,
        ?string $service
    ) {
        if ($this->doNotTrackHost($source)) {
            return false;
        }

        if ($this->webmentionIsUpdate($source, $target)) {
            return false;
        }

        $mentionDate = $this->indieDb->getFormattedDate();

        if (is_null($author)) {
            $author = '';
        }

        if (is_null($authorUrl)) {
            $authorUrl = '';
        }

        if (is_null($image)) {
            $image = '';
        }

        if (is_null($title)) {
            $title = '';
        }

        if (is_null($service)) {
            $service = 'web';
        }

        try {
            $uniqueHash = md5($target . $source . $type . $mentionDate);
            $this->indieDb->insert(
                table: 'webmentions',
                values: [
                    'id' => $uniqueHash,
                    'mention_type' => $type,
                    'mention_date' => $mentionDate,
                    'mention_source' => $source,
                    'mention_target' => $target,
                    'mention_image' => $image,
                    'mention_service' => $service,
                    'author' => $author,
                    'author_url' => $authorUrl,
                    'title' => $title,
                ]
            );
            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function trackOutgoingWebmentions(array $urls, $page): void
    {
        if (is_null($page)) {
            return;
        }

        foreach ($urls as $url) {
            $this->updateOutbox($page->uuid()->id(), $url);
        }
    }

    public function updateOutbox(string $pageUuid, array $target)
    {
        if ($this->doNotTrackHost($target['url'])) {
            return false;
        }
        $mentionDate = $this->indieDb->getFormattedDate();

        try {
            $uniqueHash = md5($target['url'] . $pageUuid);

            $existingEntry = $this->indieDb->select(
                table: 'outbox',
                fields: ['id', 'updates', 'page_uuid'],
                where: 'page_uuid = :uuid AND target = :target',
                bindings: ['uuid' => $pageUuid, 'target' => $target['url']]
            );

            if (count($existingEntry->data) > 0) {
                $newCount = $existingEntry->data[0]->updates + 1;

                $this->indieDb->update(
                    table: 'outbox',
                    values: ['updated_at' => $mentionDate, 'updates' => $newCount, 'status' => $target['status']],
                    where: ['id = "' . $existingEntry->data[0]->id . '"']
                );
                return true;
            }

            $this->indieDb->insert(
                table: 'outbox',
                values: ['id' => $uniqueHash, 'page_uuid' => $pageUuid, 'created_at' => $mentionDate, 'target' => $target['url'], 'updates' => 1, 'status' => $target['status']],
            );

            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function updateOutboxStatus(string $id, string $status)
    {
        try {
            $this->indieDb->update(
                table: 'outbox',
                values: ['status' => $status],
                where: ['id = "' . $id . '"']
            );

            return true;
        } catch (Exception $e) {
            echo 'Database error: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getSummaryByMonth(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['COUNT(id) as summary', '*'],
                where: 'mention_date LIKE :datestring GROUP BY mention_type',
                bindings: ['datestring' => $year . '-' . $month . '-%']
            );

            $summary = [
                'summary' => 0,
                'likes' => 0,
                'replies' => 0,
                'reposts' => 0,
                'mentions' => 0,
                'bookmarks' => 0,
            ];

            foreach ($result->data as $sum) {
                $summary['summary'] += $sum->summary;

                $mentionType = $this->mentionTypeToJsonType($sum->mention_type);
                $summary[$mentionType] = $sum->summary;
            }

            return $summary;
        } catch (Exception $e) {
            // echo 'Query failed: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }

    public function getDetailsByMonth(int $timestamp)
    {
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        try {
            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['mention_date', 'mention_type', 'COUNT(mention_type) as mentions'],
                where: 'mention_date LIKE :datestring GROUP BY mention_type, mention_date',
                bindings: ['datestring' => $year . '-' . $month . '-%']

            );

            $detailedStats = [];

            foreach ($result->data as $mention) {
                $day = date('d', strtotime($mention->mention_date));

                if (!isset($detailedStats[$day])) {
                    $detailedStats[$day] = [
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($mention->mention_type);
                $detailedStats[$day][$mentionType] = $mention->mentions;
            }

            return $detailedStats;
        } catch (Exception $e) {
            // echo 'Could not connect to Database: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }

    public function getTargets(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['mention_target', 'mention_type', 'COUNT(mention_type) as mentions'],
                where: 'mention_date LIKE :datestring GROUP BY mention_target, mention_type',
                bindings: ['datestring' => $year . '-' . $month . '-%']
            );

            $targets = [];

            foreach ($result->data as $webmention) {
                $targetHash = md5($webmention->mention_target);

                if (!isset($targets[$targetHash])) {
                    $page = page($webmention->mention_target);

                    if (is_null($page)) {
                        continue;
                    }

                    $targets[$targetHash] = [
                        'slug' => '/' . $webmention->mention_target,
                        'title' => $page->title()->value(),
                        'pageUrl' => $page->url(),
                        'panelUrl' => $page->panel()->url(),
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0,
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($webmention->mention_type);
                $targets[$targetHash][$mentionType] = $webmention->mentions;
                $targets[$targetHash]['sum'] += $webmention->mentions;
            }

            // flatten array, and remove hash, so we have an array in the panel
            $targets = array_values($targets);

            return $targets;
        } catch (Exception $e) {
            // echo 'Could not connect to Database: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }

    public function getSources(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['mention_source', 'mention_type', 'mention_image', 'COUNT(mention_type) as mentions', 'author', 'title', 'author_url'],
                where: 'mention_date LIKE :datestring GROUP BY mention_source, mention_type',
                bindings: ['datestring' => $year . '-' . $month . '-%']
            );

            $sources = [];

            foreach ($result->data as $webmention) {
                $targetHash = md5($webmention->mention_source);

                if (!isset($sources[$targetHash])) {
                    $host = parse_url($webmention->mention_source, PHP_URL_HOST);

                    $sources[$targetHash] = [
                        'source' => $webmention->mention_source,
                        'host' => $host,
                        'title' => $webmention->title,
                        'author' => !empty($webmention->author) ? $webmention->author : $host,
                        'author_url' => !empty($webmention->author_url) ? $webmention->author_url : $host,
                        'image' => $webmention->mention_image,
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0,
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($webmention->mention_type);
                $sources[$targetHash][$mentionType] = $webmention->mentions;
                $sources[$targetHash]['sum'] += $webmention->mentions;
            }

            return $sources;
        } catch (Exception $e) {
            // echo 'Could not connect to Database: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }

    public function getSourceHosts(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['mention_source', 'mention_type', 'mention_image', 'COUNT(mention_type) as mentions', 'author', 'title', 'mention_service', 'author_url'],
                where: 'mention_date LIKE :datestring GROUP BY mention_source, mention_type',
                bindings: ['datestring' => $year . '-' . $month . '-%']
            );

            $sources = [];

            foreach ($result->data as $webmention) {
                $host = parse_url($webmention->mention_source, PHP_URL_HOST);
                $sourceType = 'web';

                if ($host === 'brid-gy.appspot.com' || $host === 'brid.gy') {
                    $path = parse_url($webmention->mention_source, PHP_URL_PATH);
                    $pathParts = explode('/', $path);
                    $sourceType = $pathParts[2];
                }

                if (isset($webmention->mention_service) && !empty($webmention->mention_service)) {
                    $sourceType = $webmention->mention_service;
                }

                if (!isset($sources[$sourceType])) {
                    $sources[$sourceType] = [
                        'sourceType' => ucfirst($sourceType),
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($webmention->mention_type);
                $sources[$sourceType][$mentionType] += $webmention->mentions;
                $sources[$sourceType]['sum'] += $webmention->mentions;
            }

            $sources = array_values($sources);

            return $sources;
        } catch (Exception $e) {
            return false;
        }
    }
    public function getSourceAuthors(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                table: 'webmentions',
                fields: ['id', 'mention_source', 'mention_type', 'mention_image', 'COUNT(mention_type) as mentions', 'author', 'title', 'mention_service', 'author_url'],
                where: 'mention_date LIKE :datestring GROUP BY author, mention_source, mention_type',
                bindings: ['datestring' => $year . '-' . $month . '-%']
            );

            $authors = [];

            foreach ($result->data as $webmention) {
                $host = parse_url($webmention->mention_source, PHP_URL_HOST);
                $sourceType = 'web';
                $userHandle = !empty($webmention->author) ? $webmention->author : $host;
                $index = Str::slug($userHandle);

                if ($host === 'brid-gy.appspot.com' || $host === 'ap.brid.gy' || $host === 'brid.gy') {
                    $path = parse_url($webmention->mention_source, PHP_URL_PATH);
                    $pathParts = explode('/', $path);
                    $sourceType = $pathParts[2];
                }

                if (isset($webmention->mention_service) && !empty($webmention->mention_service)) {
                    $sourceType = $webmention->mention_service;
                }

                // TODO hier nach author aufsplitten
                if (!isset($authors[$index])) {
                    $authors[$index] = [
                        'id' => $webmention->id,
                        'host' => $host,
                        'sourceType' => $sourceType,
                        'source' => $webmention->mention_source,
                        'author' => $userHandle,
                        'author_url' => $webmention->author_url,
                        'image' => $webmention->mention_image,
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($webmention->mention_type);
                $authors[$index][$mentionType] += $webmention->mentions;
            }

            $authors = array_values($authors);

            return $authors;
        } catch (Exception $e) {
            // echo 'Could not connect to Database: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }
    public function getSentMentions(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                'outbox',
                ['id', 'page_uuid', 'target', 'status', 'updates'],
                'WHERE created_at LIKE "' . $year . '-' . $month . '-%" ORDER BY created_at DESC;'
            );

            $targets = [];
            foreach ($result->data as $webmention) {
                $page = page('page://' . $webmention->page_uuid);

                $pageTitle = $webmention->page_uuid;
                $pageUrl = '#';
                $panelUrl = '#';

                if (isset($page)) {
                    $pageTitle = $page->title()->value();
                    $pageUrl = $page->url();
                    $panelUrl = $page->panel()->url();
                }

                if (!isset($targets[$webmention->page_uuid])) {
                    $targets[$webmention->page_uuid] = [
                        'page' => [
                            'source' => $webmention->page_uuid,
                            'target' => $webmention->target,
                            'title' =>  $pageTitle,
                            'pageUrl' => $pageUrl,
                            'panelUrl' => $panelUrl,
                        ],
                        'entries' => []
                    ];
                }

                $targets[$webmention->page_uuid]['entries'][] = [
                    'id' => $webmention->id,
                    'url' => $webmention->target,
                    'status' => $webmention->status,
                    'updates' => $webmention->updates
                ];
            }

            $targets = array_values($targets);
            return $targets;
        } catch (Exception $e) {
            // echo 'Could not connect to Database: ', $e->getMessage(), "\n"; FIXME results in panel output before header
            return false;
        }
    }

    public function getStatSummary()
    {
        if (!option('mauricerenck.indieConnector.stats.enabled', false)) {
            $error = [
                'error' => true,
                'message' => 'Webmention statistics are disabled. Enable them in your kirby config.',
            ];

            return json_encode($error);
        }

        $timestamp = time();
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $stats = new WebmentionStats();
        return $stats->getSummaryByMonth($year, $month);
    }

    public function doNotTrackHost(string $url)
    {
        $urlData = Url::toObject($url);
        $targetHost = $urlData->domain();

        if (!is_array($this->doNotTrack)) {
            return false;
        }

        if (count($this->doNotTrack) === 0) {
            return false;
        }

        return in_array($targetHost, $this->doNotTrack);
    }

    public function webmentionIsUpdate(string $source, string $target)
    {
        $result = $this->indieDb->select(
            table: 'webmentions',
            fields: ['mention_type'],
            where: 'mention_source = :source AND mention_target = :target',
            bindings: ['source' => $source, 'target' => $target]
        );

        return $result->num_rows > 0;
    }

    private function mentionTypeToJsonType(string $type): string
    {
        switch ($type) {
            case 'like-of':
                return 'likes';
            case 'in-reply-to':
                return 'replies';
            case 'repost-of':
                return 'reposts';
            case 'mention-of':
                return 'mentions';
            case 'bookmark-of':
                return 'bookmarks';
            default:
                return 'mention';
        }
    }
}
