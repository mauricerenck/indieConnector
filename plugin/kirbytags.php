<?php
$originalTag = Kirby\Text\KirbyTag::$types['link'];
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
];
