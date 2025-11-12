<?php

declare(strict_types=1);

namespace Phetl\Transform\Set;

use Generator;
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
     * @param iterable<int, array<int|string, mixed>> ...$tables
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function concat(iterable ...$tables): Generator
    {
        if (count($tables) === 0) {
            return;
        }

        $headerEmitted = false;
        /** @var array<int|string, mixed>|null $expectedHeader */
        $expectedHeader = null;

        foreach ($tables as $tableIndex => $table) {
            $isFirstRow = true;

            foreach ($table as $row) {
                if ($isFirstRow) {
                    // Process header
                    if (!$headerEmitted) {
                        $expectedHeader = $row;
                        yield $row;
                        $headerEmitted = true;
                    } else {
                        // Validate header matches
                        if ($row !== $expectedHeader) {
                            throw new InvalidArgumentException(
                                "Table " . ((int)$tableIndex + 1) . " has different header structure"
                            );
                        }
                    }
                    $isFirstRow = false;
                    continue;
                }

                // Yield data row
                yield $row;
            }
        }
    }

    /**
     * Union tables (concat + remove duplicates).
     * Headers must match exactly.
     *
     * @param iterable<int, array<int|string, mixed>> ...$tables
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function union(iterable ...$tables): Generator
    {
        $seen = [];
        $header = null;

        foreach (self::concat(...$tables) as $index => $row) {
            if ($index === 0) {
                $header = $row;
                yield $row;
                continue;
            }

            // Serialize row for uniqueness check
            $key = serialize($row);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                yield $row;
            }
        }
    }

    /**
     * Merge tables with different headers (combines all columns).
     * Missing values are filled with null.
     *
     * @param iterable<int, array<int|string, mixed>> ...$tables
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function merge(iterable ...$tables): Generator
    {
        if (count($tables) === 0) {
            return;
        }

        // Collect all headers and data from all tables
        /** @var array<string, int> $allFieldIndices */
        $allFieldIndices = [];
        /** @var array<int, array{header: array<int|string, mixed>, rows: array<int, array<int|string, mixed>>}> $tableData */
        $tableData = [];

        foreach ($tables as $table) {
            $header = null;
            $rows = [];

            foreach ($table as $index => $row) {
                if ($index === 0) {
                    $header = $row;
                    // Track all field names
                    foreach ($row as $field) {
                        if (!isset($allFieldIndices[$field])) {
                            $allFieldIndices[$field] = count($allFieldIndices);
                        }
                    }
                } else {
                    $rows[] = $row;
                }
            }

            if ($header !== null) {
                $tableData[] = ['header' => $header, 'rows' => $rows];
            }
        }

        // Create merged header
        $mergedHeader = array_keys($allFieldIndices);
        yield $mergedHeader;

        // Merge rows from all tables
        foreach ($tableData as $data) {
            $header = $data['header'];
            $rows = $data['rows'];

            // Create mapping from source header to merged header
            /** @var array<int, int> $indexMapping */
            $indexMapping = [];
            foreach ($header as $sourceIndex => $fieldName) {
                $indexMapping[$sourceIndex] = $allFieldIndices[$fieldName];
            }

            // Yield rows with proper alignment
            foreach ($rows as $row) {
                $mergedRow = array_fill(0, count($mergedHeader), null);
                foreach ($row as $sourceIndex => $value) {
                    $targetIndex = $indexMapping[$sourceIndex];
                    $mergedRow[$targetIndex] = $value;
                }
                yield $mergedRow;
            }
        }
    }
}
