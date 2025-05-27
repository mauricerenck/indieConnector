# Infos for developers

Use `composer install` to install all dependencies. This creates an Kirby environment so you can test the plugin in the browser. You have to run a webserver for that and point it to the plugin root.

Before pushing changes run `composer run build-release` this will run all unittests and remove dev dependencies.

You can run the unittests by running: `composer test`. You can also use filters to only test specific things: `composer test -- --filter 'MastodonReceiver` for example
