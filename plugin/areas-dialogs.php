<?php

namespace mauricerenck\IndieConnector;

use Exception;

return [
    'icShare/(:any)' => [
        'load' => function (string $id) {
            $page = page('page://' . $id);

            if (!$page) {
                return true;
            }

            $externalPostSender = new ExternalPostSender();
            $fields = $externalPostSender->getServicesDialogFields($page);
            $defaultText = $externalPostSender->getTextFieldContent($page, 500);

            return [
                'component' => 'k-form-dialog',
                'props' => [
                    'size' => 'large',
                    'fields' => $fields,
                    'value' => [
                        'text' => $defaultText,
                        'skipUrl' => $page->icSkipUrl()->toBool(),

                    ],
                    'submitButton' => [
                        'icon' => 'share',
                        'text' => 'Publish post',
                        'theme' => 'green'
                    ],
                ],
            ];
        },
        'submit' => function (string $id) {
            $text = get('text');
            $skipUrl = get('skipUrl');
            $services = get('services');

            $page = page('page://' . $id);

            if (!$page) {
                return true;
            }

            if (!is_array($services)) {
                return true;
            }

            try {
                $postResults = [];
                $sender = new Sender();

                if ($page->isSkipUrl()->toBool() !== $skipUrl) {
                    $page = $page->update(['icSkipUrl' => $skipUrl]);
                }

                if (in_array('mastodon', $services)) {
                    $mastodonSender = new MastodonSender();
                    $mastodonPost = $mastodonSender->sendPost($page, $text);

                    if ($mastodonPost !== false) {
                        $postResults[] = $mastodonPost;
                    }
                }

                if (in_array('bluesky', $services)) {
                    $blueskySender = new BlueskySender();
                    $blueskyPost = $blueskySender->sendPost($page, $text);

                    if ($blueskyPost !== false) {
                        $postResults[] = $blueskyPost;
                    }
                }

                $sender->updateExternalPosts($postResults, $page);
                $sender->updateResponseCollectionUrls($postResults, $page);

                return true;
            } catch (\Exception $e) {
                return throw new Exception('Failed to send post to Mastodon: ' . $e->getMessage());
            }
        },
    ],
    'icSendWebmentions/(:any)' => [
        'load' => function () {
            return [
                'component' => 'k-text-dialog',
                'props' => [
                    'size' => 'small',
                    'text' => 'Sends Webmentions to all URLs found on this page.',
                    'submitButton' => [
                        'icon' => 'live',
                        'text' => 'Send',
                        'theme' => 'green'
                    ],
                ],
            ];
        },
        'submit' => function (string $id) {
            $page = page('page://' . $id);

            if (!$page) {
                return true;
            }

            try {
                $webmentions = new WebmentionSender();
                $webmentions->sendWebmentions($page);

                return true;
            } catch (\Exception $e) {
                return throw new Exception('Failed to send post to Mastodon: ' . $e->getMessage());
            }
        },
    ]
];
