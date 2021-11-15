# Switch from Tratschtante to IndieConnector


## Receiving Webmentions

Simply change you config strings from `'mauricerenck.tratschtante.XXXXXX'` to `'mauricerenck.indieConnector.XXXXXX'`.
That's it. Everything should work fine.

## Subscribing to the hook

Change `'tratschtante.webhook.received'` to `'indieConnector.webmention.received'`.

For example:

```
'hooks' => [
    'tratschtante.webhook.received' => function ($webmention, $targetPage) {
        // YOUR CODE
    }
],
```

must be changed to:

```
'hooks' => [
    'indieConnector.webmention.received' => function ($webmention, $targetPage) {
        // YOUR CODE
    }
],
```