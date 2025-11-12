<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\JsonExtractor;
use PHPUnit\Framework\TestCase;

final class JsonExtractorTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFile = sys_get_temp_dir() . '/phetl_test_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        parent::tearDown();
    }

    public function test_it_implements_extractor_interface(): void
    {
        file_put_contents($this->testFile, '[]');

        $extractor = new JsonExtractor($this->testFile);

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
    }

    public function test_it_extracts_json_array_of_objects(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
    }

    public function test_it_validates_file_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON file does not exist');

        new JsonExtractor('/nonexistent/file.json');
    }

    public function test_it_validates_file_is_readable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission test not applicable on Windows');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON file is not readable');

        touch($this->testFile);
        chmod($this->testFile, 0o000);

        try {
            new JsonExtractor($this->testFile);
        } finally {
            chmod($this->testFile, 0o644);
        }
    }

    public function test_it_validates_valid_json(): void
    {
        file_put_contents($this->testFile, 'invalid json {[}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON in file');

        $extractor = new JsonExtractor($this->testFile);
        iterator_to_array($extractor->extract());
    }

    public function test_it_validates_json_is_array(): void
    {
        file_put_contents($this->testFile, '{"key": "value"}');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON file must contain an array of objects');

        $extractor = new JsonExtractor($this->testFile);
        iterator_to_array($extractor->extract());
    }

    public function test_it_yields_rows_lazily(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'age' => 30],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);
        $iterator = $extractor->extract();

        $this->assertInstanceOf(\Generator::class, $iterator);
    }

    public function test_it_handles_empty_array(): void
    {
        file_put_contents($this->testFile, '[]');

        $extractor = new JsonExtractor($this->testFile);
        $result = iterator_to_array($extractor->extract());

        $this->assertCount(0, $result);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'age' => 30],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);

        $result1 = iterator_to_array($extractor->extract());
        $result2 = iterator_to_array($extractor->extract());

        $this->assertEquals($result1, $result2);
    }

    public function test_it_handles_inconsistent_fields(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'age' => 30, 'city' => 'NYC'],
            ['name' => 'Bob', 'age' => 25],
            ['name' => 'Charlie', 'city' => 'LA'],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);
        $result = iterator_to_array($extractor->extract());

        // Header should contain all unique fields
        $this->assertCount(4, $result);
        $this->assertContains('name', $result[0]);
        $this->assertContains('age', $result[0]);
        $this->assertContains('city', $result[0]);
    }

    public function test_it_preserves_nested_structures(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'metadata' => ['role' => 'admin', 'level' => 5]],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);
        $result = iterator_to_array($extractor->extract());

        $this->assertEquals(['name', 'metadata'], $result[0]);
        $this->assertEquals('Alice', $result[1][0]);
        $this->assertEquals(['role' => 'admin', 'level' => 5], $result[1][1]);
    }

    public function test_it_handles_numeric_indices(): void
    {
        $json = json_encode([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);
        file_put_contents($this->testFile, $json);

        $extractor = new JsonExtractor($this->testFile);
        $result = iterator_to_array($extractor->extract());

        // All rows should be numerically indexed arrays
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey(0, $result[0]);
    }
}
