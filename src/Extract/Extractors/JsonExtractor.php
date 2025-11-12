<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;

/**
 * Extracts data from JSON files containing arrays of objects.
 *
 * Converts JSON array of objects into tabular format with headers
 * derived from object keys. Uses lazy evaluation for memory efficiency.
 */
final class JsonExtractor implements ExtractorInterface
{
    /**
     * @param string $filePath Path to the JSON file
     */
    public function __construct(
        private readonly string $filePath
    ) {
        $this->validate();
    }

    /**
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable
    {
        $content = file_get_contents($this->filePath);

        if ($content === false) {
            return;
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON in file: ' . json_last_error_msg());
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('JSON file must contain an array of objects');
        }

        if ($data === []) {
            return;
        }

        // Extract all unique field names from all objects
        $fields = $this->extractFieldNames($data);

        // Yield header row
        yield array_values($fields);

        // Yield data rows
        foreach ($data as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('JSON file must contain an array of objects');
            }

            yield $this->normalizeRow($row, $fields);
        }
    }

    /**
     * Validate file exists and is readable.
     */
    private function validate(): void
    {
        if (! file_exists($this->filePath)) {
            throw new InvalidArgumentException('JSON file does not exist: ' . $this->filePath);
        }

        if (! is_readable($this->filePath)) {
            throw new InvalidArgumentException('JSON file is not readable: ' . $this->filePath);
        }
    }

    /**
     * Extract all unique field names from the dataset.
     *
     * @param array<int, array<string, mixed>> $data
     * @return array<int, string>
     */
    private function extractFieldNames(array $data): array
    {
        $fields = [];

        foreach ($data as $row) {
            // @phpstan-ignore-next-line - Runtime validation needed for malformed JSON
            if (! is_array($row)) {
                throw new InvalidArgumentException('JSON file must contain an array of objects');
            }

            foreach (array_keys($row) as $key) {
                if (! in_array($key, $fields, true)) {
                    $fields[] = $key;
                }
            }
        }

        return $fields;
    }

    /**
     * Normalize a row to match the field structure.
     *
     * @param array<string, mixed> $row
     * @param array<int, string> $fields
     * @return array<int, mixed>
     */
    private function normalizeRow(array $row, array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $normalized[] = $row[$field] ?? null;
        }

        return $normalized;
    }
}
