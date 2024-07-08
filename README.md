# Kirby IndieConnector
#### Send and receive Webmentions, send Mastodon and Bluesky posts or act like an ActivityPub instance

![GitHub release](https://img.shields.io/github/release/mauricerenck/indieConnector.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-4%2B-black.svg)

---
IndieConnector is your interface to the IndieWeb. It'll help you to:

- Receive Webmentions
- Send Webmentions
- Post to Mastodon
- Post to Bluesky
- Act like a ActivityPub Instance

This plugins handles all the stuff around Webmentions and then normalizes the format and triggers a hook. Other plugins can then subscribe to this hook and use the data.

If you want Webmentions to be shown on your pages, you have to use additional plugins (or write your own) which use IndieConnector to handle Webmentions. For example the [Komments plugin](https://github.com/mauricerenck/komments) which will then show received webmentions as a comment (or in any other way you wish).

---
## Installation

Use one of these three methods to install the plugin:

- `composer require mauricerenck/indieConnector`
- unzip [main.zip](https://github.com/mauricerenck/indieConnector/releases/latest) to `site/plugins/indieConnector`

---

## Docs

* [Receiving Webmentions](docs/receiving.md)
* [Sending Webmentions](docs/sending.md)
* [Activate the Panel view](docs/panel-view.md)
* [Be a Mastodon Instance](docs/activitypub.md) (ActivityPub)
* [Post to Mastodon or Bluesky](docs/mastodon.md)
* [Subscribe to the hook](docs/hook.md)
* [Using webmention.io](docs/webmentionio.md)
* [Using microformats](docs/microformats.md)
* [All options](docs/options.md)

---

## Roadmap

- [ ] Send Webmentions and Posts asynchronously
- [ ] Nested indieweb replies
- [ ] Blocklist for users on Mastodon and Bluesky
- [ ] Manual post to Mastodon and Bluesky
- [ ] Manual queue cleanup
- [ ] Block hosts from within the panel
