<?php

declare(strict_types=1);

namespace Phetl\Transform\Set;

use InvalidArgumentException;

/**
 * Set operations for combining tables.
 */
class SetOperation
{
    /**
     * Concatenate tables vertically (append rows).
     * Headers must match exactly.
     *
     * @param array{0: array<string>, 1: array<int, array<int|string, mixed>>} ...$tables Tables as [headers, data] tuples
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function concat(array ...$tables): array
    {
        if (count($tables) === 0) {
            return [[], []];
        }

        $expectedHeaders = null;
        $allData = [];

        foreach ($tables as $tableIndex => $table) {
            [$headers, $data] = $table;

            if ($expectedHeaders === null) {
                $expectedHeaders = $headers;
            }
            else {
                // Validate headers match
                if ($headers !== $expectedHeaders) {
                    throw new InvalidArgumentException(
                        "Table " . ((int) $tableIndex + 1) . " has different header structure"
                    );
                }
            }

            // Append data rows
            foreach ($data as $row) {
                $allData[] = $row;
            }
        }

        return [$expectedHeaders ?? [], $allData];
    }

    /**
     * Union tables (concat + remove duplicates).
     * Headers must match exactly.
     *
     * @param array{0: array<string>, 1: array<int, array<int|string, mixed>>} ...$tables Tables as [headers, data] tuples
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function union(array ...$tables): array
    {
        // First concatenate all tables
        [$headers, $data] = self::concat(...$tables);

        // Remove duplicates
        $seen = [];
        $uniqueData = [];

        foreach ($data as $row) {
            // Serialize row for uniqueness check
            $key = serialize($row);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueData[] = $row;
            }
        }

        return [$headers, $uniqueData];
    }

    /**
     * Merge tables with different headers (combines all columns).
     * Missing values are filled with null.
     *
     * @param array{0: array<string>, 1: array<int, array<int|string, mixed>>} ...$tables Tables as [headers, data] tuples
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function merge(array ...$tables): array
    {
        if (count($tables) === 0) {
            return [[], []];
        }

        // Collect all field names across all tables
        /** @var array<string, int> $allFieldIndices */
        $allFieldIndices = [];
        /** @var array<int, array{header: array<string>, rows: array<int, array<int|string, mixed>>}> $tableData */
        $tableData = [];

        foreach ($tables as $table) {
            [$headers, $data] = $table;

            // Track all field names
            foreach ($headers as $field) {
                if (! isset($allFieldIndices[$field])) {
                    $allFieldIndices[$field] = count($allFieldIndices);
                }
            }

            $tableData[] = ['header' => $headers, 'rows' => $data];
        }

        // Create merged header
        $mergedHeaders = array_keys($allFieldIndices);

        // Merge rows from all tables
        $allData = [];
        foreach ($tableData as $tblData) {
            $headers = $tblData['header'];
            $rows = $tblData['rows'];

            // Create mapping from source header to merged header
            /** @var array<int, int> $indexMapping */
            $indexMapping = [];
            foreach ($headers as $sourceIndex => $fieldName) {
                $indexMapping[$sourceIndex] = $allFieldIndices[$fieldName];
            }

            // Add rows with proper alignment
            foreach ($rows as $row) {
                $mergedRow = array_fill(0, count($mergedHeaders), null);
                foreach ($row as $sourceIndex => $value) {
                    $targetIndex = $indexMapping[$sourceIndex];
                    $mergedRow[$targetIndex] = $value;
                }
                $allData[] = $mergedRow;
            }
        }

        return [$mergedHeaders, $allData];
    }
}
