<?php

declare(strict_types=1);

namespace Phetl\Load\Loaders;

use InvalidArgumentException;
use Phetl\Contracts\LoaderInterface;
use Phetl\Support\LoadResult;

/**
 * Loads data to CSV files.
 *
 * Supports configurable delimiters, enclosures, and escape characters.
 * Handles complex data including multiline fields and special characters.
 */
final class CsvLoader implements LoaderInterface
{
    /**
     * @param string $filePath Path to the CSV file
     * @param string $delimiter Field delimiter (default: comma)
     * @param string $enclosure Field enclosure character (default: double quote)
     * @param string $escape Escape character (default: backslash)
     */
    public function __construct(
        private readonly string $filePath,
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\'
    ) {
        $this->validate();
    }

    /**
     * @param array<string> $headers Column names
     * @param iterable<int, array<int|string, mixed>> $data Data rows (without header)
     * @return LoadResult Result containing row count and operation details
     */
    public function load(array $headers, iterable $data): LoadResult
    {
        $handle = fopen($this->filePath, 'w');

        if ($handle === false) {
            throw new InvalidArgumentException('Cannot open file for writing: ' . $this->filePath);
        }

        $rowCount = 0;

        try {
            // Write header row
            /** @var array<int|string, bool|float|int|string|null> $headers */
            fputcsv($handle, $headers, $this->delimiter, $this->enclosure, $this->escape);

            // Write data rows
            foreach ($data as $row) {
                /** @var array<int|string, bool|float|int|string|null> $row */
                fputcsv($handle, $row, $this->delimiter, $this->enclosure, $this->escape);
                $rowCount++;
            }
        }
        finally {
            fclose($handle);
        }

        return new LoadResult($rowCount);
    }

    /**
     * Validate file path is writable.
     */
    private function validate(): void
    {
        $directory = dirname($this->filePath);

        // Create directory if it doesn't exist
        if (! is_dir($directory)) {
            if (! @mkdir($directory, 0o755, true)) {
                throw new InvalidArgumentException('Cannot create directory: ' . $directory);
            }
        }

        // Check if directory is writable
        if (! is_writable($directory)) {
            throw new InvalidArgumentException('Directory is not writable: ' . $directory);
        }
    }
}
