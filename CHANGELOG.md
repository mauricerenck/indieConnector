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
