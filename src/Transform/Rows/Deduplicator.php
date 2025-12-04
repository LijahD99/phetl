<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use InvalidArgumentException;

/**
 * Deduplication operations for removing and detecting duplicate rows.
 */
class Deduplicator
{
    /**
     * Remove duplicate rows, keeping only distinct rows.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function distinct(array $headers, array $data, string|array|null $fields = null): array
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;

        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($headers, $fields);
        }

        // Track seen rows
        $seen = [];
        $result = [];

        foreach ($data as $row) {
            // Create key based on specified fields or all fields
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn ($i) => $row[$i] ?? null, $fieldIndices);
            }
            else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $row;
            }
        }

        return [$headers, $result];
    }

    /**
     * Alias for distinct - petl compatibility.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function unique(array $headers, array $data, string|array|null $fields = null): array
    {
        return self::distinct($headers, $data, $fields);
    }

    /**
     * Return only duplicate rows (rows that appear more than once).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for duplicates (null = all fields)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function duplicates(array $headers, array $data, string|array|null $fields = null): array
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;

        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($headers, $fields);
        }

        // Count occurrences
        $counts = [];
        foreach ($data as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn ($i) => $row[$i] ?? null, $fieldIndices);
            }
            else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        // Collect rows that appear more than once
        $result = [];
        $seen = [];
        foreach ($data as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn ($i) => $row[$i] ?? null, $fieldIndices);
            }
            else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);

            if ($counts[$key] > 1 && ! isset($seen[$key])) {
                $seen[$key] = true;
                $result[] = $row;
            }
        }

        return [$headers, $result];
    }

    /**
     * Count occurrences of each unique row.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @param string $countField Name for the count column (default: 'count')
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function countDistinct(
        array $headers,
        array $data,
        string|array|null $fields = null,
        string $countField = 'count'
    ): array {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;

        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($headers, $fields);
        }

        // Count occurrences and store first occurrence of each unique row
        $counts = [];
        $firstOccurrence = [];

        foreach ($data as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn ($i) => $row[$i] ?? null, $fieldIndices);
            }
            else {
                $keyValues = $row;
            }

            $key = serialize($keyValues);

            if (! isset($counts[$key])) {
                $counts[$key] = 0;
                $firstOccurrence[$key] = $row;
            }
            $counts[$key]++;
        }

        // Build result with count field
        $newHeaders = array_merge($headers, [$countField]);
        $result = [];

        foreach ($counts as $key => $count) {
            $result[] = array_merge($firstOccurrence[$key], [$count]);
        }

        return [$newHeaders, $result];
    }

    /**
     * Check if all rows are unique.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return bool
     */
    public static function isUnique(array $headers, array $data, string|array|null $fields = null): bool
    {
        $fields = $fields !== null ? (is_array($fields) ? $fields : [$fields]) : null;

        // Get field indices if fields specified
        $fieldIndices = null;
        if ($fields !== null) {
            $fieldIndices = self::getFieldIndices($headers, $fields);
        }

        $seen = [];

        foreach ($data as $row) {
            if ($fieldIndices !== null) {
                $keyValues = array_map(fn ($i) => $row[$i] ?? null, $fieldIndices);
            }
            else {
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
