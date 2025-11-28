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
            $defaultText = $externalPostSender->getTextFieldContent($page);

            $isDraft = $page->isDraft();

            return [
                'component' => 'k-form-dialog',
                'props' => [
                    'size' => 'large',
                    'fields' => $fields,
                    'value' => [
                        'text' => $defaultText,
                        'skipUrl' => $page->icSkipUrl()->toBool(),
                        'savePostText' => $isDraft,
                    ],
                    'submitButton' => [
                        'icon' => $isDraft ? 'clock' : 'share',
                        'text' => $isDraft ? 'Send on publish' : 'Publish post',
                        'theme' => $isDraft ? 'green-icon' : 'green'
                    ],
                ],
            ];
        },
        'submit' => function (string $id) {
            $text = get('text');
            $skipUrl = get('skipUrl');
            $overwriteField = get('overwriteField');
            $savePostText = get('savePostText');
            $services = get('services');

            $overwriteField = option('mauricerenck.indieConnector.post.textfields', ['description']);
            $overwriteField = (is_array($overwriteField)) ? $overwriteField[0] : $overwriteField;

            $page = page('page://' . $id);

            if (!$page) {
                return true;
            }

            $isDraft = $page->isDraft();

            if (!is_array($services) && !$isDraft) {
                return true;
            }

            try {
                if ($page->isSkipUrl()->toBool() !== $skipUrl) {
                    $page = $page->update(['icSkipUrl' => $skipUrl]);
                }

                if (!$isDraft) {
                    $postResults = [];
                    $sender = new Sender();

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
                }

                if ($savePostText == true) {
                    $page = $page->update([$overwriteField => $text]);
                }

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
