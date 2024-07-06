# Receiving Webmentions using webmention.io

## Base Setup

To "connect" this plugin to webmention.io you have to set a callback secret in your config.php

```
[
    'mauricerenck.indieConnector.secret' => 'my-secret',
]
```

This could be any string, think of it as a sort of password.

## Add the webmention.io endpoints

Add webmention.io endpoints to your HTML `<head></head>`. Those will look like this and you will get them from webmention.io when you login to your account:

```
<!-- webmention -->
<link rel="pingback" href="https://webmention.io/YOUR-ACCOUNT/xmlrpc" />
<link rel="webmention" href="https://webmention.io/YOUR-ACCOUNT/webmention" />
```

In order to be allowed to use webmention hooks, you have to receive at least one webmention on your site before. So after adding those headers to your site, you have to send a webmention to yourself.

## Enable Webmention.io Hook

-   Go to your webmention.io account -> Webhooks.
-   Enter the IndieConnector endpoint: `https://your-url.tld/indieconnector/webhook/webmentionio`
-   Enter the callback secret you set in your config.php

## Enable panel overview

_This feature required sqlite to be available by your hoster. Most hosters support this by default, but you might want to make sure it's enabled._

IndieConnector comes with a nice overview of all your received webmentions. To enable this feature, set the `stats` option in your `config.php` to true and set a path where the database file should be stored. **Make sure this path exists, it will not be created**. The path is relative to your kirby root.

Example setup:

```
'mauricerenck.indieConnector.stats.enabled' => true,
'mauricerenck.indieConnector.sqlitePath' => 'content/.sqlite/',
```
