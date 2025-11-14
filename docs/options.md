# Options

## General settings

| Option           | Default                     | Description                                                                              |
| ---------------- | --------------------------- | ---------------------------------------------------------------------------------------- |
| `localhosts`     | `['localhost','127.0.0.1']` | Set local hosts to prevent sending webmentions and posts when testing on a local machine |
| `blockedTargets` | `[]`                        | Array of hosts to block sending webmentions to                                           |
| `secret`         | `''`                        | Your secret for webmention.io and the queue                                              |

## Settings for sending webmentions ([details](sending.md))

| Option                    | Default                                                        | Description                                                                                                        |
| ------------------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `send.enabled`            | `true`                                                         | Enable sending webmentions                                                                                         |
| `send.automatically`      | `true`                                                         | Enable sending webmentions automatically on page save                                                              |
| `send.hook.enabled`       | `false`                                                        | Allow sending webmentions from Kirby hook triggered by other plugins                                               |
| `send.maxRetries`         | `3`                                                            | How often should indieconnector try to send a mention if it failes                                                 |
| `send.markDeleted`        | `false`                                                        | When you delete a page, mark it as gone, so webmention targets can get informed about that - **Needs a database!** |
| `send.skipSameHost`       | `true`                                                         | Skip sending webmentions to yourself                                                                               |
| `send.allowedTemplates`   | `[]`                                                           | Only these template are allowed to send webmentions                                                                |
| `send.blockedTemplates`   | `[]`                                                           | These templates cannot send webmentions                                                                            |
| `send.url-fields`         | `['textfield:text','layouteditor:layout','blockeditor:block']` | Set fieldnames and types to look for urls in                                                                       |
| `send.outboxFilename`     | `indieConnector.json`                                          | Change the filename of the processed urls file                                                                     |
| `sendWebmention`          | `true`                                                         | **DEPRECATED** see `send.enabled`                                                                                  |
| `skipSameHost`            | `true`                                                         | **DEPRECATED** see `send.skipSameHost`                                                                             |
| `outboxFilename`          | `indieConnector.json`                                          | **DEPRECATED** see `send.outboxFilename`                                                                           |
| `allowedTemplates`        | `[]`                                                           | **DEPRECATED** see `send.allowedTemplates`                                                                         |
| `blockedTemplates`        | `[]`                                                           | **DEPRECATED** see `send.blockedTemplates`                                                                         |
| `send-mention-url-fields` | `['textfield:text','layouteditor:layout','blockeditor:block']` | **DEPRECATED** see `send.url-fields`                                                                               |

## Settings for receiving webmentions ([details](receiving.md))

| Option                   | Default | Description                                                                    |
| ------------------------ | ------- | ------------------------------------------------------------------------------ |
| `receive.enabled`        | `true`  | Enable receiving webmentions                                                   |
| `receive.useHtmlContent` | `false` | Set to true if you want to show html content from the sender (not recommended) |
| `receive.blockedSources` | `[]`    | An array of source URLs to block, remove the path to block the entire host     |

## Settings for the queue ([details](receiving.md))

| Option          | Default | Description                                                    |
| --------------- | ------- | -------------------------------------------------------------- |
| `queue.enabled` | `false` | Queue all incoming webmentions before processing them          |
| `queue.retries` | `5`     | Retry `n` times to process the webmention if there is an error |

## Settings for statistics in the panel

| Option             | Default           | Description                                               |
| ------------------ | ----------------- | --------------------------------------------------------- |
| `stats.enabled`    | `false`           | Enable webmention stats in the panel                      |
| `stats.doNotTrack` | `['fed.brid.gy']` | When sending webmentions, these hosts will not be tracked |

## Database settings

| Option              | Default              | Description                                                                    |
| ------------------- | -------------------- | ------------------------------------------------------------------------------ |
| `sqlitePath`        | `'content/.sqlite/'` | Relative path to where the sqlite file should be stored (directory must exist) |
| `disableMigrations` | `false`              | Disable automatic migrations (may lead to errors)                              |

## Posting to external services

| Option                  | Default           | Description                                                                    |
| ----------------------- | ----------------- | ------------------------------------------------------------------------------ |
| `post.prefereLanguage`  | `-`               | Use another language than your default language to use the text from           |
| `post.usePermalinkUrl`  | `false`           | Use the permalink url instead of the page url                                  |
| `post.skipUrl`          | `false`           | NEVER add the url to the post                                                  |
| `post.skipUrlTemplates` | `[]`              | Do not add the url to the post when using the given templates                  |
| `post.textfields`       | `['description']` | Text source fields for posting elsewhere                                       |
| `post.imagefield`       | `''`              | Image source field for posting elsewhere, must be one image                    |
| `post.imagealtfield`    | `alt`             | Name of the field containing the alt text for the image in your file blueprint |
| `post.tagsfield`        | `tags`            | A Kirby tag field to use for hashtags on mastodon and bluesky                  |
| `post.allowedTemplates` | `[]`              | Set templates allowed to send webmentions                                      |
| `post.blockedTemplates` | `[]`              | Block templates from sending webmentions                                       |
| `post.automatically`    | `true`            | Send posts automatically when a page is published                              |

### Mastodon ([details](mastodon.md))

| Option                  | Default         | Description                                                   |
| ----------------------- | --------------- | ------------------------------------------------------------- |
| `mastodon.enabled`      | `false`         | Enable posting to mastodon on publish                         |
| `mastodon.bearer`       | `‘‘`            | Your API Token                                                |
| `mastodon.instance-url` | `‘‘`            | Your mastodon instance url                                    |
| `mastodon.text-length`  | `500`           | When to trim the text                                         |
| `mastodon.resizeImages` | `0`             | Resize images before upload, value in pixel, 0 means disabled |
| `sendMastodon`          | `false`         | **DEPRECATED**                                                |
| `mastodon-bearer`       | `‘‘`            | **DEPRECATED**                                                |
| `mastodon-instance-url` | `‘‘`            | **DEPRECATED**                                                |
| `mastodon-text-length`  | `500`           | **DEPRECATED**                                                |
| `mastodon-text-field`   | `‘description‘` | **DEPRECATED**                                                |

### Bluesky ([details](bluesky.md))

| Option                 | Default | Description                                 |
| ---------------------- | ------- | ------------------------------------------- |
| `bluesky.enabled`      | `false` | Enable posting to bluesky on publish        |
| `bluesky.handle`       | `‘‘`    | Your user handle                            |
| `bluesky.password`     | `‘‘`    | Your bluesky app password                   |
| `bluesky.resizeImages` | `800`   | Resize images before upload, value in pixel |

## Collecting responses ([details](responses.md))

| Option                  | Default | Description                                                       |
| ----------------------- | ------- | ----------------------------------------------------------------- |
| `responses.enabled`     | `false` | Enable collecting responses                                       |
| `responses.limit`       | `10`    | Number of posts to check for responses                            |
| `responses.ttl`         | `60`    | Minutes after which a post url should be re-checked for responses |
| `responses.queue.limit` | `50`    | Number of items to process per run                                |

## ActivityPub beta ([details](activitiypub.md))

| Option              | Default | Description               |
| ------------------- | ------- | ------------------------- |
| `activityPubBridge` | `false` | Enable activityPub (beta) |
