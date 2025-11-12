<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Generator;
use InvalidArgumentException;

/**
 * Deduplication operations for removing and detecting duplicate rows.
 */
class Deduplicator
{
    /**
     * Remove duplicate rows, keeping only distinct rows.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function distinct(iterable $data, string|array|null $fields = null): Generator
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;
        
        $tableData = self::processTable($data);
        
        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($tableData['header'], $fields);
        }

        // Yield header
        yield $tableData['header'];

        // Track seen rows
        $seen = [];

        foreach ($tableData['rows'] as $row) {
            // Create key based on specified fields or all fields
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn($i) => $row[$i] ?? null, $fieldIndices);
            } else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                yield $row;
            }
        }
    }

    /**
     * Alias for distinct - petl compatibility.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function unique(iterable $data, string|array|null $fields = null): Generator
    {
        yield from self::distinct($data, $fields);
    }

    /**
     * Return only duplicate rows (rows that appear more than once).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for duplicates (null = all fields)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function duplicates(iterable $data, string|array|null $fields = null): Generator
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;
        
        $tableData = self::processTable($data);
        
        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($tableData['header'], $fields);
        }

        // Count occurrences
        $counts = [];
        foreach ($tableData['rows'] as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn($i) => $row[$i] ?? null, $fieldIndices);
            } else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        // Yield header
        yield $tableData['header'];

        // Yield rows that appear more than once
        $seen = [];
        foreach ($tableData['rows'] as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn($i) => $row[$i] ?? null, $fieldIndices);
            } else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);
            
            if ($counts[$key] > 1 && !isset($seen[$key])) {
                $seen[$key] = true;
                yield $row;
            }
        }
    }

    /**
     * Count occurrences of each unique row.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @param string $countField Name for the count column (default: 'count')
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function countDistinct(
        iterable $data,
        string|array|null $fields = null,
        string $countField = 'count'
    ): Generator {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;
        
        $tableData = self::processTable($data);
        
        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($tableData['header'], $fields);
        }

        // Count occurrences and store first occurrence of each unique row
        $counts = [];
        $firstOccurrence = [];
        
        foreach ($tableData['rows'] as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn($i) => $row[$i] ?? null, $fieldIndices);
            } else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);
            
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
                $firstOccurrence[$key] = $row;
            }
            $counts[$key]++;
        }

        // Yield header with count field
        yield array_merge($tableData['header'], [$countField]);

        // Yield rows with counts
        foreach ($counts as $key => $count) {
            yield array_merge($firstOccurrence[$key], [$count]);
        }
    }

    /**
     * Check if all rows are unique.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return bool
     */
    public static function isUnique(iterable $data, string|array|null $fields = null): bool
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;
        
        $tableData = self::processTable($data);
        
        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($tableData['header'], $fields);
        }

        $seen = [];

        foreach ($tableData['rows'] as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn($i) => $row[$i] ?? null, $fieldIndices);
            } else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);

            if (isset($seen[$key])) {
                return false; // Found duplicate
            }
            
            $seen[$key] = true;
        }

        return true; // All unique
    }

    /**
     * Process table into header and rows.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return array{header: array<int|string, mixed>, rows: array<int, array<int|string, mixed>>}
     */
    private static function processTable(iterable $data): array
    {
        $header = null;
        $rows = [];

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            $rows[] = $row;
        }

        if ($header === null) {
            throw new InvalidArgumentException('Table must have a header row');
        }

        return [
            'header' => $header,
            'rows' => $rows,
        ];
    }

    /**
     * Get field indices from header.
     *
     * @param array<int|string, mixed> $header
     * @param array<string> $fields
     * @return array<int|string>
     */
    private static function getFieldIndices(array $header, array $fields): array
    {
        $indices = [];

        foreach ($fields as $field) {
            $index = array_search($field, $header, true);
            if ($index === false) {
                throw new InvalidArgumentException("Field '$field' not found in header");
            }
            $indices[] = $index;
        }

        return $indices;
    }
}
