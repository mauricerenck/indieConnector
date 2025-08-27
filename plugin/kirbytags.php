<?php

use Kirby\Http\Remote;
use Kirby\Text\KirbyTag;

$originalTag = KirbyTag::$types['link'];

return [
    'like' => [
        'attr' => $originalTag['attr'],
        'html' => function ($tag) use ($originalTag) {
            $tag->class = trim('u-like-of ' . $tag->class);

            $link = $originalTag['html'];
            $markup = $link($tag);

            return $markup;
        },
    ],
    'bookmark' => [
        'attr' => $originalTag['attr'],
        'html' => function ($tag) use ($originalTag) {
            $tag->class = trim('u-bookmark-of ' . $tag->class);

            $link = $originalTag['html'];
            $markup = $link($tag);

            return $markup;
        },
    ],
    'repost' => [
        'attr' => $originalTag['attr'],
        'html' => function ($tag) use ($originalTag) {
            $tag->class = trim('u-repost-of ' . $tag->class);

            $link = $originalTag['html'];
            $markup = $link($tag);

            return $markup;
        },
    ],
    'reply' => [
        'attr' => $originalTag['attr'],
        'html' => function ($tag) use ($originalTag) {
            $tag->class = trim('u-reply-of ' . $tag->class);

            $link = $originalTag['html'];
            $markup = $link($tag);

            return $markup;
        },
    ],
    'mastodonpost' => [
        'html' => function ($tag) {
            $embedUrl = $tag->mastodonpost;

            $hostname = parse_url($embedUrl, PHP_URL_HOST);

            $oEmbedResult = Remote::get('https://' . $hostname . '/api/oembed?format=json&url=' . $embedUrl);
            if (isset($oEmbedResult)) {
                $oEmbedJson = $oEmbedResult->json();

                if (isset($oEmbedJson['html'])) {
                    return $oEmbedJson['html'];
                }
            }

            return null;
        },
    ],
];
