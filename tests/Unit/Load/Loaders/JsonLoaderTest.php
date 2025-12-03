<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Load\Loaders;

use InvalidArgumentException;
use Phetl\Contracts\LoaderInterface;
use Phetl\Load\Loaders\JsonLoader;
use PHPUnit\Framework\TestCase;

final class JsonLoaderTest extends TestCase
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

    public function test_it_implements_loader_interface(): void
    {
        $loader = new JsonLoader($this->testFile);

        $this->assertInstanceOf(LoaderInterface::class, $loader);
    }

    public function test_it_loads_data_to_json_file(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
        ];

        $loader = new JsonLoader($this->testFile);
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(2, $rowCount);
        $this->assertFileExists($this->testFile);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals(['name' => 'Alice', 'age' => 30, 'city' => 'NYC'], $decoded[0]);
        $this->assertEquals(['name' => 'Bob', 'age' => 25, 'city' => 'LA'], $decoded[1]);
    }

    public function test_it_handles_pretty_print_option(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $loader = new JsonLoader($this->testFile, prettyPrint: true);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        // Pretty print adds newlines and indentation
        $this->assertStringContainsString("\n", $content);
        $this->assertStringContainsString('    ', $content);
    }

    public function test_it_handles_empty_data(): void
    {
        $loader = new JsonLoader($this->testFile);
        $rowCount = $loader->load([])->rowCount();

        $this->assertEquals(0, $rowCount);
        $this->assertFileExists($this->testFile);
        $this->assertEquals('[]', file_get_contents($this->testFile));
    }

    public function test_it_handles_header_only(): void
    {
        $data = [
            ['name', 'age', 'city'],
        ];

        $loader = new JsonLoader($this->testFile);
        $rowCount = $loader->load($data)->rowCount();

        $this->assertEquals(0, $rowCount);
        $content = file_get_contents($this->testFile);
        $this->assertEquals('[]', $content);
    }

    public function test_it_overwrites_existing_file(): void
    {
        file_put_contents($this->testFile, '{"old": "content"}');

        $data = [
            ['name'],
            ['Alice'],
        ];

        $loader = new JsonLoader($this->testFile);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('old', $content);
        $decoded = json_decode($content, true);
        $this->assertEquals([['name' => 'Alice']], $decoded);
    }

    public function test_it_handles_null_values(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', null, 'NYC'],
            ['Bob', 25, null],
        ];

        $loader = new JsonLoader($this->testFile);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);

        $this->assertNull($decoded[0]['age']);
        $this->assertNull($decoded[1]['city']);
    }

    public function test_it_handles_nested_arrays(): void
    {
        $data = [
            ['name', 'metadata'],
            ['Alice', ['role' => 'admin', 'level' => 5]],
        ];

        $loader = new JsonLoader($this->testFile);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);

        $this->assertEquals(['role' => 'admin', 'level' => 5], $decoded[0]['metadata']);
    }

    public function test_it_validates_writable_directory(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Directory permission test not applicable on Windows');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Directory is not writable');

        new JsonLoader('/nonexistent/directory/file.json');
    }

    public function test_it_creates_directory_if_needed(): void
    {
        $tempDir = sys_get_temp_dir() . '/phetl_test_' . uniqid();
        $filePath = $tempDir . '/output.json';

        $data = [
            ['name'],
            ['Alice'],
        ];

        $loader = new JsonLoader($filePath);
        $loader->load($data);

        $this->assertFileExists($filePath);

        // Cleanup
        unlink($filePath);
        rmdir($tempDir);
    }

    public function test_it_preserves_numeric_values(): void
    {
        $data = [
            ['name', 'age', 'score'],
            ['Alice', 30, 95.5],
            ['Bob', 25, 88.3],
        ];

        $loader = new JsonLoader($this->testFile);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);

        $this->assertIsInt($decoded[0]['age']);
        $this->assertIsFloat($decoded[0]['score']);
        $this->assertEquals(30, $decoded[0]['age']);
        $this->assertEquals(95.5, $decoded[0]['score']);
    }

    public function test_it_handles_boolean_values(): void
    {
        $data = [
            ['name', 'active'],
            ['Alice', true],
            ['Bob', false],
        ];

        $loader = new JsonLoader($this->testFile);
        $loader->load($data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded);

        $this->assertIsBool($decoded[0]['active']);
        $this->assertTrue($decoded[0]['active']);
        $this->assertFalse($decoded[1]['active']);
    }
}
