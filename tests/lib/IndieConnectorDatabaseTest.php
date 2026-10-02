<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use InvalidArgumentException;

class IndieConnectorDatabaseTest extends TestCase
{
    private IndieConnectorDatabase $db;
    private $defaultOptions;
    private $dir;

    public function setUp(): void
    {
        // setup temp dir for sqlite test database
        // $dir = sys_get_temp_dir() . '/indieconnector-test-' . uniqid() . '/';
        $this->dir = '.sqlite/indieconnector-test-' . uniqid() . '/';
        mkdir($this->dir, 0777, true);

        $this->defaultOptions = new OptionHandlerMock([
            'sqlitePath' => $this->dir,
        ]);

        // setup database
        $this->db = new IndieConnectorDatabase(options: $this->defaultOptions);
        $this->db->query(
            'CREATE TABLE items (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name TEXT UNIQUE,
                        foo TEXT,
                        counter INTEGER DEFAULT 0
                    )'
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '*') as $file) {
            unlink($file);
        }
        rmdir($this->dir);

        parent::tearDown();
    }

    #[Group('database')]
    #[TestDox('assertIdentifier - should handle valid identifier')]
    public function testAssertIdentifierHandlesValidIdentifier(): void
    {
        $this->expectNotToPerformAssertions();
        $this->db->assertIdentifier('valid_identifier');
    }

    #[Group('database')]
    #[TestDox('assertIdentifier - should handle sql injection')]
    public function testAssertIdentifierHandlesSqlInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->assertIdentifier("'; DROP TABLE items; --");
    }


    #[Group('database')]
    #[TestDox('insert - should insert row into database')]
    public function testInsertStoresRow(): void
    {
        $this->assertTrue(
            $this->db->insert('items', [
                'name'    => 'foo',
                'counter' => 5,
            ])
        );

        $rows = $this->db->select('items', [
            'name',
            'counter',
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('foo', $rows->first()->name());
    }

    #[Group('database')]
    #[TestDox('insert - should return false on unique violation')]
    public function testInsertReturnsFalseOnUniqueViolation(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertFalse($this->db->insert('items', ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('insert - should return false for unknown table')]
    public function testInsertReturnsFalseForUnknownTable(): void
    {
        $this->assertFalse($this->db->insert('nope', ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('insert - should handle quotes in values')]
    public function testInsertHandlesQuotesInValues(): void
    {
        $this->assertTrue($this->db->insert('items', ['name' => 'O\'Reilly "x"']));
        $this->assertSame('O\'Reilly "x"', $this->db->select('items', ['name'])->first()->name);
    }

    #[Group('database')]
    #[TestDox('insert - should handle sql injection')]
    public function testInsertHandlesSqlInjection(): void
    {
        $this->assertFalse($this->db->insert('items', ["'; DROP TABLE items; --" => 'hello']));
    }


    #[Group('database')]
    #[TestDox('delete - should delete single row')]
    public function testDeleteDeletesSingleRow(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertTrue($this->db->delete('items', ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('delete - should delete multiple rows')]
    public function testDeleteDeletesMultipleRows(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->db->insert('items', ['name' => 'bar']);
        $this->assertTrue($this->db->delete('items', ['name' => 'foo']));
        $this->assertTrue($this->db->delete('items', ['name' => 'bar']));
    }

    #[Group('database')]
    #[TestDox('delete - should handle NULL values')]
    public function testDeleteHandlesNullValues(): void
    {
        $this->db->insert('items', ['name' => null]);
        $this->assertTrue($this->db->delete('items', ['name' => null]));
    }

    #[Group('database')]
    #[TestDox('delete - should handle sql injection')]
    public function testDeleteHandlesSqlInjection(): void
    {
        $this->db->insert('items', ['name' => null]);
        $this->assertFalse($this->db->delete("'; DROP TABLE items; --", ['name' => null]));
    }

    #[Group('database')]
    #[TestDox('delete - should handle empty where clause')]
    public function testDeleteHandlesEmptyWhereClause(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertFalse($this->db->delete('items', []));
    }

    #[Group('database')]
    #[TestDox('update - should update single entry')]
    public function testUpdateUpdatesSingleEntry(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertTrue($this->db->update('items', ['name' => 'bar'], ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('update - should handle quotes in values')]
    public function testUpdateHandlesQuotesInValues(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertTrue($this->db->update('items', ['name' => 'O\'Reilly "x"'], ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('update - should update multiple entries')]
    public function testUpdateUpdatesMultipleEntries(): void
    {
        $this->db->insert('items', ['name' => 'foo', 'foo' => 'bar']);
        $this->db->insert('items', ['name' => 'bar', 'foo' => 'bar']);
        $this->assertTrue($this->db->update('items', ['name' => 'baz'], ['foo' => 'bar']));
    }

    #[Group('database')]
    #[TestDox('update - should handle empty values')]
    public function testUpdateHandlesEmptyValues(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertFalse($this->db->update('items', [], ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('update - should handle sql injection')]
    public function testUpdateHandlesSqlInjection(): void
    {
        $this->db->insert('items', ['name' => 'foo']);
        $this->assertFalse($this->db->update('items', [], ["name" => "'; DROP TABLE items; --"]));
    }

    #[Group('database')]
    #[TestDox('upsert - should create single entry')]
    public function testUpsertCreatesSingleEntry(): void
    {
        $this->db->upsert('items', ['name' => 'foo'], 'name');
        $this->assertTrue($this->db->update('items', ['name' => 'bar'], ['name' => 'foo']));
    }

    #[Group('database')]
    #[TestDox('upsert - should update single entry')]
    public function testUpsertUpdatesSingleEntry(): void
    {
        $this->db->upsert('items', ['name' => 'foo'], 'name');
        $this->assertTrue($this->db->upsert('items', ['name' => 'bar'], 'name'));
    }

    #[Group('database')]
    #[TestDox('upsert - should handle empty payload')]
    public function testUpsertHandlesEmptyPayload(): void
    {
        $this->assertFalse($this->db->upsert('items', [], 'name'));
    }

    #[Group('database')]
    #[TestDox('upsert - should handle sql injection')]
    public function testUpsertHandlesSqlInjection(): void
    {
        $this->assertFalse($this->db->upsert('items', ["'; DROP TABLE items; --" => 'foo'], 'name'));
    }
}
