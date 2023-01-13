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
        if (option('mauricerenck.indieConnector.stats', false)) {
            $pluginPath = str_replace('utils', '', __DIR__);
            $migrationPath = $pluginPath . '/migrations/';

            // step 1 connect to database
            $db = $this->connect();
            $versionResult = $db->query('SELECT version FROM migrations ORDER BY version DESC LIMIT 1');

            // step 3 run through all files
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
                    $lastMigration = (int)$versionResult->data[0]->version;
                }

                if ($lastMigration < $version) {
                    foreach ($migrationStructures as $query) {
                        $db->execute(trim($query));
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