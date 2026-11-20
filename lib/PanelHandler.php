<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;
use Exception;

class PanelHandler
{
    private IndieConnectorDatabase $indieDb;
    protected OptionHandler $options;

    public function __construct(
        ?OptionHandler $options = null,
        ?IndieConnectorDatabase $indieDatabase = null,
    ) {
        $this->options = $options ?? new OptionHandler();
        $this->indieDb = $indieDatabase ?? new IndieConnectorDatabase();
    }

    /**
     * Generates props for the panel area
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function getWebmentionProps(string $year, string $month): array
    {
        FlowDebug::step('PanelHandler', '', 'getWebmentionProps', 'info', '');
        return [
            'year' => $year,
            'month' => $month,
            'sources' => $this->getWebmentionSources($year, $month),
            'targets' => $this->getWebmentionTargets($year, $month),
        ];
    }


    /**
     * Get all webmention sources for a year and month
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function getWebmentionSources(string $year, string $month): array
    {
        FlowDebug::step('PanelHandler', '', 'getWebmentionSources', 'info', '');

        try {
            [$in, $statusBindings] = $this->indieDb->inClause('status', [
                WebmentionIncomingStatus::Published,
                WebmentionIncomingStatus::PublishedDeleted,
                WebmentionIncomingStatus::Blocked,
            ]);

            $result = $this->indieDb->select(
                table: 'webmentions_received',
                fields: ['*'],
                where: "created_at LIKE :datestring AND status IN ({$in}) ORDER BY updated_at DESC",
                bindings: ['datestring' => $year . '-' . $month . '-%'] + $statusBindings,
            );

            if (is_null($result)) {
                FlowDebug::step('PanelHandler', '', 'getWebmentionSources', 'info', 'No Results');
                return [];
            }

            $sources = [];
            foreach ($result->data as $webmention) {
                $host = parse_url($webmention->source_url, PHP_URL_HOST);
                $id = Str::camel(Str::replace($webmention->source_url, '.', ' '));

                $targetPage = page('page://' . $webmention->page_uuid);
                if (!is_null($targetPage)) {
                    $targetPanelUrl = $targetPage->panel()->url();
                    $targetTitle = $targetPage->title()->value();
                    $targetUrl = $targetPage->url();
                }

                if (!isset($sources[$id])) {
                    $sources[$id] = [
                        'host' => $host,
                        'target_url' => $targetUrl ?? '#',
                        'target_name' => $targetTitle ?? 'unknown target',
                        'target_panel_url' => $targetPanelUrl ?? '#',
                        'source_id' => $webmention->id,
                        'source_url' => $webmention->source_url,
                        'source_name' => !empty($webmention->source_name) ? $webmention->source_name : $host,
                        'source_profile_url' => !empty($webmention->source_profile_url) ? $webmention->source_profile_url : $host,
                        'source_avatar' => $webmention->source_avatar,
                        'updated_at' => $webmention->updated_at,
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0,
                        'blocked' => $webmention->blocked == 1,
                    ];
                }

                $mentionType = $this->mentionTypeToJsonType($webmention->mention_type);
                $sources[$id][$mentionType]++;
            }

            FlowDebug::step('PanelHandler', '', 'getWebmentionSources', 'info', 'sources', $sources);
            return $sources;
        } catch (Exception $e) {
            return [];
        }
    }
    /**
     * Get all sent webmention for a year and month
     *
     * @param string $year
     * @param string $month
     * @return array
     */
    public function getWebmentionTargets(string $year, string $month): array
    {
        FlowDebug::step('PanelHandler', '', 'getWebmentionTargets', 'info', '');

        try {
            [$in, $statusBindings] = $this->indieDb->inClause('status', [
                WebmentionOutgoingStatus::Sent,
                WebmentionIncomingStatus::Pending,
                WebmentionIncomingStatus::Blocked,
            ]);

            $result = $this->indieDb->select(
                table: 'webmentions_sent',
                fields: ['*'],
                where: "created_at LIKE :datestring AND status IN ({$in}) ORDER BY updated_at DESC",
                bindings: ['datestring' => $year . '-' . $month . '-%'] + $statusBindings,
            );

            if (is_null($result)) {
                FlowDebug::step('PanelHandler', '', 'getWebmentionTargets', 'info', 'No Results');
                return [];
            }

            $sources = [];
            foreach ($result->data as $webmention) {
                $host = parse_url($webmention->target_url, PHP_URL_HOST);

                $sourcePage = page('page://' . $webmention->page_uuid);
                if (!is_null($sourcePage)) {
                    $sourcePanelUrl = $sourcePage->panel()->url();
                    $sourceTitle = $sourcePage->title()->value();
                    $sourceUrl = $sourcePage->url();
                }

                $sources[] = [
                    'host' => $host,
                    'source_url' => $sourceUrl ?? '#',
                    'source_name' => $sourceTitle ?? 'unknown page',
                    'source_panel_url' => $sourcePanelUrl ?? '#',
                    'source_id' => $webmention->id,
                    'target_url' => $webmention->target_url,
                    'target_name' => !empty($webmention->target_name) ? $webmention->target_name : $host,
                    'target_avatar' => $webmention->target_avatar,
                    'updated_at' => $webmention->updated_at,
                    'status' => $webmention->status,
                    'blocked' => $webmention->blocked == 1,
                ];
            }

            FlowDebug::step('PanelHandler', '', 'getWebmentionTargets', 'info', 'targets', $sources);
            return $sources;
        } catch (Exception $e) {
            return [];
        }
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
