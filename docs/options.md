# Options


## General settings

| Option                    | Default                                                        | Description                                                                              |
| ------------------------- | -------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `localhosts`              | `['localhost','127.0.0.1']`                                    | Set local hosts to prevent sending webmentions and posts when testing on a local machine |
| `blockedTargets`          | `[]`                                							 | Array of hosts to block sending webmentions to                                           |


## Settings for sending webmentions ([details](sending.md))

| Option                    | Default                                                        | Description                                                                                                        |
| ------------------------- | -------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `send.enabled`            | `true`                                					     | Enable sending webmentions                                                                                         |
| `send.maxRetries`         | `3`                                							 | How often should indieconnector try to send a mention if it failes                                                 |
| `send.markDeleted`        | `false`                             							 | When you delete a page, mark it as gone, so webmention targets can get informed about that - **Needs a database!** |
| `send.skipSameHost`       | `true`                             							 | Skip sending webmentions to yourself                                                        |
| `send.allowedTemplates`   | `[]`                             			                     | Only these template are allowed to send webmentions                       |
| `send.blockedTemplates`   | `[]`                             			                     | These templates cannot send webmentions                       |
| `send.url-fields`         | `['textfield:text','layouteditor:layout','blockeditor:block']` | Set fieldnames and types to look for urls in                       |
| `send.outboxFilename`     | `indieConnector.json`                             			 | Change the filename of the processed urls file                       |
| `sendWebmention`          | `true`                                                         | DEPRECATED see `send.enabled`                                              |
| `skipSameHost`            | `true`                                                         | DEPRECATED see `send.skipSameHost`                                                    |
| `outboxFilename`          | `indieConnector.json`                                          | DEPRECATED see `send.outboxFilename`                                           |
| `allowedTemplates`        | `[]`                                                           | DEPRECATED see `send.allowedTemplates`                                      |
| `blockedTemplates`        | `[]`                                                           | DEPRECATED see `send.blockedTemplates`                                                 |
| `send-mention-url-fields` | `['textfield:text','layouteditor:layout','blockeditor:block']` | DEPRECATED see `send.url-fields`                                             |


## Settings for receiving webmentions ([details](receiving.md))

| Option              | Default | Description                                                                    |
| ------------------- | ------- | ------------------------------------------------------------------------------ |
| `secret`            | `''`    | Your webmention.io web hook secret                                             |
| `receive.enabled`   | `true`  | Enable receiving webmentions                                                   |
| `receive.useHtmlContent`    | `false` | Set to true if you want to show html content from the sender (not recommended) |
| `receive.blockedSources`    | `[]`    | An array of source URLs to block, remove the path to block the entire host     |
| `queue.enabled`     | `false` | Queue all incoming webmentions before processing them                          |
| `queue.retries`     | `5`     | Retry `n` times to process the webmention if there is an error                 |


## Settings for statistics in the panel

| Option             | Default              | Description                                                                    |
| ------------------ | -------------------- | ------------------------------------------------------------------------------ |
| `stats.enabled`    | `false`              | Enable webmention stats in the panel                                           |
| `stats.doNotTrack` | `['fed.brid.gy']`    | When sending webmentions, these hosts will not be tracked                      |

## Database settings

| Option             | Default              | Description                                                                    |
| ------------------ | -------------------- | ------------------------------------------------------------------------------ |
| `sqlitePath`       | `'content/.sqlite/'` | Relative path to where the sqlite file should be stored (directory must exist) |


## Posting to external services

| Option                  | Default         | Description                                      |
| ----------------------- | --------------- | ------------------------------------------------ |
| `post.textfield`        | `‘description‘` | Text source field for posting elsewhere          |
| `post.imagefield`       | `''` | Image source field for posting elsewhere, must be one image |
| `post.allowedTemplates` | `[]` | Set templates allowed to send webmentions                   |
| `post.blockedTemplates` | `[]` | Block templates from sending webmentions                    |


### Mastodon ([details](mastodon.md))

| Option                  | Default         | Description                           |
| ----------------------- | --------------- | ------------------------------------- |
| `mastodon.enabled`      | `false`         | Enable posting to mastodon on publish |
| `mastodon.bearer`       | `‘‘`            | Your API Token                        |
| `mastodon.instance-url` | `‘‘`            | Your mastodon instance url            |
| `mastodon.text-length`  | `500`           | When to trim the text                 |
| `sendMastodon`          | `false`         | DEPRECATED                            |
| `mastodon-bearer`       | `‘‘`            | DEPRECATED                            |
| `mastodon-instance-url` | `‘‘`            | DEPRECATED                            |
| `mastodon.text-length`  | `500`           | DEPRECATED                            |
| `mastodon-text-field`   | `‘description‘` | DEPRECATED                            |

### Bluesky ([details](bluesky.md))

| Option                  | Default         | Description                          |
| ----------------------- | --------------- | ------------------------------------ |
| `bluesky.enabled`       | `false`         | Enable posting to bluesky on publish |
| `bluesky.handle`        | `‘‘`            | Your user handle                     |
| `bluesky.password`      | `‘‘`            | Your bluesky app password            |


## ActivityPub beta ([details](activitiypub.md))

| Option              | Default | Description               |
| ------------------- | ------- | ------------------------- |
| `activityPubBridge` | `false` | Enable activityPub (beta) |
