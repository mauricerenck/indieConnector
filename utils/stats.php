<?php

namespace mauricerenck\IndieConnector;

use c;
use f;
use Db;
use date;
use is_null;
use database;
use DateTime;
use Exception;
use preg_split;
use json_decode;
use json_encode;
use str_replace;
use Kirby\Http\Url;
use Kirby\Toolkit\V;
use Kirby\Http\Remote;
use Kirby\Http\Server;
use Kirby\Toolkit\Str;

class WebmentionStats
{
    private $db;
    private $pluginPath;
    private $sqlitePath;

    public function __construct()
    {
        $this->connect();
        $this->migrate();
    }

    public function trackMention(string $target, string $source, string $type, string $image)
    {
        $trackingDate = time();
        $mentionDate = $this->formatTrackingDate($trackingDate);

        try {
            $uniqueHash = md5($target . $source . $type . $mentionDate);
            $this->db->query('INSERT INTO webmentions(id, mention_type, mention_date, mention_source, mention_target, mention_image) VALUES("' . $uniqueHash . '", "' . $type . '","' . $mentionDate . '", "' . $source . '", "' . $target . '", "' . $image . '")');

            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getSummaryByMonth(int $year, int $month)
    {
        try {
            $month = (integer) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->db->query('SELECT COUNT(id) as summary, * FROM webmentions WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_type;');
            $summary = [
                'summary' => 0,
                'likes' => 0,
                'replies' => 0,
                'reposts' => 0,
                'mentions' => 0,
                'bookmarks' => 0
            ];

            foreach ($result->data as $sum) {
                $summary['summary'] += $sum->summary;

                $mentionType = $this->mentionTypeToJsonType($sum->mention_type);
                $summary[$mentionType] = $sum->summary;
            }

            return $summary;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    public function getDetailsByMonth(int $timestamp)
    {
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        try {
            $result = $this->db->query('SELECT mention_date, COUNT(mention_type) as mentions, mention_type FROM webmentions WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_type, mention_date;');
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
            $month = (integer) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->db->query('SELECT mention_target, mention_type, COUNT(mention_type) as mentions FROM webmentions WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_target, mention_type;');
            $targets = [];

            foreach ($result->data as $webmention) {
                $targetHash = md5($webmention->mention_target);

                if (!isset($targets[$targetHash])) {
                    $page = page($webmention->mention_target);

                    $targets[$targetHash] = [
                        'slug' => '/' . $webmention->mention_target,
                        'title' => $page->title()->value(),
                        'pageUrl' => $page->url(),
                        'panelUrl' => $page->panelUrl(),
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0
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
            $month = (integer) $month;
            $month = $month < 10 ? '0' . $month : $month;

            $result = $this->db->query('SELECT mention_source, mention_type, mention_image, COUNT(mention_type) as mentions FROM webmentions WHERE mention_date LIKE "' . $year . '-' . $month . '-%" GROUP BY mention_source, mention_type;');
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
                        'sum' => 0
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

    public function getStatSummary()
    {
        if (!option('mauricerenck.indieConnector.stats', false)) {
            $error = [
                'error' => true,
                'message' => 'Webmention statistics are disabled. Enable them in your kirby config.'
            ];

            return json_encode($error);
        }

        $timestamp = time();
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $stats = new WebmentionStats();
        $summary = $stats->getSummaryByMonth($year, $month);

        return $summary;
    }

    public function getPluginVersion()
    {
        try {
            $composerString = f::read(__DIR__ . '/../composer.json');
            $composerJson = json_decode($composerString);

            $packagistResult = Remote::get('https://repo.packagist.org/p2/mauricerenck/indieconnector.json');
            $packagistJson = json_decode($packagistResult->content());
            $latestVersion = $packagistJson->packages->{'mauricerenck/indieconnector'}[0]->version;

            return [
                'local' => $composerJson->version,
                'latest' => $latestVersion,
                'updateAvailable' => $composerJson->version !== $latestVersion,
                'error' => false
            ];
        } catch (Exception $e) {
            return [
                'local' => $composerJson->version,
                'latest' => 'unkown',
                'updateAvailable' => false,
                'error' => true
            ];
        }
    }

    private function connect()
    {
        try {
            $this->sqlitePath = option('mauricerenck.indieConnector.sqlitePath');
            $this->pluginPath = str_replace('utils', '', __DIR__);

            $this->db = new Database([
                'type' => 'sqlite',
                'database' => $this->sqlitePath . 'indieConnector.sqlite',
            ]);

            return true;
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return false;
        }
    }

    private function migrate()
    {
        if (option('mauricerenck.indieConnector.stats', false)) {
            $composer = f::read(__DIR__ . '/../composer.json');
            $package = json_decode($composer);

            if (!$this->db->validateTable('settings')) {
                $migrationStructures = explode(';', f::read($this->pluginPath . 'migrations/database_1-0-0.sql'));

                foreach ($migrationStructures as $query) {
                    $this->db->execute(trim($query));
                }

                $this->db->execute("INSERT INTO settings (version) VALUES ('" . $package->version . "')");
            }
        }
    }

    private function formatTrackingDate(int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }

    private function mentionTypeToJsonType(string $type): string
    {
        switch ($type) {
            case 'LIKE': return 'likes';
            case 'REPLY': return 'replies';
            case 'REPOST': return 'reposts';
            case 'MENTION': return 'mentions';
            case 'BOOKMARK': return 'bookmarks';
            default: 'mention';
        }
    }
}
