# Kirby IndieConnector
#### A Kirby Webmention plugin other plugins can subscribe to

![GitHub release](https://img.shields.io/github/release/mauricerenck/indieConnector.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

---

This plugin currently works only with webmention.io.

IndieConnector receives and sends Webmentions. But it doesn't "do" anything with received Webmentions. It's function is to handle all the stuff around Webmentions and then normalize the format and trigger a hook. Other plugins can then subscribe to this hook and use the data.

If you want Webmentions to be shown on your pages, you have to use additional plugins (or write your own) which use IndieConnector to handle Webmentions. For example the [Komments plugin](https://github.com/mauricerenck/komments) which will then show received webmentions as a comment (or in any other way you wish).

---
## Installation

Use one of these three methods to install the plugin:

- `composer require mauricerenck/indieConnector`
- unzip [master.zip](https://github.com/mauricerenck/indieConnector/releases/latest) to `site/plugins/indieConnector`
- `git submodule add https://github.com/mauricerenck/indieConnector.git site/plugins/indieConnector`

---

## Docs

* [Receiving Webmentions](docs/receiving.md)
* [Sending Webmentions](docs/sending.md)
* [Be a Mastodon Instance](docs/activitypub.md) (ActivityPub)
* [Post to Mastodon](docs/mastodon.md)
* [Subscribe to the hook](docs/hook.md)
* [All options](docs/options.md)
* [Switch from Tratschtante to IndieConnector](docs/switch.md)
---

## Features

- Receive Webmentions on your site
- Send Webmentions from your site
- Shows a Webmention overview in the panel
- Propagates Webmentions via Hook so other plugins can subscribe and use them
- Send updates to mastodon (toot)

---

## Roadmap 

- [ ] Nested indieweb replies
- [ ] Blocklist for domains
- [ ] Blocklist for users
- [ ] Implement receiving webmentions without webmention.io
- [x] Connect to Mastodon (first step via https://fed.brid.gy/)
- [x] Kirby 3.6 ready
- [x] View Webmention stats in the panel
- [x] Send webmentions
- [x] Notify on Mastodon
