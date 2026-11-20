<?php

namespace mauricerenck\IndieConnector;

use Exception;

return [
    'icReplyToWebmention/(:any)' => [
        'load' => fn(string $webmentionId) => [
            'component' => 'k-form-drawer',
            'props'     => [
                'fields' => [
                    'text' => [
                        'label'    => 'Your reply',
                        'type'     => 'textarea',
                        'size' => 'medium',
                    ],
                    'wmType' => [
                        'label' => 'Webmention type',
                        'type'  => 'select',
                        'options' => [
                            [
                                'text' => "Like",
                                'value' => "like-of"
                            ],
                            [
                                'text' => "Reply",
                                'value' => "in-reply-to"
                            ],
                            [
                                'text' => "Mention",
                                'value' => "mention-of"
                            ],
                            [
                                'text' => "Repost",
                                'value' => "repost-of"
                            ],
                            [
                                'text' => "Bookmark",
                                'value' => "bookmark-of"
                            ],
                        ]
                    ],
                ],
                'value' => ["text" => "", "wmType" => 'in-reply-to'],
            ]
        ],
        'submit' => function (string $webmentionId) {
            try {
                $outbox = new Outbox();
                $webmention = $outbox->getIncomingWebmentionById($webmentionId);

                if (!$webmention) {
                    return false;
                }

                $text   = get('text');
                $wmType = get('wmType');
                $outbox->addWebmentionTarget($webmention->page_uuid, $webmention->source_url, $webmention->target_url, $text, $wmType);

                $webmentions = new WebmentionHandler();
                $webmentions->sendSingleWebmention($webmention->target_url, $webmention->source_url, $webmention->page_uuid);

                return true;
            } catch (\Exception $e) {
                return throw new Exception('Failed to send a reply ' . $e->getMessage());
            }
        },
    ]

];
