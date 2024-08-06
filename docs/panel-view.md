# Enable panel overview

**This feature requires a SQLite database!** Learn how to set it up [here](/docs/database.md).

IndieConnector comes with a nice overview of all your received webmentions. To enable this feature, set the `stats` option in your `config.php` to true.

Example setup:

```
'mauricerenck.indieConnector.stats.enabled' => true,
```

## Do not track

You can configure a list of hosts that should not be tracked. To enable this feature, set the `stats.doNotTrack` option in your `config.php` to an array of hosts. By default it includes `'fed.brid.gy'` in case you are using the activityPub feature.

```
'mauricerenck.indieConnector.stats.doNotTrack' => ['hostname.example.com'],
```