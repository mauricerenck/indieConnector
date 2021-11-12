# Hooks

IndieConnector receives (and soon sends) Webmentions. But it doesn't "do" anything with it. It's function is to handle all the stuff around Webmentions and then normalize the format and trigger a hook. Other plugins can then subscribe to this hook and use the data.

The [Komments plugin]() does this, for example.

If you want to build your own plugin using Webmentions this is your starting point.

## Usage

Whenever a webmention ins received, IndieConnector will trigger a Kirby hook your plugin can subscribe to. To do this, you have to listen to the hook by setting it up in your plugins `index.php` file:

```
'hooks' => [
    'indieConnector.webhook.received' => function ($webmention, $targetPage) {
        // $webmention: webmention data, see below
        // $targetPage: a kirby page object

        // YOUR CODE
    }
],
```

You will then get the data of the webmention in a normalized format **and** the page object of the page the webmention was sent to. With this data you can now do whatever you want.


## Data format

When the hook is fired you get the webmention data as an php array, which looks like this:

```
[
'type' => STRING // one of the webmention.io types, see https://webmention.io/settings/webhooks,
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
