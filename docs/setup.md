# Setup


FIXME: in order to be able to use webmention.io hooks, you have to receive at least one webmention, explain how!
## Receiving Webmentions

To "connect" this plugin to webmention.io you have to set a callback secret in your config.php

```
[
    'mauricerenck.indieConnector.secret' => 'my-secret',
]
```

- Go to your webmention.io account -> Webhooks.
- Enter the IndieConnector endpoint: `https://your-url.tld/indieConnector/webhook/webmentionio`
- Enter the callback secret you set in your config.php

## Add webmention.io Endpoints to your HTML `<head></head>`. Those will look like this and you will get them from webmention.io:

```
<!-- webmention -->
<link rel="pingback" href="https://webmention.io/YOUR-ACCOUNT/xmlrpc" />
<link rel="webmention" href="https://webmention.io/YOUR-ACCOUNT/webmention" />
```

## Enable panel overview

*This feature required sqlite to be available by your hoster. Most hosters support this by default, but you might want to make sure it's enabled.*

IndieConnector comes with a nice overview of all your received webmentions. To enable this feature, set the `stats` option in your `config.php` to true and set a path where the database file should be stored. **Make sure this path exists, it will not be created**.

Example setup:
```
'mauricerenck.indieConnector.stats' => true,
'mauricerenck.indieConnector.sqlitePath' => '.sqlite/',
```
