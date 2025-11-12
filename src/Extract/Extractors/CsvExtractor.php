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
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable
    {
        $handle = fopen($this->filePath, 'r');

        if ($handle === false) {
            return;
        }

        try {
            while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
                yield $row;
            }
        } finally {
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
