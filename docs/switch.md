# Switch from Tratschtante to IndieConnector


## Receiving Webmentions

Simply change you config strings from `'mauricerenck.tratschtante.XXXXXX'` to `'mauricerenck.indieConnector.XXXXXX'`.
That's it. Everything should work fine.

## Subscribing to the hook

Change `'tratschtante.webhook.received'` to `'indieConnector.webhook.received'`.

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
    'indieConnector.webhook.received' => function ($webmention, $targetPage) {
        // YOUR CODE
    }
],
```