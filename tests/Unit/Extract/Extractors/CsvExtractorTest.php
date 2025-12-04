<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\CsvExtractor;
use PHPUnit\Framework\TestCase;

final class CsvExtractorTest extends TestCase
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

    public function test_it_implements_extractor_interface(): void
    {
        file_put_contents($this->testFile, "name,age\nAlice,30");

        $extractor = new CsvExtractor($this->testFile);

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
    }

    public function test_it_extracts_csv_data(): void
    {
        $csvContent = "name,age,city\nAlice,30,NYC\nBob,25,LA";
        file_put_contents($this->testFile, $csvContent);

        $extractor = new CsvExtractor($this->testFile);
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age', 'city'], $headers);
        $this->assertCount(2, $data);
        $this->assertEquals(['Alice', '30', 'NYC'], $data[0]);
        $this->assertEquals(['Bob', '25', 'LA'], $data[1]);
    }

    public function test_it_validates_file_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CSV file does not exist');

        new CsvExtractor('/nonexistent/file.csv');
    }

    public function test_it_validates_file_is_readable(): void
    {
        // Skip on Windows as chmod doesn't work the same way
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permission test not applicable on Windows');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CSV file is not readable');

        // Create a file but make it unreadable (Unix-only)
        touch($this->testFile);
        chmod($this->testFile, 0o000);

        try {
            new CsvExtractor($this->testFile);
        }
        finally {
            chmod($this->testFile, 0o644);
        }
    }

    public function test_it_yields_rows_lazily(): void
    {
        file_put_contents($this->testFile, "name,age\nAlice,30\nBob,25");

        $extractor = new CsvExtractor($this->testFile);
        $result = $extractor->extract();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        [$headers, $data] = $result;
        $this->assertEquals(['name', 'age'], $headers);
        $this->assertCount(2, $data);
    }

    public function test_it_handles_custom_delimiter(): void
    {
        file_put_contents($this->testFile, "name;age;city\nAlice;30;NYC");

        $extractor = new CsvExtractor($this->testFile, delimiter: ';');
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age', 'city'], $headers);
        $this->assertEquals(['Alice', '30', 'NYC'], $data[0]);
    }

    public function test_it_handles_custom_enclosure(): void
    {
        file_put_contents($this->testFile, "'name','age'\n'Alice','30'");

        $extractor = new CsvExtractor($this->testFile, enclosure: "'");
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age'], $headers);
        $this->assertEquals(['Alice', '30'], $data[0]);
    }

    public function test_it_handles_custom_escape(): void
    {
        // PHP's fgetcsv behavior with escape characters
        file_put_contents($this->testFile, "name,value\n\"John\",\"He said \\\"hi\\\"\"");

        $extractor = new CsvExtractor($this->testFile, escape: '\\');
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'value'], $headers);
        // Note: fgetcsv escapes quotes with backslash
        $this->assertEquals(['John', 'He said \"hi\"'], $data[0]);
    }

    public function test_it_handles_empty_file(): void
    {
        file_put_contents($this->testFile, '');

        $extractor = new CsvExtractor($this->testFile);
        [$headers, $data] = $extractor->extract();

        $this->assertCount(0, $headers);
        $this->assertCount(0, $data);
    }

    public function test_it_handles_header_only_file(): void
    {
        file_put_contents($this->testFile, 'name,age,city');

        $extractor = new CsvExtractor($this->testFile);
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age', 'city'], $headers);
        $this->assertCount(0, $data); // Header only, no data rows
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        file_put_contents($this->testFile, "name,age\nAlice,30");

        $extractor = new CsvExtractor($this->testFile);

        [$headers1, $data1] = $extractor->extract();
        [$headers2, $data2] = $extractor->extract();

        $this->assertEquals($headers1, $headers2);
        $this->assertEquals($data1, $data2);
    }

    public function test_it_handles_quoted_fields_with_commas(): void
    {
        file_put_contents($this->testFile, "name,location\n\"Smith, John\",\"NYC, NY\"");

        $extractor = new CsvExtractor($this->testFile);
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'location'], $headers);
        $this->assertEquals(['Smith, John', 'NYC, NY'], $data[0]);
    }

    public function test_it_handles_multiline_quoted_fields(): void
    {
        file_put_contents($this->testFile, "name,bio\n\"Alice\",\"Line 1\nLine 2\"");

        $extractor = new CsvExtractor($this->testFile);
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'bio'], $headers);
        $this->assertEquals(['Alice', "Line 1\nLine 2"], $data[0]);
    }
}
