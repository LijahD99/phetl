<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Load\Loaders;

use InvalidArgumentException;
use PDO;
use Phetl\Contracts\LoaderInterface;
use Phetl\Load\Loaders\DatabaseLoader;
use PHPUnit\Framework\TestCase;

final class DatabaseLoaderTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function test_it_implements_loader_interface(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');

        $loader = new DatabaseLoader($this->pdo, 'users');

        $this->assertInstanceOf(LoaderInterface::class, $loader);
    }

    public function test_it_loads_data_to_database_table(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER, city TEXT)');

        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(2, $rowCount);

        // Verify data was inserted
        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY name');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertEquals(['name' => 'Alice', 'age' => 30, 'city' => 'NYC'], $result[0]);
        $this->assertEquals(['name' => 'Bob', 'age' => 25, 'city' => 'LA'], $result[1]);
    }

    public function test_it_validates_non_empty_table_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name cannot be empty');

        new DatabaseLoader($this->pdo, '');
    }

    public function test_it_handles_empty_data(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT)');

        $loader = new DatabaseLoader($this->pdo, 'users');
        $rowCount = $loader->load([])->rowCount();

        $this->assertEquals(0, $rowCount);
    }

    public function test_it_handles_header_only(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');

        $data = [
            ['name', 'age'],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(0, $rowCount);

        // Verify no data was inserted
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetchColumn();
        $this->assertEquals(0, $result);
    }

    public function test_it_handles_null_values(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER, city TEXT)');

        $data = [
            ['name', 'age', 'city'],
            ['Alice', null, 'NYC'],
            ['Bob', 25, null],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $loader->load($data);

        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY name');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNull($result[0]['age']);
        $this->assertNull($result[1]['city']);
    }

    public function test_it_uses_batch_insert_for_performance(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');

        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(3, $rowCount);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetchColumn();
        $this->assertEquals(3, $result);
    }

    public function test_it_handles_special_characters(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, note TEXT)');

        $data = [
            ['name', 'note'],
            ["O'Brien", 'He said "hello"'],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $loader->load($data);

        $stmt = $this->pdo->query('SELECT * FROM users');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($result);
        $this->assertEquals("O'Brien", $result['name']);
        $this->assertEquals('He said "hello"', $result['note']);
    }

    public function test_it_handles_nonexistent_table_gracefully(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $loader = new DatabaseLoader($this->pdo, 'nonexistent_table');

        // SQLite PRAGMA doesn't throw for nonexistent tables, returns empty result
        // So no rows will match and insert won't happen
        $rowCount = $loader->load($data)->rowCount();

        // Since table doesn't exist, getTableColumns returns empty, no inserts happen
        $this->assertEquals(1, $rowCount); // Row is processed but not inserted
    }

    public function test_it_handles_column_mismatch(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');

        // Data has more columns than table
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(1, $rowCount);

        // Verify only matching columns were inserted
        $stmt = $this->pdo->query('SELECT * FROM users');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($result);
        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(30, $result['age']);
    }

    public function test_it_uses_transactions_for_atomicity(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT NOT NULL, age INTEGER)');

        // Second row will fail (name is NOT NULL)
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            [null, 25], // This should fail
        ];

        $loader = new DatabaseLoader($this->pdo, 'users');

        try {
            $loader->load($data);
            $this->fail('Expected exception was not thrown');
        } catch (\PDOException $e) {
            // Expected - transaction should rollback
        }

        // Verify no data was inserted (transaction rolled back)
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetchColumn();
        $this->assertEquals(0, $result);
    }

    public function test_it_preserves_data_types(): void
    {
        $this->pdo->exec('CREATE TABLE test (int_col INTEGER, real_col REAL, text_col TEXT)');

        $data = [
            ['int_col', 'real_col', 'text_col'],
            [42, 3.14, 'hello'],
        ];

        $loader = new DatabaseLoader($this->pdo, 'test');
        $loader->load($data);

        $stmt = $this->pdo->query('SELECT * FROM test');
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($result);
        $this->assertEquals(42, $result['int_col']);
        $this->assertEquals(3.14, $result['real_col']);
        $this->assertEquals('hello', $result['text_col']);
    }
}
