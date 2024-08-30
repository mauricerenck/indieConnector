# Hooks

When IndieConnector receives a Webmention, it triggers a hook. This hook can be used by other plugins to do something with the received data.

## Webmention received

```php
'hooks' => [
    'indieConnector.webmention.received' => function ($webmention, $targetPage) {
        // $webmention: webmention data, see below
        // $targetPage: a kirby page uuid with scheme

        // YOUR CODE
    }
],
```

When the hook is fired you get the webmention data as an php array, which looks like this:

```php
[
'type' => string // one of the webmention types
'target' => 'target url',
'source' => 'source url',
'published' => 'publication date',
'author' => [
    'type' => 'card' or null,
    'name' => 'name' or null,
    'avatar' => 'avatar-url' or null,
    'url' => 'author url' or null,
],
'content' => 'comment text or empty string'
]
```

## Webmention deleted

A webmention previously received by IndieConnector was deleted.

```php
'hooks' => [
    'indieConnector.webmention.deleted' => function ($sourceUrl, $targetUrl) {
        // YOUR CODE
    }
],
```

## Types of webmentions:

There can be different types of webmentions, those are:

'like-of', 'repost-of', 'bookmark-of', 'in-reply-to', 'rsvp', 'mention-of', 'invite'
