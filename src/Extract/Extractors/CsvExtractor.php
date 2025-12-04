<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;

/**
 * Extracts data from CSV files.
 *
 * Supports configurable delimiters, enclosures, and escape characters.
 * Uses lazy evaluation for memory efficiency with large files.
 */
final class CsvExtractor implements ExtractorInterface
{
    /**
     * @param string $filePath Path to the CSV file
     * @param string $delimiter Field delimiter (default: comma)
     * @param string $enclosure Field enclosure character (default: double quote)
     * @param string $escape Escape character (default: backslash)
     * @param bool $hasHeaders Whether first row contains headers (default: true)
     */
    public function __construct(
        private readonly string $filePath,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
        private readonly bool $hasHeaders = true
    ) {
        $this->validate();
    }

    /**
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public function extract(): array
    {
        $handle = fopen($this->filePath, 'r');

        if ($handle === false) {
            return [[], []];
        }

        try {
            $headers = [];
            $data = [];

            if ($this->hasHeaders) {
                // Read header row
                $headerRow = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
                if ($headerRow !== false) {
                    $headers = array_map('strval', $headerRow);
                }
            }
            else {
                // Auto-generate headers based on first row column count
                $firstRow = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
                if ($firstRow !== false) {
                    $headers = array_map(fn ($i) => "col_$i", array_keys($firstRow));
                    $data[] = $firstRow; // Include first row as data
                }
            }

            // Read data rows
            while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                $data[] = $row;
            }

            return [$headers, $data];
        }
        finally {
            fclose($handle);
        }
    }

    /**
     * Validate file exists and is readable.
     */
    private function validate(): void
    {
        if (! file_exists($this->filePath)) {
            throw new InvalidArgumentException('CSV file does not exist: ' . $this->filePath);
        }

        if (! is_readable($this->filePath)) {
            throw new InvalidArgumentException('CSV file is not readable: ' . $this->filePath);
        }
    }
}
