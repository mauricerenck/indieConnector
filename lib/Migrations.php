<?php

namespace mauricerenck\IndieConnector;

use Exception;
use Kirby\Database\Database;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

class Migrations
{
    public function migrate()
    {
        $statsEnabled = option('mauricerenck.indieConnector.stats.enabled', false);
        $queueEnabled = option('mauricerenck.indieConnector.queue.enabled', false);
        $disableMigrations = option('mauricerenck.indieConnector.disableMigrations', false);

        if (($statsEnabled || $queueEnabled) && !$disableMigrations) {
            $pluginPath = str_replace('lib', '', __DIR__);
            $migrationPath = $pluginPath . '/migrations/';

            if (!file_exists(option('mauricerenck.indieConnector.sqlitePath', '.sqlite/'))) {
                mkdir(option('mauricerenck.indieConnector.sqlitePath', '.sqlite/'));
            }

            $db = $this->connect();

            if ($db === null || $db === false) {
                return false;
            }

            $versionResult = $db->query('SELECT version FROM migrations ORDER BY version DESC LIMIT 1');

            if (!Dir::exists($migrationPath)) {
                return false;
            }

            if (!Dir::isReadable($migrationPath)) {
                return false;
            }

            $migrations = Dir::files($migrationPath, ['.', '..'], true);

            foreach ($migrations as $migration) {
                $version = str_replace(['database_', '.sql'], ['', ''], F::filename($migration));
                $migrationStructures = explode(';', F::read($migration));
                $lastMigration = 0;

                if ($versionResult !== false) {
                    $lastMigration = (int) $versionResult->data[0]->version;
                }

                if ($lastMigration < $version) {
                    foreach ($migrationStructures as $query) {
                        if (!empty(trim($query))) {
                            $db->execute(trim($query));
                        }
                    }
                }

                $db->execute('INSERT INTO migrations (version) VALUES (' . $version . ')');
            }
        }
    }

    private function connect()
    {
        try {
            $sqlitePath = option('mauricerenck.indieConnector.sqlitePath');

            return new Database([
                'type' => 'sqlite',
                'database' => $sqlitePath . 'indieConnector.sqlite',
            ]);
        } catch (Exception $e) {
            echo 'Could not connect to Database: ', $e->getMessage(), "\n";
            return null;
        }
    }
}
