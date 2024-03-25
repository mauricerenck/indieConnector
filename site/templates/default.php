<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="pingback" href="https://webmention.io/indie-connector.test/xmlrpc" />
    <link rel="webmention" href="https://webmention.io/indie-connector.test/webmention" />

</head>
<body>
<h1><?= $page->title() ?></h1>

<main>
    <div class="h-entry">
      <p class="p-name">Post content goes here...</p>
      <a class="u-like-of" href="https://liked-page-tld">I like this</a>
    </div>

    <div class="h-entry">
      <p class="p-name">Post content goes here...</p>
      <a class="u-bookmark-of" href="URL of the bookmarked post">Bookmark</a>
    </div>

    <div class="h-event">
      <p class="p-name">Event title goes here...</p>
      <p class="p-summary">Event summary goes here...</p>
      <data class="p-start" value="YYYY-MM-DD">Event start date</data>
      <data class="p-end" value="YYYY-MM-DD">Event end date</data>
      <span class="p-location">Event location</span>
      <span class="u-in-reply-to">URL of the event</span>
      <span class="p-rsvp">yes/no/maybe/interested</span>
    </div>

    <div class="h-entry">
      <p class="p-name">Post content goes here...</p>
      <span class="u-in-reply-to">URL of the post being replied to</span>
    </div>

    <div class="h-event">
      <p class="p-name">Event title goes here...</p>
      <p class="p-summary">Event summary goes here...</p>
      <data class="p-start" value="YYYY-MM-DD">Event start date</data>
      <data class="p-end" value="YYYY-MM-DD">Event end date</data>
      <span class="p-location">Event location</span>
      <span class="u-in-reply-to">URL of the event</span>
      <span class="p-invitee">Invitee's URL</span>
    </div>


    <div class="h-entry">
      <p class="p-name">Post content goes here...</p>
      <span class="u-repost-of">URL of the original post being reposted</span>
    </div>

</main>
</body>
</html>
