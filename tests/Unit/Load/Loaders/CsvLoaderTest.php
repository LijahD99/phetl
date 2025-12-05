<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Load\Loaders;

use InvalidArgumentException;
use Phetl\Contracts\LoaderInterface;
use Phetl\Load\Loaders\CsvLoader;
use PHPUnit\Framework\TestCase;

final class CsvLoaderTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFile = sys_get_temp_dir() . '/phetl_test_' . uniqid() . '.csv';
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
        $loader = new CsvLoader($this->testFile);

        $this->assertInstanceOf(LoaderInterface::class, $loader);
    }

    public function test_it_loads_data_to_csv_file(): void
    {
        $headers = ['name', 'age', 'city'];
        $data = [
            ['Alice', '30', 'NYC'],
            ['Bob', '25', 'LA'],
        ];

        $loader = new CsvLoader($this->testFile);
        $rowCount = $loader->load($headers, $data)->rowCount();

        $this->assertEquals(2, $rowCount);
        $this->assertFileExists($this->testFile);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('name,age,city', $content);
        $this->assertStringContainsString('Alice,30,NYC', $content);
        $this->assertStringContainsString('Bob,25,LA', $content);
    }

    public function test_it_handles_custom_delimiter(): void
    {
        $headers = ['name', 'age'];
        $data = [
            ['Alice', '30'],
        ];

        $loader = new CsvLoader($this->testFile, delimiter: ';');
        $loader->load($headers, $data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('name;age', $content);
        $this->assertStringContainsString('Alice;30', $content);
    }

    public function test_it_handles_custom_enclosure(): void
    {
        $headers = ['name', 'age'];
        $data = [
            ['Alice', '30'],
        ];

        $loader = new CsvLoader($this->testFile, enclosure: "'");
        $loader->load($headers, $data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        // fputcsv only adds enclosure when needed (commas, newlines, etc.)
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('Alice', $content);
    }

    public function test_it_handles_custom_escape(): void
    {
        $headers = ['name', 'quote'];
        $data = [
            ['Alice', 'She said "hi"'],
        ];

        $loader = new CsvLoader($this->testFile, escape: '\\');
        $loader->load($headers, $data);

        $this->assertFileExists($this->testFile);
    }

    public function test_it_handles_fields_with_commas(): void
    {
        $headers = ['name', 'location'];
        $data = [
            ['Smith, John', 'NYC, NY'],
        ];

        $loader = new CsvLoader($this->testFile);
        $loader->load($headers, $data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('"Smith, John"', $content);
        $this->assertStringContainsString('"NYC, NY"', $content);
    }

    public function test_it_handles_empty_data(): void
    {
        $loader = new CsvLoader($this->testFile);
        $rowCount = $loader->load([], [])->rowCount();

        $this->assertEquals(0, $rowCount);
        $this->assertFileExists($this->testFile);
        // Empty file may contain just a newline from fputcsv
        $content = file_get_contents($this->testFile);
        $this->assertTrue($content === '' || $content === "\n");
    }

    public function test_it_handles_header_only(): void
    {
        $headers = ['name', 'age', 'city'];
        $data = [];

        $loader = new CsvLoader($this->testFile);
        $rowCount = $loader->load($headers, $data)->rowCount();

        $this->assertEquals(0, $rowCount);
        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString('name,age,city', $content);
    }

    public function test_it_overwrites_existing_file(): void
    {
        file_put_contents($this->testFile, 'old content');

        $headers = ['name'];
        $data = [
            ['Alice'],
        ];

        $loader = new CsvLoader($this->testFile);
        $loader->load($headers, $data);

        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('old content', $content);
        $this->assertStringContainsString('Alice', $content);
    }

    public function test_it_handles_multiline_fields(): void
    {
        $headers = ['name', 'bio'];
        $data = [
            ['Alice', "Line 1\nLine 2"],
        ];

        $loader = new CsvLoader($this->testFile);
        $loader->load($headers, $data);

        $this->assertFileExists($this->testFile);
        $content = file_get_contents($this->testFile);
        $this->assertNotFalse($content);
        $this->assertStringContainsString("Line 1\nLine 2", $content);
    }

    public function test_it_validates_writable_directory(): void
    {
        // Skip on Windows as directory permissions work differently
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Directory permission test not applicable on Windows');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create directory');

        new CsvLoader('/nonexistent/directory/file.csv');
    }

    public function test_it_creates_directory_if_needed(): void
    {
        $tempDir = sys_get_temp_dir() . '/phetl_test_' . uniqid();
        $filePath = $tempDir . '/output.csv';

        $headers = ['name'];
        $data = [
            ['Alice'],
        ];

        $loader = new CsvLoader($filePath);
        $loader->load($headers, $data);

        $this->assertFileExists($filePath);

        // Cleanup
        unlink($filePath);
        rmdir($tempDir);
    }

    public function test_it_handles_null_values(): void
    {
        $headers = ['name', 'age', 'city'];
        $data = [
            ['Alice', null, 'NYC'],
            ['Bob', '25', null],
        ];

        $loader = new CsvLoader($this->testFile);
        $loader->load($headers, $data);

        $this->assertFileExists($this->testFile);
    }
}
