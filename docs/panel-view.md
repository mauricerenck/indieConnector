# Enable panel overview

_This feature required sqlite to be available by your hoster. Most hosters support this by default, but you might want to make sure it's enabled._

IndieConnector comes with a nice overview of all your received webmentions. To enable this feature, set the `stats` option in your `config.php` to true and set a path where the database file should be stored. **Make sure this path exists, it will not be created**. The path is relative to your kirby root.

Example setup:

```
'mauricerenck.indieConnector.stats.enabled' => true,
'mauricerenck.indieConnector.sqlitePath' => 'content/.sqlite/',
```

## Do not track

You can configure a list of hosts that should not be tracked. To enable this feature, set the `stats.doNotTrack` option in your `config.php` to an array of hosts. By default it includes `'fed.brid.gy'` in case you are using the activityPub feature.
