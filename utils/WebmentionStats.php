<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Http\Url;

class WebmentionStats
{
    private $indieDb;

    public function __construct(
        private ?array $doNotTrack = null,
        private ?IndieConnectorDatabase $indieDatabase = null
    ) {
        $this->doNotTrack = $doNotTrack ?? option('mauricerenck.indieConnector.stats.doNotTrack', ['fed.brid.gy']);

        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
    }

    public function trackMention(string $target, string $source, string $type, string $image)
    {
        if ($this->doNotTrackHost($source)) {
            return false;
        }

        $mentionDate = $this->indieDb->getFormattedDate();

        try {
            $uniqueHash = md5($target . $source . $type . $mentionDate);
            $this->indieDb->insert(
                'webmentions',
                ['id', 'mention_type', 'mention_date', 'mention_source', 'mention_target', 'mention_image'],
                [$uniqueHash, $type, $mentionDate, $source, $target, $image]
            );
            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function trackOutgoingWebmentions(array $urls, $page): void
    {
        foreach ($urls as $url) {
            $this->updateOutbox($page->uuid()->id(), $url);
        }
    }

    public function updateOutbox(string $pageUuid, string $target)
    {
        if ($this->doNotTrackHost($target)) {
            return false;
        }

        $mentionDate = $this->indieDb->getFormattedDate();

        try {
            $uniqueHash = md5($target . $pageUuid . $mentionDate);

            $this->indieDb->insert(
                'webmention_outbox',
                ['id', 'page_uuid', 'sent_date', 'target'],
                [$uniqueHash, $pageUuid, $mentionDate, $target]
            );

            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getSummaryByMonth(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                'webmentions',
                ['COUNT(id) as summary', '*'],
                'WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_type;'
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
            echo 'Query failed: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getDetailsByMonth(int $timestamp)
    {
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        try {
            $result = $this->indieDb->select(
                'webmentions',
                ['mention_date', 'mention_type', 'COUNT(mention_type) as mentions'],
                'WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_type, mention_date;'
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
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getTargets(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                'webmentions',
                ['mention_target', 'mention_type', 'COUNT(mention_type) as mentions'],
                'WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_target, mention_type;'
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

            return $targets;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getSources(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                'webmentions',
                ['mention_source', 'mention_type', 'mention_image', 'COUNT(mention_type) as mentions'],
                'WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_source, mention_type;'
            );

            $sources = [];

            foreach ($result->data as $webmention) {
                $targetHash = md5($webmention->mention_source);

                if (!isset($sources[$targetHash])) {
                    $host = parse_url($webmention->mention_source);
                    $sources[$targetHash] = [
                        'source' => $webmention->mention_source,
                        'host' => $host['host'],
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
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getSentMentions(int $year, int $month)
    {
        try {
            $month = (int) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->indieDb->select(
                'webmention_outbox',
                ['page_uuid', 'target'],
                'WHERE sent_date LIKE "' . $year . '-' . $month . '-%";'
            );

            $targets = [];

            foreach ($result->data as $webmention) {
                $page = page('page://' . $webmention->page_uuid);

                if (is_null($page)) {
                    $targets[] = [
                        'target' => $webmention->target,
                        'title' => $webmention->page_uuid,
                        'pageUrl' => '#',
                        'panelUrl' => '#',
                    ];
                    continue;
                }

                $targets[] = [
                    'target' => $webmention->target,
                    'title' => $page->title()->value(),
                    'pageUrl' => $page->url(),
                    'panelUrl' => $page->panel()->url(),
                ];
            }

            return $targets;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
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
