# Collecting Responses

Collect likes, reposts and replies from Mastodon or Bluesky and show them on your pages.

**This feature requires a SQLite database. [Learn how to set it up here](database.md)**

When you use the IndieConnector to create posts on Mastodon or Bluesky whenever you publish a page (POSSE), you can now collect responses from those posts and show them as comments or in any other form on your pages.

To use this feature you have to at least enable it in your config.php:

```php
'mauricerenck.indieConnector.responses.enabled' => true
'mauricerenck.indieConnector.secret' => 'YOUR-SECRET
```

From now on the Response Collector will remember all post URLs. It will do so whenever the IndieConnector creates a new post on Mastodon or Bluesky or when you manually fill the Mastodon or Bluesky URL fields.

## Collecting Responses

To collect new reponses, you have to call a Webhook. The URL is:

```
https://example.com/indieConnector/cron/queue-responses?secret=YOUR-SECRET
```

Of course you have to change `example.com` to match your host and `YOUR-SECRET` to match the secret your set in your `config.php`.

The plugin will then check the next 10 post URLs it knows and which haven't been check in the last hour.

You can change how many URLs are checked and after how many minutes they should be checked again by setting:

```php
'mauricerenck.indieConnector.responses.limit' => 20
'mauricerenck.indieConnector.responses.ttl' => 120
```

To check 20 posts in row check for the same URLs again after two hours (or 120 minutes).

Depending on how frequently you want to check for responses you should adjust those values and schedule the cronjob which calls the Webhook URL.

## Parse Responses

New reposnes will be added to a queue. To process that queue, you have to call another Webhook:

```
https://example.com/indieConnector/cron/fetch-responses?secret=YOUR-SECRET
```

This Webhook will process 50 responses in a row. You can change that by setting:

```php
'mauricerenck.indieConnector.responses.queue.limit' => 100
```

New reponses will be processed as Webmention, so after setting everything up, you should see those reponses on the Panel View of IndieConnector in case you enabled it.

## brid.gy

If you used brid.gy to collect respones, please make sure to deactivate it, otherwise you might end up with duplicates.
