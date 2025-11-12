<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use InvalidArgumentException;
use PDO;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\DatabaseExtractor;
use PHPUnit\Framework\TestCase;

final class DatabaseExtractorTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create test table
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                age INTEGER,
                city TEXT
            )
        ');

        // Insert test data
        $this->pdo->exec("
            INSERT INTO users (name, age, city) VALUES
            ('Alice', 30, 'NYC'),
            ('Bob', 25, 'LA'),
            ('Charlie', 35, 'Chicago')
        ");
    }

    public function test_it_implements_extractor_interface(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM users');

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
    }

    public function test_it_extracts_database_query_results(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name, age FROM users ORDER BY id');
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(4, $result); // Header + 3 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
        $this->assertEquals(['Charlie', 35], $result[3]);
    }

    public function test_it_validates_non_empty_query(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query cannot be empty');

        new DatabaseExtractor($this->pdo, '');
    }

    public function test_it_yields_rows_lazily(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM users');
        $iterator = $extractor->extract();

        $this->assertInstanceOf(\Generator::class, $iterator);
    }

    public function test_it_handles_parameterized_queries(): void
    {
        $extractor = new DatabaseExtractor(
            $this->pdo,
            'SELECT name, age FROM users WHERE age > :min_age ORDER BY id',
            ['min_age' => 25]
        );
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(3, $result); // Header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Charlie', 35], $result[2]);
    }

    public function test_it_handles_empty_result_set(): void
    {
        $extractor = new DatabaseExtractor(
            $this->pdo,
            'SELECT * FROM users WHERE age > 100'
        );
        $result = iterator_to_array($extractor->extract());

        // Should still have header row
        $this->assertCount(1, $result);
        $this->assertEquals(['id', 'name', 'age', 'city'], $result[0]);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name FROM users ORDER BY id');

        $result1 = iterator_to_array($extractor->extract());
        $result2 = iterator_to_array($extractor->extract());

        $this->assertEquals($result1, $result2);
    }

    public function test_it_handles_all_column_types(): void
    {
        // Create table with various types
        $this->pdo->exec('
            CREATE TABLE test_types (
                int_col INTEGER,
                text_col TEXT,
                real_col REAL,
                blob_col BLOB
            )
        ');

        $this->pdo->exec("
            INSERT INTO test_types VALUES (42, 'hello', 3.14, X'DEADBEEF')
        ");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM test_types');
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(2, $result);
        $this->assertEquals(['int_col', 'text_col', 'real_col', 'blob_col'], $result[0]);
        $this->assertEquals(42, $result[1][0]);
        $this->assertEquals('hello', $result[1][1]);
        $this->assertEquals(3.14, $result[1][2]);
    }

    public function test_it_preserves_null_values(): void
    {
        $this->pdo->exec("INSERT INTO users (name, age, city) VALUES ('Dave', NULL, NULL)");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name, age, city FROM users WHERE name = "Dave"');
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(2, $result);
        $this->assertEquals(['Dave', null, null], $result[1]);
    }

    public function test_it_handles_special_characters_in_data(): void
    {
        $this->pdo->exec("INSERT INTO users (name, age, city) VALUES ('O''Brien', 28, 'San Francisco')");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name FROM users WHERE name = \'O\'\'Brien\'');
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(2, $result);
        $this->assertEquals(["O'Brien"], $result[1]);
    }

    public function test_it_throws_exception_on_invalid_query(): void
    {
        $this->expectException(\PDOException::class);

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM nonexistent_table');
        iterator_to_array($extractor->extract());
    }
}
