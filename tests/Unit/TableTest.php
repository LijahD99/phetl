<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit;

use PDO;
use Phetl\Table;
use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    private string $testDir;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/phetl_table_test_' . uniqid();
        mkdir($this->testDir, 0o777, true);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->testDir);
        }
    }

    public function test_it_creates_table_from_array(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);

        $this->assertInstanceOf(Table::class, $table);
        $this->assertEquals($data, $table->toArray());
    }

    public function test_it_creates_table_from_csv(): void
    {
        $csvFile = $this->testDir . '/test.csv';
        file_put_contents($csvFile, "name,age\nAlice,30\nBob,25");

        $table = Table::fromCsv($csvFile);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_creates_table_from_json(): void
    {
        $jsonFile = $this->testDir . '/test.json';
        file_put_contents($jsonFile, json_encode([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]));

        $table = Table::fromJson($jsonFile);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_creates_table_from_database(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');
        $this->pdo->exec("INSERT INTO users VALUES ('Alice', 30), ('Bob', 25)");

        $table = Table::fromDatabase($this->pdo, 'SELECT * FROM users ORDER BY name');

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_is_iterable(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);

        $rows = [];
        foreach ($table as $row) {
            $rows[] = $row;
        }

        $this->assertEquals($data, $rows);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data);

        $first = [];
        foreach ($table as $row) {
            $first[] = $row;
        }

        $second = [];
        foreach ($table as $row) {
            $second[] = $row;
        }

        $this->assertEquals($first, $second);
    }

    public function test_it_looks_at_first_n_rows(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
            ['David', 40],
        ];

        $table = Table::fromArray($data);

        $result = $table->look(3);
        $this->assertCount(4, $result); // Header + 3 data rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
        $this->assertEquals(['Charlie', 35], $result[3]);
    }

    public function test_it_counts_rows(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);

        $this->assertEquals(2, $table->count()); // Excludes header
    }

    public function test_it_gets_header(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $table = Table::fromArray($data);

        $this->assertEquals(['name', 'age', 'city'], $table->header());
    }

    public function test_it_handles_header_only_table(): void
    {
        // Create a table with just header, no data rows
        $table = Table::fromArray([['id', 'name']]);

        $this->assertEquals(['id', 'name'], $table->header());
        $this->assertEquals(1, $table->count()); // Count includes header
    }

    public function test_it_writes_to_csv(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);
        $csvFile = $this->testDir . '/output.csv';

        $rowCount = $table->toCsv($csvFile)->rowCount();

        $this->assertEquals(2, $rowCount);
        $this->assertFileExists($csvFile);

        $content = file_get_contents($csvFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('Alice', $content);
    }

    public function test_it_writes_to_json(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);
        $jsonFile = $this->testDir . '/output.json';

        $rowCount = $table->toJson($jsonFile)->rowCount();

        $this->assertEquals(2, $rowCount);
        $this->assertFileExists($jsonFile);
    }

    public function test_it_writes_to_database(): void
    {
        $this->pdo->exec('CREATE TABLE users (name TEXT, age INTEGER)');

        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data);

        $rowCount = $table->toDatabase($this->pdo, 'users')->rowCount();

        $this->assertEquals(2, $rowCount);

        $stmt = $this->pdo->query('SELECT COUNT(*) FROM users');
        $this->assertNotFalse($stmt);
        $this->assertEquals(2, $stmt->fetchColumn());
    }

    public function test_it_creates_table_from_rest_api(): void
    {
        $table = Table::fromRestApi('https://api.example.com/users', [
            '_mock_response' => json_encode([
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]),
        ]);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['id', 'name'], $result[0]);
        $this->assertEquals([1, 'Alice'], $result[1]);
        $this->assertEquals([2, 'Bob'], $result[2]);
    }

    public function test_it_creates_table_from_rest_api_with_auth(): void
    {
        $table = Table::fromRestApi('https://api.example.com/users', [
            '_mock_response' => json_encode([
                ['id' => 1, 'name' => 'Alice'],
            ]),
            'auth' => [
                'type' => 'bearer',
                'token' => 'test-token',
            ],
        ]);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals([1, 'Alice'], $result[1]);
    }

    public function test_it_creates_table_from_rest_api_with_pagination(): void
    {
        $table = Table::fromRestApi('https://api.example.com/users', [
            '_mock_responses' => [
                json_encode([['id' => 1, 'name' => 'Alice']]),
                json_encode([['id' => 2, 'name' => 'Bob']]),
                json_encode([]),
            ],
            'pagination' => [
                'type' => 'offset',
                'page_size' => 1,
            ],
        ]);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals([1, 'Alice'], $result[1]);
        $this->assertEquals([2, 'Bob'], $result[2]);
    }

    public function test_it_creates_table_from_rest_api_with_mapping(): void
    {
        $table = Table::fromRestApi('https://api.example.com/users', [
            '_mock_response' => json_encode([
                'data' => [
                    ['user_id' => 1, 'profile' => ['name' => 'Alice']],
                ],
            ]),
            'mapping' => [
                'data_path' => 'data',
                'fields' => [
                    'id' => 'user_id',
                    'name' => 'profile.name',
                ],
            ],
        ]);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['id', 'name'], $result[0]);
        $this->assertEquals([1, 'Alice'], $result[1]);
    }
}
