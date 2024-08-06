# Receiving Webmentions

## Base Setup

IndieConnector will receive webmentions by default. You only have to add the the webmention endpoint to your site so other sites know where to send webmentios to. This is done by adding the following code to your HTML `<head></head>`:

```php
<?php snippet('webmention-endpoint'); ?>
```

This is all you need to receive webmentions.

**Please keep in mind that this plugin does not store or display webmentions. You'll need an additional plugin for that.**

## Enabling the queue

**This feature requires a SQLite database!** Learn how to set it up [here](/docs/database.md).

If you receive a lot of webmentions, you might want to enable the queue feature. This will store all incoming webmentions in a queue and process them in the background. To enable this feature, set the `queue` option in your `config.php` to true.

Example setup:

```php
'mauricerenck.indieConnector.queue.enabled' => true,
'mauricerenck.indieConnector.secret' => 'your-very-secret',
```

If for some reason an entry cannot be processed, IndieConnector will retry five times before marking it as failed. You can change this number by setting the `retries` option in your `config.php`.

Example setup:

```php
'mauricerenck.indieConnector.queue.retries' => 3,
```

If you enable the queue, you have to set up a cronjob to process the queue. Processing is triggered by sending a **POST** request to the `indieconnector/queue` endpoint. The body needs to include your secret and the amount of entries you want to process. The amount of entries is optional and defaults to 10.:

```json
{
  "limit": "2",
  "secret": "your-very-secret"
}
```

## Blocking sources

Maybe you don't want to receive webmentions from certain sources. You can block these sources by setting the `receive.blockedSources` option in your `config.php`. This option should be an array of URLs. You can either block the whole domain or a specific URL:


```php
'mauricerenck.indieConnector.receive.blockedSources' => ['https://example.com','https://example.com/source'],
```


## Using HTML content

By default IndieConnector will only use the `text` property of the webmention. If you want to use the `html` property instead, you can set the `receive.useHtmlContent` option in your `config.php` to true. I would not recommend this, as it can lead to security issues.

```php
'mauricerenck.indieConnector.receive.useHtmlContent' => true,
```

## Disable receiving Webmentions

Maybe you only want to use the plugin for its other features. You can then disable receiving webmentions in your `config.php`:

```php
'mauricerenck.indieConnector.receive.enabled' => false,
```