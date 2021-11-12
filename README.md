# Kirby IndieConnector
#### A Kirby Webmention plugin other plugins can subscribe to

![GitHub release](https://img.shields.io/github/release/mauricerenck/indieConnector.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

---

This plugin currently works only with webmention.io and is a replacement for the old "Tratschtante" plugin.

**Be aware:**

The panel view of this plugin only works with the upcomming Kirby 3.6 release! Beside that, receiving Webmentions with older Kirby version will work.

IndieConnector receives (and soon sends) Webmentions. But it doesn't "do" anything with it. It's function is to handle all the stuff around Webmentions and then normalize the format and trigger a hook. Other plugins can then subscribe to this hook and use the data.

If you want Webmentions to be shown on your pages, you have to use additional plugins (or write your own) which use IndieConnector to handle Webmentions. For example the [Komments plugin]() which will then show received webmentions as a comment (or in any other way you wish).

---
## Installation

Use one of these three methods to install the plugin:

- `composer require mauricerenck/indieConnector`
- unzip [master.zip](https://github.com/mauricerenck/indieConnector/releases/latest) to `site/plugins/indieConnector`
- `git submodule add https://github.com/mauricerenck/indieConnector.git site/plugins/indieConnector`

* [Setup the plugin](docs/setup.md)
* [Subscribe to the hook](docs/hook.md)

---

## Features

- Receive Webmentions on your site
- Shows a Webmention overview in the panel
- Sends out Webmentions via Hook so other plugins can subscribe and use them

---

## Roadmap 

- [x] Kirby 3.6 ready
- [x] View Webmention stats in the panel
- [ ] Send webmentions
- [ ] Notify on Mastodon
- [ ] Ping Archive.org
- [ ] Implement Webmentions without webmention.io
