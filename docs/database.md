# Databse

Some of the IndieConnector features require a database. Only SQLite is supported in the moment. To set it up, go to `site/config/config.php` and set the path to the sqlite file, ending with a `/`. The database will be created automatically as soon as one of the features requiring a database is enabled. Make sure the directory is writeable. The content directory might be good place, because you probably already create backups for it.

Example setup:

```
'mauricerenck.indieConnector.sqlitePath' => 'content/.sqlite/',
```

## Migrations

As soon as the plugin is loaded by Kirby and at least one of the database features is enabled, IndieConnector will run all the migrations automatically. This will happen every time Kirby loads the plugins. Normally this will be no problem, as the plugin only runs new migrations - but it still have to query the database. If you do not want this, you can disable automatic migrations by setting:


```
'mauricerenck.indieConnector.disableMigrations' => true,
```

Please be aware that this can lead to errors or missing data if you do not run migrations after every update of the plugin manually!