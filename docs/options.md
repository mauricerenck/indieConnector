# Options

## Settings for sending webmentions ([details](sending.md))

| Option                    | Default                                                        | Description                                    |
| ------------------------- | -------------------------------------------------------------- | ---------------------------------------------- |
| `sendWebmention`          | `true`                                                         | Enable sending webmentions on page save        |
| `outboxFilename`          | `indieConnector.json`                                          | Change the filename of the processed urls file |
| `allowedTemplates`        | `[]`                                                           | Set templates allowed to send webmentions      |
| `blockedTemplates`        | `[]`                                                           | Block templates from sending webmentions       |
| `send-mention-url-fields` | `['textfield:text','layouteditor:layout','blockeditor:block']` | Set fieldnames and types to look for urls in   |

## Settings for receiving webmentions ([details](receiving.md))

| Option       | Default              | Description                                                                    |
| ------------ | -------------------- | ------------------------------------------------------------------------------ |
| `secret`     | `''`                 | Your webmention.io web hook secret                                             |
| `stats`      | `false`              | Enable webmention stats in the panel                                           |
| `sqlitePath` | `'content/.sqlite/'` | Relative path to where the sqlite file should be stored (directory must exist) |


## Settings for posting on mastodon ([details](mastodon.md))

| Option                  | Default         | Description                     |
| ----------------------- | --------------- | ------------------------------- |
| `sendMastodon`          | `false`         | Enable posting toots on publish |
| `mastodon-bearer`       | `??????`            | Your API Token                  |
| `mastodon-instance-url` | `??????`            | Your mastodon instance url      |
| `mastodon-text-field`   | `???description???` | Source field for toot text      |

