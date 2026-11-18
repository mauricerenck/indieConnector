<?php

namespace mauricerenck\IndieConnector;

use Kirby\Cms\Page;
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
            'sources' => $this->getWebmentionSources($year, $month)
        ];
    }


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
                where: "created_at LIKE :datestring AND status IN ({$in})",
                bindings: ['datestring' => $year . '-' . $month . '-%'] + $statusBindings,
            );

            if (is_null($result)) {
                FlowDebug::step('PanelHandler', '', 'getWebmentionSources', 'info', 'No Results');
                return [];
            }

            $sources = [];
            foreach ($result->data as $webmention) {
                $host = parse_url($webmention->source_url, PHP_URL_HOST);
                $id = Str::camel(Str::replace($host, '.', ' '));

                if (!isset($sources[$id])) {
                    $sources[$id] = [
                        'host' => $host,
                        'title' => $host,
                        'source_url' => $webmention->source_url,
                        'source_name' => !empty($webmention->source_name) ? $webmention->source_name : $host,
                        'source_profile_url' => !empty($webmention->source_profile_url) ? $webmention->source_profile_url : $host,
                        'source_avatar' => $webmention->source_avatar,
                        'likes' => 0,
                        'replies' => 0,
                        'reposts' => 0,
                        'mentions' => 0,
                        'bookmarks' => 0,
                        'sum' => 0,
                        'blocked' => $webmention->status === WebmentionIncomingStatus::Blocked->value,
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
