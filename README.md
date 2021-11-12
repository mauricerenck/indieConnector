# Kirby IndieConnector
#### A Kirby Webmention Plugin your Plugins can subscribe to

![GitHub release](https://img.shields.io/github/release/mauricerenck/indieConnector.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

---

This plugin currently works only with webmention.io and is a replacement for the old "Tratschtante" plugin.

Add the IndieConnector Endpoint to your webmention.io Webhooks and enter the callback secret you set in your kirby config.

## Installation

- `composer require mauricerenck/indieConnector`
- unzip [master.zip](https://github.com/mauricerenck/indieConnector/releases/latest) to `site/plugins/indieConnector`
- `git submodule add https://github.com/mauricerenck/indieConnector.git site/plugins/indieConnector`

## Config

You have to set a callback secret in your config.php

```
[
    'mauricerenck.indieConnector.secret' => 'my-secret',
]
```

- Go to your webemention.io account and to Webhooks.
- Enter the IndieConnector endpoint: `https://your-url.tld/indieConnector/webhook/webmentionio`
- Enter the callback secret you set in your config.php

## Usage

Whenever a webmention ins received, IndieConnector will trigger a Kirby-Hook your plugin can subscribe to:

```
'hooks' => [
    'indieConnector.webhook.received' => function ($webmention, $targetPage) {
        // $webmention: webmention data, see below
        // $targetPage: a kirby page object

        // YOUR CODE
    }
],
```

## Data

IndieConnector will handle an array with some data to you:

```
[
'type' => STRING // one of the webmention.io types, see https://webmention.io/settings/webhooks,
'target' => 'target url',
'source' => 'source url',
'published' => 'publication date',
'author' => [
    'type' => 'card' or null,
    'name' => 'name' or null,
    'avatar' => 'avatar-url' or null,
    'url' => 'author url' or null,
],
'content' => 'comment text or empty string'
]
```

## Future Plans

- Sending webmentions (already implemented elsewhere, just have to move it here)
- Support for "native" webmentions, not only webmention.io
