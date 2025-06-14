<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

?>
<html>

<head>
    <meta charset="utf-8">
</head>

<body>
    <div style="max-width: 500px; margin: 50px auto; border: 1px solid #ccc; border-radius: 5px; padding: 20px;">
        <article class="h-entry">
            <h1 class="p-name"><?= $page->title() ?></h1>

            <div class="p-summary">
                <p><?= Str::short($page->text()->kt(), 200); ?></p>
            </div>
            <p class="e-content"><?= $page->text()->kt(); ?></p>
            <a class="u-url" href="<?= $page->responseUrl() ?>">Permalink</a>

            <a class="u-<?= $page->responseType(); ?>" href="<?= $page->targetPage() ?>"><?= $page->targetPage() ?></a>

            <time class="published date dt-published"
                itemprop="datePublished"
                datetime="<?= $page->responseDate()->toDate('Y-m-d H:i:s') ?>">
                <?= $page->responseDate()->toDate('d.m.Y') ?>
            </time>

            <span class="p-category"><?= $page->responseSource() ?></span>

            <p class="h-card" style="margin-top: 50px; border-top: 1px solid #ccc; padding-top: 50px;">
                <img class="u-photo" src="<?= $page->authorAvatar() ?>" alt="" style="max-width: 100%;" />
                <a class="p-name u-url" href="<?= $page->authorUrl() ?>"><?= $page->authorName() ?></a>
            </p>
        </article>
    </div>
</body>

</html>