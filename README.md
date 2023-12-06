# Kirby IndieConnector
#### Send and receive Webmentions, send Mastodon posts or act like an ActivityPub instance

![GitHub release](https://img.shields.io/github/release/mauricerenck/indieConnector.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-4%2B-black.svg)

---
IndieConnector receives and sends Webmentions. It can also post to Mastodon when a new page is published and act like a ActivityPub Instance (using fed.brid.gy).

This plugins handles all the stuff around Webmentions and then normalizes the format and triggers a hook. Other plugins can then subscribe to this hook and use the data.
If you want Webmentions to be shown on your pages, you have to use additional plugins (or write your own) which use IndieConnector to handle Webmentions. For example the [Komments plugin](https://github.com/mauricerenck/komments) which will then show received webmentions as a comment (or in any other way you wish).

---
## Installation

Use one of these three methods to install the plugin:

- `composer require mauricerenck/indieConnector`
- unzip [master.zip](https://github.com/mauricerenck/indieConnector/releases/latest) to `site/plugins/indieConnector`

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
- Act as a ActivityPub Instance (via fed.bridgy)

---

## Roadmap 

- [ ] Receive webmentions without webmention.io
- [ ] React to webmention delete
- [ ] Queue webmentions before processing
- [ ] Nested indieweb replies
- [ ] Blocklist for domains
- [ ] Blocklist for users

