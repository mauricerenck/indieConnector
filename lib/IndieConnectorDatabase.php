<?php

namespace mauricerenck\IndieConnector;

use Kirby\Database\Database;
use Exception;
use InvalidArgumentException;

class IndieConnectorDatabase
{
    protected OptionHandler $options;
    private Database  $db;

    public function __construct(
        ?OptionHandler $options = null,
    ) {
        $this->options = $options ?? new OptionHandler();
        $this->db = $this->connect();
    }

    public function connect()
    {
        try {
            $opt_sqlitePath = $this->options->get('sqlitePath');

            if (!$opt_sqlitePath) {
                return false;
            }

            $db = new Database([
                'type' => 'sqlite',
                'database' => $opt_sqlitePath . 'indieConnector.sqlite',
            ]);

            return $db;
        } catch (Exception $e) {
            return false;
        }
    }

    public function insert(string $table, array $payload): bool
    {

        /*
        EXAMPLE:
        $this->db->insert('items', [
            'name'    => 'foo',
            'counter' => 5,
        ])
        */
        try {
            if ($payload === []) {
                throw new InvalidArgumentException(
                    'The payload must not be empty.'
                );
            }

            // check for malformed identifiers
            $this->assertIdentifier($table);
            foreach (array_keys($payload) as $field) {
                $this->assertIdentifier($field);
            }


            $fields = array_keys($payload);
            $columns = implode(', ', $fields);
            $placeholders = implode(
                ', ',
                array_map(
                    fn(string $field): string => ':' . $field,
                    $fields
                )
            );

            $query = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)',
                $table,
                $columns,
                $placeholders
            );

            $result = $this->db->query($query, $payload);

            if (!$result) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function upsert(
        string $table,
        array $payload,
        string $uniqueField
    ): bool {

        /*
       EXAMPLE
       $this->db->upsert(
           'items',
           [
               'name'    => 'foo',
               'counter' => 5,
           ],
           'name'
       );
       */
        try {
            if ($payload === []) {
                throw new InvalidArgumentException(
                    'The payload must not be empty.'
                );
            }

            $this->assertIdentifier($table);
            $this->assertIdentifier($uniqueField);

            foreach (array_keys($payload) as $field) {
                $this->assertIdentifier($field);
            }

            if (!array_key_exists($uniqueField, $payload)) {
                throw new InvalidArgumentException(
                    "The unique field {$uniqueField} must be present in the payload."
                );
            }

            $fields = array_keys($payload);

            $columns = implode(', ', $fields);

            $placeholders = implode(
                ', ',
                array_map(
                    static fn(string $field): string => ':' . $field,
                    $fields
                )
            );

            $updateFields = array_filter(
                $fields,
                static fn(string $field): bool => $field !== $uniqueField
            );

            if ($updateFields === []) {
                $updateQuery = "{$uniqueField} = excluded.{$uniqueField}";
            } else {
                $updateQuery = implode(
                    ', ',
                    array_map(
                        static fn(string $field): string =>
                        "{$field} = excluded.{$field}",
                        $updateFields
                    )
                );
            }

            $query = sprintf(
                'INSERT INTO %s (%s) VALUES (%s)
             ON CONFLICT (%s) DO UPDATE SET %s',
                $table,
                $columns,
                $placeholders,
                $uniqueField,
                $updateQuery
            );

            $this->db->query($query, $payload);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }


    public function update(
        string $table,
        array $values,
        array $where
    ): bool {
        try {
            if ($values === []) {
                throw new InvalidArgumentException(
                    'You have to provide values to update.'
                );
            }

            if ($where === []) {
                throw new InvalidArgumentException(
                    'UPDATE without conditions is not allowed.'
                );
            }

            $this->assertIdentifier($table);

            $setParts = [];
            $whereParts = [];
            $bindings = [];

            foreach ($values as $field => $value) {
                $this->assertIdentifier($field);

                $parameter = ':set_' . $field;

                if ($value === null) {
                    $setParts[] = "{$field} = NULL";
                } else {
                    $setParts[] = "{$field} = {$parameter}";
                    $bindings[$parameter] = $value;
                }
            }

            foreach ($where as $field => $value) {
                $this->assertIdentifier($field);

                $parameter = ':where_' . $field;

                if ($value === null) {
                    $whereParts[] = "{$field} IS NULL";
                } else {
                    $whereParts[] = "{$field} = {$parameter}";
                    $bindings[$parameter] = $value;
                }
            }

            $query = sprintf(
                'UPDATE %s SET %s WHERE %s',
                $table,
                implode(', ', $setParts),
                implode(' AND ', $whereParts)
            );

            $this->db->query($query, $bindings);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function select(
        string $table,
        array $fields,
        string $where = '',
        ?int $limit = null,
        array $bindings = []
    ): mixed {

        /*
       EXAMPLE
       $this->indieDb->select(
           'table',
           ['id', 'fieldname'],
           'WHERE id = :id',
           ['id' => $id]
       )
       */
        try {
            $this->assertIdentifier($table);

            foreach ($fields as $field) {
                $this->assertIdentifier($field);
            }

            if ($limit !== null && $limit < 0) {
                throw new InvalidArgumentException(
                    'LIMIT must be a non-negative integer'
                );
            }

            $limitSql = $limit !== null
                ? ' LIMIT ' . $limit
                : '';

            $columns = implode(', ', $fields);

            $query = sprintf(
                'SELECT %s FROM %s %s%s',
                $columns,
                $table,
                $where !== '' ? $where . ' ' : '',
                $limitSql
            );

            return $this->db->query($query, $bindings);
        } catch (Exception $e) {
            return false;
        }
    }


    public function delete(string $table, array $where): bool
    {

        /*
        EXAMPLE:
        $this->db->delete('items', [
            'name'    => 'foo',
            'counter' => 5,
        ]);
        */
        try {
            if ($where === []) {
                throw new InvalidArgumentException(
                    'DELETE without conditions is not allowed.'
                );
            }

            $this->assertIdentifier($table);

            $conditions = [];
            $bindings = [];

            foreach ($where as $field => $value) {
                $this->assertIdentifier($field);

                $parameter = ':' . $field;

                if ($value === null) {
                    $conditions[] = "{$field} IS NULL";
                } else {
                    $conditions[] = "{$field} = {$parameter}";
                    $bindings[$parameter] = $value;
                }
            }

            $query = sprintf(
                'DELETE FROM %s WHERE %s',
                $table,
                implode(' AND ', $conditions)
            );

            $this->db->query($query, $bindings);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function truncate(string $table): bool
    {
        /*
        EXAMPLE:
        $this->db->truncate('items');
        */
        try {
            $this->assertIdentifier($table);
            $this->db->query("DELETE FROM {$table}");

            return true;
        } catch (Exception $e) {
            return false;
        }
    }



    // FIXME: DEPRECATED?
    public function query(string $query): mixed
    {
        try {
            return $this->db->query($query);
        } catch (Exception $e) {
            return false;
        }
    }

    // FIXME: DEPRECATED
    public function convertValuesToSaveDbString(array $values): array
    {
        return array_map(function ($value) {
            return $this->convertToSaveDbString($value);
        }, $values);
    }
    // FIXME: DEPRECATED
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

    public function assertIdentifier(string $identifier): void
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException(
                "Invalid identifier: {$identifier}"
            );
        }
    }
}
