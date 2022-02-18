<?php

namespace mauricerenck\IndieConnector;

use Kirby;
use \Response;

@require_once __DIR__ . '/lib/indieweb-comments.php';
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('mauricerenck/indieConnector', [
    'options' => require_once(__DIR__ . '/internal/options.php'),
    'areas' => require_once(__DIR__ . '/components/areas.php'),
    'routes' => [
        [
            'pattern' => 'indieConnector/webhook/webmentionio',
            'method' => 'POST',
            'action' => function () {
                $response = json_decode(file_get_contents('php://input'));
                $receiver = new WebmentionReceiver();
                $hookHelper = new HookHelper();
                $webmention = [];

                if ($response->secret !== option('mauricerenck.indieConnector.secret')) {
                    return new Response('Not found', 'text/plain', 404);
                }

                $targetPage = $receiver->getPageFromUrl($response->post->{'wm-target'});
                if (is_null($targetPage)) {
                    return new Response('Not found', 'text/plain', 404);
                }

                $webmention['type'] = $receiver->getWebmentionType($response->post->{'wm-property'});
                $webmention['target'] = $targetPage->id();
                $webmention['source'] = $receiver->getTransformedSourceUrl($response->post->{'wm-source'});
                $webmention['published'] = (!is_null($response->post->published)) ? $response->post->published : $response->post->{'wm-received'};
                $webmention['content'] = (isset($response->post->content) && isset($response->post->content->text)) ? $response->post->content->text : '';
                $webmention['author'] = $receiver->getAuthor($response);

                if ($webmention['type'] === 'MENTION') {
                    if (is_null($webmention['author']['name'])) {
                        $webmention['author']['name'] = $webmention['source'];
                    }
                    if (is_null($webmention['author']['url'])) {
                        $webmention['author']['url'] = $webmention['source'];
                    }
                }

                $hookHelper->triggerHook('indieConnector.webmention.received', ['webmention' => $webmention, 'targetPage' => $targetPage]);

                if (option('mauricerenck.indieConnector.stats', false)) {
                    $stats = new WebmentionStats();
                    $stats->trackMention($webmention['target'], $webmention['source'], $webmention['type'], $webmention['author']['avatar']);
                }

                return $webmention;
            }
        ],
    ]
]);
