# Options

## Settings for sending webmentions ([details](sending.md))

| Option                    | Default                                                        | Description                                                                              |
| ------------------------- | -------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `sendWebmention`          | `true`                                                         | Enable sending webmentions on page save                                                  |
| `skipSameHost`            | `true`                                                         | Skip sending webmentions to yourself                                                     |
| `outboxFilename`          | `indieConnector.json`                                          | Change the filename of the processed urls file                                           |
| `allowedTemplates`        | `[]`                                                           | Set templates allowed to send webmentions                                                |
| `blockedTemplates`        | `[]`                                                           | Block templates from sending webmentions                                                 |
| `send-mention-url-fields` | `['textfield:text','layouteditor:layout','blockeditor:block']` | Set fieldnames and types to look for urls in                                             |
| `localhosts`              | `['localhost','127.0.0.1']`                                    | Set local hosts to prevent sending webmentions and posts when testing on a local machine |
| `blockedTargets`          | `[]`                                							 | Array of hosts to block sending webmentions to                                           |
| `send.maxRetries`         | `3`                                							 | How often should indieconnector try to send a mention if it failes                       |


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

| `sqlitePath`       | `'content/.sqlite/'` | Relative path to where the sqlite file should be stored (directory must exist) |


## Settings for posting on mastodon ([details](mastodon.md))

| Option                  | Default         | Description                     |
| ----------------------- | --------------- | ------------------------------- |
| `sendMastodon`          | `false`         | Enable posting toots on publish |
| `mastodon-bearer`       | `‘‘`            | Your API Token                  |
| `mastodon-instance-url` | `‘‘`            | Your mastodon instance url      |
| `mastodon.text-length`  | `500`           | When to trim the text           |
| `mastodon-text-field`   | `‘description‘` | DEPRECATED - use `post.textfield`     |

| `post.textfield`        | `‘description‘` | Text source field for posting elsewhere |
| `post.imagefield`        | `''` | Image source field for posting elsewhere, must be one image |
| `post.allowedTemplates`        | `[]` | Set templates allowed to send webmentions |
| `post.blockedTemplates`        | `[]` | Block templates from sending webmentions |

## ActivityPub beta ([details](activitiypub.md))

| Option              | Default | Description               |
| ------------------- | ------- | ------------------------- |
| `activityPubBridge` | `false` | Enable activityPub (beta) |
