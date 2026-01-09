## [2.14.2](https://github.com/mauricerenck/indieConnector/compare/v2.14.1...v2.14.2) (2026-01-09)


### Bug Fixes

* more rebost webmention type detection ([215ae61](https://github.com/mauricerenck/indieConnector/commit/215ae612dcb5f861c8219f0cfe027392fa566d53))

## [2.14.1](https://github.com/mauricerenck/indieConnector/compare/v2.14.0...v2.14.1) (2025-11-28)


### Bug Fixes

* never trim tags [#33](https://github.com/mauricerenck/indieConnector/issues/33) ([385536e](https://github.com/mauricerenck/indieConnector/commit/385536e477d2cd89072fd4a8c37f4d3cdeb415d6))

# [2.14.0](https://github.com/mauricerenck/indieConnector/compare/v2.13.0...v2.14.0) (2025-11-14)


### Features

* get post data ([cea1b9a](https://github.com/mauricerenck/indieConnector/commit/cea1b9ab43d98489f7f5e306c3e4ee64751d9396))
* option for resizing images before upload ([26d11b2](https://github.com/mauricerenck/indieConnector/commit/26d11b206f4894b1bd26bd6c4cf08b177e5ff545))

# [2.13.0](https://github.com/mauricerenck/indieConnector/compare/v2.12.2...v2.13.0) (2025-11-14)


### Features

* Enhance cURL requests with User-Agent and error handling ([#32](https://github.com/mauricerenck/indieConnector/issues/32)) ([42e3d4b](https://github.com/mauricerenck/indieConnector/commit/42e3d4bd4970efdf2df446fe90981faac91d4244))

## [2.12.2](https://github.com/mauricerenck/indieConnector/compare/v2.12.1...v2.12.2) (2025-10-16)


### Bug Fixes

* limit templates for posse ([82993be](https://github.com/mauricerenck/indieConnector/commit/82993bef5e3b8fbd659acf850205047f4cbcdff9))
* no more scss style lang ([1a603be](https://github.com/mauricerenck/indieConnector/commit/1a603be5dbf963bbe1a0a36820c83209e6a7ff8d))

## [2.12.1](https://github.com/mauricerenck/indieConnector/compare/v2.12.0...v2.12.1) (2025-08-27)


### Bug Fixes

* copy and paste error ([4c35b93](https://github.com/mauricerenck/indieConnector/commit/4c35b93af30d52e493e158e052bae87cd352df0a))

# [2.12.0](https://github.com/mauricerenck/indieConnector/compare/v2.11.0...v2.12.0) (2025-08-27)


### Features

* mastodonpost kirby tag ([043aaf4](https://github.com/mauricerenck/indieConnector/commit/043aaf4cdcf61e50faec4472648e3086943ea308))

# [2.11.0](https://github.com/mauricerenck/indieConnector/compare/v2.10.0...v2.11.0) (2025-08-11)


### Bug Fixes

* bluesky detect hashtags  [#28](https://github.com/mauricerenck/indieConnector/issues/28) ([90625bb](https://github.com/mauricerenck/indieConnector/commit/90625bbe36a73390b01814d1c493303c7c940f3e))
* mastodon text length option not considered ([c6d8e9f](https://github.com/mauricerenck/indieConnector/commit/c6d8e9fac26940965b4a1b534c697f6405f26192))


### Features

* include alt text when posting images ([63e6510](https://github.com/mauricerenck/indieConnector/commit/63e6510aae12626da3ecc56524295c5693b8adb7)), closes [#29](https://github.com/mauricerenck/indieConnector/issues/29)
* use tags from tagfield when posting [#28](https://github.com/mauricerenck/indieConnector/issues/28) ([1187392](https://github.com/mauricerenck/indieConnector/commit/11873925443db03b37655caf62ba232e2e7307d6)), closes [#30](https://github.com/mauricerenck/indieConnector/issues/30)

# [2.10.0](https://github.com/mauricerenck/indieConnector/compare/v2.9.2...v2.10.0) (2025-07-06)


### Bug Fixes

* allow duplicate ids from different mastodon instances ([14834d0](https://github.com/mauricerenck/indieConnector/commit/14834d0d027c2fa7abb94c0d8557ce322cfad8c8))
* dark mode in panel view ([ec0f49c](https://github.com/mauricerenck/indieConnector/commit/ec0f49c26ea243d37b6191e074347698856dfd7b))


### Features

* disable automatic sending of webmentions ([62a0b7c](https://github.com/mauricerenck/indieConnector/commit/62a0b7c2aae4f311631f91bb154e9c471b0a1843))
* post manually panel button ([eccf12a](https://github.com/mauricerenck/indieConnector/commit/eccf12a9c10a660e775a5b6a83a4da4495e71970))
* trigger sending webmentions from view button ([2c07f00](https://github.com/mauricerenck/indieConnector/commit/2c07f00f1e56d4003706840daee2ab48d8e7b11d))

## [2.9.2](https://github.com/mauricerenck/indieConnector/compare/v2.9.1...v2.9.2) (2025-06-20)


### Bug Fixes

* handle empty string secrets ([2892f14](https://github.com/mauricerenck/indieConnector/commit/2892f14a3cd6f37a1479ae9e76c2efc89a2ffa8f))

## [2.9.1](https://github.com/mauricerenck/indieConnector/compare/v2.9.0...v2.9.1) (2025-06-16)


### Bug Fixes

* removed missed debug code ([ff16b79](https://github.com/mauricerenck/indieConnector/commit/ff16b79537340a7f47a0967e531f162ecc51577b))

# [2.9.0](https://github.com/mauricerenck/indieConnector/compare/v2.8.0...v2.9.0) (2025-06-15)


### Bug Fixes

* author urls in panel and redirect for processed response urls ([8870111](https://github.com/mauricerenck/indieConnector/commit/887011148400dc0fba62f8189385eeed952f8d07))
* move bluesky connect to prevent performance issues ([fdf67bb](https://github.com/mauricerenck/indieConnector/commit/fdf67bb90e3c69c1be1beb679db76d08991b999f))
* response collector - handle deleted posts ([d9e5069](https://github.com/mauricerenck/indieConnector/commit/d9e5069b4978bc5230234dd3519c4b48c128483b)), closes [#27](https://github.com/mauricerenck/indieConnector/issues/27)


### Features

* detect service types like mastodon and bluesky [#25](https://github.com/mauricerenck/indieConnector/issues/25) ([efd1d08](https://github.com/mauricerenck/indieConnector/commit/efd1d0878a4587d581693a49733df9d700fe7a25))

# [2.8.0](https://github.com/mauricerenck/indieConnector/compare/v2.7.3...v2.8.0) (2025-05-27)


### Features

* fetch responses from mastodon and bluesky ([#23](https://github.com/mauricerenck/indieConnector/issues/23)) ([0978d26](https://github.com/mauricerenck/indieConnector/commit/0978d26deea65bbb1e2a5289b22dc00588e78dae)), closes [#22](https://github.com/mauricerenck/indieConnector/issues/22) [#17](https://github.com/mauricerenck/indieConnector/issues/17)

## [2.7.3](https://github.com/mauricerenck/indieConnector/compare/v2.7.2...v2.7.3) (2025-04-16)


### Bug Fixes

* check for empty atUri ([d8b3732](https://github.com/mauricerenck/indieConnector/commit/d8b3732818d9a58516a6a8b2b49acf7f590a530c))

## [2.7.2](https://github.com/mauricerenck/indieConnector/compare/v2.7.1...v2.7.2) (2025-04-12)


### Bug Fixes

* prevent timing issue when writing multiple posts to outbox [#18](https://github.com/mauricerenck/indieConnector/issues/18) ([a4991bc](https://github.com/mauricerenck/indieConnector/commit/a4991bcd166ab96033a0f9f128f54e66a4560fda)), closes [#19](https://github.com/mauricerenck/indieConnector/issues/19) [#20](https://github.com/mauricerenck/indieConnector/issues/20)

## [2.7.1](https://github.com/mauricerenck/indieConnector/compare/v2.7.0...v2.7.1) (2025-04-04)


### Bug Fixes

* autoload ([e66d406](https://github.com/mauricerenck/indieConnector/commit/e66d40643dc1ac6f18f84fce1edfffb109a03717))

# [2.7.0](https://github.com/mauricerenck/indieConnector/compare/v2.6.0...v2.7.0) (2025-04-04)


### Bug Fixes

* transforms the field name to lowercase [#16](https://github.com/mauricerenck/indieConnector/issues/16) ([d837bdd](https://github.com/mauricerenck/indieConnector/commit/d837bdd381a952fbf50f298f1f6c2f2d838dfbd4))


### Features

* send webmentions via triggered hook ([9c0c7aa](https://github.com/mauricerenck/indieConnector/commit/9c0c7aade870cc4d0bea4ce7c2869645fab2207d))

# [2.6.0](https://github.com/mauricerenck/indieConnector/compare/v2.5.0...v2.6.0) (2025-02-28)


### Features

* source view splitted into sources and users ([d609310](https://github.com/mauricerenck/indieConnector/commit/d609310e76f2c7b1ad32583370f215af6ee0c46e))

# [2.5.0](https://github.com/mauricerenck/indieConnector/compare/v2.4.1...v2.5.0) (2025-01-24)


### Bug Fixes

* handle bluesky posting when preferedLanguage is empty ([2f63a4f](https://github.com/mauricerenck/indieConnector/commit/2f63a4fb4760f63a601c5360f14c1cda6371b5ad))


### Features

* kirby 5 compatibility ([ae1575c](https://github.com/mauricerenck/indieConnector/commit/ae1575c3a387e34705db2c790617179540da9aa8))

## [2.4.1](https://github.com/mauricerenck/indieConnector/compare/v2.4.0...v2.4.1) (2024-12-29)


### Bug Fixes

* shorter toggle labels ([9af1d17](https://github.com/mauricerenck/indieConnector/commit/9af1d179ded8c9917941133eb73d1be26494022e))

# [2.4.0](https://github.com/mauricerenck/indieConnector/compare/v2.3.4...v2.4.0) (2024-11-28)


### Features

* allow setting a language for posting to mastodon and bluesky ([be447c7](https://github.com/mauricerenck/indieConnector/commit/be447c71ad98e1b6e2e6bfd6195685f663a6e803))
* set a prefered language for posting to mastodon or bluesky ([d86dac0](https://github.com/mauricerenck/indieConnector/commit/d86dac00f184937dfbe357a40b0b7934094779e5))

## [2.3.4](https://github.com/mauricerenck/indieConnector/compare/v2.3.3...v2.3.4) (2024-11-28)


### Bug Fixes

* allow empty title ([88dd5e5](https://github.com/mauricerenck/indieConnector/commit/88dd5e5066edb099adc7676ee3ac57810f273d4d))

## [2.3.3](https://github.com/mauricerenck/indieConnector/compare/v2.3.2...v2.3.3) (2024-11-28)


### Bug Fixes

* allow empty author ([0f0d636](https://github.com/mauricerenck/indieConnector/commit/0f0d636a47907bdb9826f513a553a8b1a30853f9))

## [2.3.2](https://github.com/mauricerenck/indieConnector/compare/v2.3.1...v2.3.2) (2024-11-28)


### Bug Fixes

* webmention stats - allow empty image ([9c232c4](https://github.com/mauricerenck/indieConnector/commit/9c232c46ee0ebbdee25d5d46a3db6f7de724a0b4))

## [2.3.1](https://github.com/mauricerenck/indieConnector/compare/v2.3.0...v2.3.1) (2024-11-23)


### Bug Fixes

* revert to mention-of if no type was detected ([7dbb53a](https://github.com/mauricerenck/indieConnector/commit/7dbb53a8d33cc133f006ec702e4e9e96261ef2cf))
* set target and source urls if not set in constructor ([d932d81](https://github.com/mauricerenck/indieConnector/commit/d932d81b424bffea18bba4c9a9062afffd78a1d2))

# [2.3.0](https://github.com/mauricerenck/indieConnector/compare/v2.2.2...v2.3.0) (2024-11-23)


### Features

* process queue from panel ([0e5c460](https://github.com/mauricerenck/indieConnector/commit/0e5c460ff6e6cb0c2b4508e10d7e29e79f440e97))

## [2.2.2](https://github.com/mauricerenck/indieConnector/compare/v2.2.1...v2.2.2) (2024-11-13)


### Bug Fixes

* shorten text when too long ([a9590aa](https://github.com/mauricerenck/indieConnector/commit/a9590aad45f122a7175eb669ee57a73810635965))

## [2.2.1](https://github.com/mauricerenck/indieConnector/compare/v2.2.0...v2.2.1) (2024-11-11)


### Bug Fixes

* add note to author info ([d37148a](https://github.com/mauricerenck/indieConnector/commit/d37148abed02a55dc7a31d243d78cd2298786d9b))
* dependency updates ([0db3960](https://github.com/mauricerenck/indieConnector/commit/0db3960b0e5082ce3b706fa409020e4e19573913)), closes [#15](https://github.com/mauricerenck/indieConnector/issues/15)

# [2.2.0](https://github.com/mauricerenck/indieConnector/compare/v2.1.3...v2.2.0) (2024-09-26)


### Features

* get bluesky url page method ([c86692c](https://github.com/mauricerenck/indieConnector/commit/c86692caebd5acafe7a4a4f28dbb39f148328bde))

## [2.1.3](https://github.com/mauricerenck/indieConnector/compare/v2.1.2...v2.1.3) (2024-09-10)


### Bug Fixes

* mastodon docs [#14](https://github.com/mauricerenck/indieConnector/issues/14) ([f4cd782](https://github.com/mauricerenck/indieConnector/commit/f4cd782d7ba843b2a76b901e9e32fdb3152a7987))

## [2.1.2](https://github.com/mauricerenck/indieConnector/compare/v2.1.1...v2.1.2) (2024-09-09)


### Bug Fixes

* always return a date, set one if not in data ([6e2d319](https://github.com/mauricerenck/indieConnector/commit/6e2d319096175ffd1d7af22326050e6019e6faad))

## [2.1.1](https://github.com/mauricerenck/indieConnector/compare/v2.1.0...v2.1.1) (2024-08-30)


### Bug Fixes

* handle non-url types in mastodon replies with hashtags ([eaf6bad](https://github.com/mauricerenck/indieConnector/commit/eaf6bad40fefb0c756c14485b90e17f9fc8d15e1))
* webmention now sends uuid instead of page object ([ee93c5d](https://github.com/mauricerenck/indieConnector/commit/ee93c5d02622f44a2f254918cfbc1224549e57a1))

# [2.1.0](https://github.com/mauricerenck/indieConnector/compare/v2.0.1...v2.1.0) (2024-08-17)


### Features

* define multiple source fields for external posting ([0f9f04c](https://github.com/mauricerenck/indieConnector/commit/0f9f04c72a71cd6f1f526d70adc197dec788657f))

## [2.0.1](https://github.com/mauricerenck/indieConnector/compare/v2.0.0...v2.0.1) (2024-08-10)


### Bug Fixes

* outbox version 2 format mixup ([73ef59e](https://github.com/mauricerenck/indieConnector/commit/73ef59e0dcc78cc0089000abd9350c8f3dc28740))

# [2.0.0](https://github.com/mauricerenck/indieConnector/compare/v1.10.0...v2.0.0) (2024-08-06)


### Features

* native webmentions ([#13](https://github.com/mauricerenck/indieConnector/issues/13)) ([38321f6](https://github.com/mauricerenck/indieConnector/commit/38321f648b828795d807e7a8f286dedfa2f08448))


### BREAKING CHANGES

* - rewrite of receive classes and tests

* feat: stats enabled option
* the configuration stats = true has been moved to stats.enabled = true

* feat: split data into webmention hooks

* feat: webmention queue

* feat: process queue

* feat: urlcheck class

* feat: page checks class

* feat: database class

* feat: outsourced webmention io specific code

* feat: mastodon posting

* feat: post images

* feat: block templates

* feat: disable per page

* feat: configure status length

* feat: handle deleted pages

* feat: paginated tables in panel view

* feat: disable migrations

* feat: mastodon url panel field

* feat: block source urls in config

* feat: check for disabled webmentions on page level

* feat: kirbytags for like, bookmark, repost, reply

* feat: disable posting to mastodon on page level

* feat: post to bluesky

* feat: endpoint snippet

* feat: updated outbox format
* feat: save status of external posts

* feat: mastodon url page method

* feat: check bsky and mastodon urls and do not send again

* feat: stat view updates

* fix: stats option keeping donottrack in mind

* fix: localurl checks

* fix: use uri instead of slug for full path

* fix: 410 route

* fix: mastodon sender enable had wrong init value

* fix: adapt to webmentions.rock tests

* improvement: hook - detect urls once

* improvement: microformat detection and tests

* improvement: robust author field getters

* improvement: show author and page title in stats

* improvement: stats now check if the webmention is an update and doesnt count twice

* improvement: mastodon sender with new options

* improvement: new option structure

* improvement: moved routes into separate file

* improvement: stats

* improvement: use ktable component

# [1.10.0](https://github.com/mauricerenck/indieConnector/compare/v1.9.1...v1.10.0) (2023-12-30)


### Features

* do not track host ([#10](https://github.com/mauricerenck/indieConnector/issues/10)) ([c054d21](https://github.com/mauricerenck/indieConnector/commit/c054d212fa27d607722fc377e8ad8c66d0ea3702))

## [1.9.1](https://github.com/mauricerenck/indieConnector/compare/v1.9.0...v1.9.1) (2023-12-06)


### Bug Fixes

* composer updates ([9f08816](https://github.com/mauricerenck/indieConnector/commit/9f088160dcc738d86da57121ca9a99f41b1b1aac))

# [1.9.0](https://github.com/mauricerenck/indieConnector/compare/v1.8.2...v1.9.0) (2023-11-23)


### Features

* handle db migrations if no path is given ([9a5d438](https://github.com/mauricerenck/indieConnector/commit/9a5d43881b91ddf5c10082de0a92422119ca83ca))

## [1.8.2](https://github.com/mauricerenck/indieConnector/compare/v1.8.1...v1.8.2) (2023-10-29)


### Bug Fixes

* remove bridgy json request ([dbc7a5a](https://github.com/mauricerenck/indieConnector/commit/dbc7a5ab9ed985b9fbb2fd66436ba9eb4ff1a140)), closes [#8](https://github.com/mauricerenck/indieConnector/issues/8)

## [1.8.1](https://github.com/mauricerenck/indieConnector/compare/v1.8.0...v1.8.1) (2023-08-27)


### Bug Fixes

* panel error on unknown webmention endpoint ([e05b3ca](https://github.com/mauricerenck/indieConnector/commit/e05b3ca58280b3dcc0b346c2bda58dea9556fd22))

# [1.8.0](https://github.com/mauricerenck/indieConnector/compare/v1.7.0...v1.8.0) (2023-07-12)


### Features

* skip sending mentions to own host ([252c7ba](https://github.com/mauricerenck/indieConnector/commit/252c7ba86588ee36c13405bbc958f8d013d52d78))

# [1.7.0](https://github.com/mauricerenck/indieConnector/compare/v1.6.3...v1.7.0) (2023-01-20)


### Features

* added migrations ([e69f407](https://github.com/mauricerenck/indieConnector/commit/e69f407011ab8fa905ba6bab8504c8ef55e297ab))
* controll webmention sending on page ([a4672a1](https://github.com/mauricerenck/indieConnector/commit/a4672a1fed2825665f3c9b7845c96d67308b0c4c))
* page mock ([2b7aa1a](https://github.com/mauricerenck/indieConnector/commit/2b7aa1adea344176dc4b5827511f8f0278cc49b3))
* webmention outbox stats ([ad6d6e1](https://github.com/mauricerenck/indieConnector/commit/ad6d6e19d08b5646348abc4f8f2e398b7da9d09b))

## [1.6.3](https://github.com/mauricerenck/indieConnector/compare/v1.6.2...v1.6.3) (2022-12-13)


### Bug Fixes

* add activity pub snippet for more reliable webmention ([66fa078](https://github.com/mauricerenck/indieConnector/commit/66fa078f2a93cb3300d9ef8e4bffe52079908628))

## [1.6.2](https://github.com/mauricerenck/indieConnector/compare/v1.6.1...v1.6.2) (2022-12-13)


### Bug Fixes

* set activitypub webmention url earlier ([76d8076](https://github.com/mauricerenck/indieConnector/commit/76d80764a439680b0c2ed6eebb981823a8795287))

## [1.6.1](https://github.com/mauricerenck/indieConnector/compare/v1.6.0...v1.6.1) (2022-12-13)


### Bug Fixes

* bump version ([da2bf23](https://github.com/mauricerenck/indieConnector/commit/da2bf231c0d8d5fb3d7f76691322ab943b2c968f))

# [1.6.0](https://github.com/mauricerenck/indieConnector/compare/v1.5.0...v1.6.0) (2022-12-09)


### Features

* activityPub integration ([#6](https://github.com/mauricerenck/indieConnector/issues/6)) ([f7e66a2](https://github.com/mauricerenck/indieConnector/commit/f7e66a21772ffc5566407b973c79a64964ecfb3f))

# [1.5.0](https://github.com/mauricerenck/indieConnector/compare/v1.4.2...v1.5.0) (2022-10-15)


### Features

* kirby 3.8 deprectations removed ([6ec930f](https://github.com/mauricerenck/indieConnector/commit/6ec930f7caf0158b6e7b9a71c69f6aba4ffaf979))

## [1.4.2](https://github.com/mauricerenck/indieConnector/compare/v1.4.1...v1.4.2) (2022-04-07)


### Bug Fixes

* javascript php mixup ([82ea703](https://github.com/mauricerenck/indieConnector/commit/82ea7032771beac91e7f3b973bb098ff01d800e0))

## [1.4.1](https://github.com/mauricerenck/indieConnector/compare/v1.4.0...v1.4.1) (2022-04-07)


### Bug Fixes

* sqlite queries need a leading zero ([50240a0](https://github.com/mauricerenck/indieConnector/commit/50240a07b036c165a0784c80b0e0c6bd299f25d3))

# [1.4.0](https://github.com/mauricerenck/indieConnector/compare/v1.3.1...v1.4.0) (2022-04-07)


### Features

* cycle through months ([cbf6f09](https://github.com/mauricerenck/indieConnector/commit/cbf6f09d2ce034baebeccade03a220413d32820a))

## [1.3.1](https://github.com/mauricerenck/indieConnector/compare/v1.3.0...v1.3.1) (2022-02-26)


### Bug Fixes

* enable hooks ([7265757](https://github.com/mauricerenck/indieConnector/commit/726575793e86a2076d37ddf63c1b6945cde9af7d))

# [1.3.0](https://github.com/mauricerenck/indieConnector/compare/v1.2.4...v1.3.0) (2022-02-26)


### Features

* sending webmentions ([f89bb54](https://github.com/mauricerenck/indieConnector/commit/f89bb5433cfbef01b94764d55638adb02e3c0e3a))

## [1.2.4](https://github.com/mauricerenck/indieConnector/compare/v1.2.3...v1.2.4) (2022-02-18)


### Bug Fixes

* missing respone class ([1a2c2d9](https://github.com/mauricerenck/indieConnector/commit/1a2c2d982dec29d74ffd323bfb318e51416d0ac1))

## [1.2.3](https://github.com/mauricerenck/indieConnector/compare/v1.2.2...v1.2.3) (2022-01-08)


### Bug Fixes

* Minor typo ([#4](https://github.com/mauricerenck/indieConnector/issues/4)) ([b645e2e](https://github.com/mauricerenck/indieConnector/commit/b645e2e28a94850a5dd53c903b426ad274b7a0a3))

## [1.2.2](https://github.com/mauricerenck/indieConnector/compare/v1.2.1...v1.2.2) (2021-12-18)


### Bug Fixes

* wrong retun signature in known networks ([60d47ab](https://github.com/mauricerenck/indieConnector/commit/60d47ab9fcdc560d49b9be42891dfef872d3c5b7))

## [1.2.1](https://github.com/mauricerenck/indieConnector/compare/v1.2.0...v1.2.1) (2021-11-17)


### Bug Fixes

* stability level for k3.6 dependency ([6cf1ecd](https://github.com/mauricerenck/indieConnector/commit/6cf1ecd2842314500271906492f81f19a8e6931f))

# [1.2.0](https://github.com/mauricerenck/indieConnector/compare/v1.1.0...v1.2.0) (2021-11-15)


### Features

* breaking changes! New hook name ([dc240a3](https://github.com/mauricerenck/indieConnector/commit/dc240a3594ba374fd785ae89d6d5886dc9587ea7))

# [1.1.0](https://github.com/mauricerenck/indieConnector/compare/v1.0.0...v1.1.0) (2021-11-12)


### Features

* README ([5a5d281](https://github.com/mauricerenck/indieConnector/commit/5a5d281415cff8fa02231779064d8d4e81b4d92e))

# 1.0.0 (2021-11-12)


### Features

* automatic releases ([69d630e](https://github.com/mauricerenck/indieConnector/commit/69d630ec991de4343c0169556b5e1e9d08be6780))
* core setup ([b5dc148](https://github.com/mauricerenck/indieConnector/commit/b5dc1482e75c57e5194037ba4124ec0635a22ce5))
* INIT ([0f7a192](https://github.com/mauricerenck/indieConnector/commit/0f7a1923ae35e90c5c4fa90033657a0755609119))
