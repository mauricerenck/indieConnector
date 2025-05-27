<?php

namespace mauricerenck\IndieConnector;

use Kirby\Database\Database;

use Exception;

class IndieConnectorDatabase
{
    private $db;

    public function __construct()
    {
        $this->db = $this->connect();
    }

    public function connect()
    {
        try {
            $sqlitePath = option('mauricerenck.indieConnector.sqlitePath');

            return $this->db = new Database([
                'type' => 'sqlite',
                'database' => $sqlitePath . 'indieConnector.sqlite',
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function insert(string $table, array $fields, array $values): bool
    {
        try {
            $values = $this->convertValuesToSaveDbString($values);
            $query =
                'INSERT INTO ' . $table . '(' . implode(',', $fields) . ') VALUES("' . implode('","', $values) . '")';

            $this->db->query($query);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function upsert(string $table, array $fields, array $values, string $uniqueField, string $set): bool
    {
        try {
            $values = $this->convertValuesToSaveDbString($values);
            $query =
                'INSERT INTO ' . $table . '(' . implode(',', $fields) . ') VALUES("' . implode('","', $values) . '")'
                . ' ON CONFLICT(' . $uniqueField . ') DO UPDATE SET ' . $set;

            $this->db->query($query);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function update(string $table, array $fields, array $values, string $filters): bool
    {
        try {
            $values = $this->convertValuesToSaveDbString($values);
            $query = 'UPDATE ' . $table . ' SET ';
            $query .= implode(
                ',',
                array_map(
                    function ($field, $value) {
                        return $field . '="' . $value . '"';
                    },
                    $fields,
                    $values
                )
            );
            $query .= ' ' . $filters;
            $this->db->query($query);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function select(string $table, array $fields, ?string $filters = ''): mixed
    {
        try {
            $query = 'SELECT ' . implode(',', $fields) . ' FROM ' . $table . ' ' . $filters;
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(string $table, string $filters): bool
    {
        try {
            $query = 'DELETE FROM ' . $table . ' ' . $filters;
            $this->db->query($query);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function query(string $query): mixed
    {
        try {
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    public function convertValuesToSaveDbString(array $values): array
    {
        return array_map(function ($value) {
            return $this->convertToSaveDbString($value);
        }, $values);
    }

    public function convertToSaveDbString(string $string): string
    {
        return $this->db->escape($string);
    }

    public function getFormattedDate(): string
    {
        return $this->formatDate(time());
    }

    public function formatDate(int $timestamp): string
    {
        return date('Y-m-d', $timestamp);
    }
}
