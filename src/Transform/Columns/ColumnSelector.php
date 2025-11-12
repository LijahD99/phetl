<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

use Generator;
use InvalidArgumentException;

/**
 * Column selection transformations for filtering table columns.
 */
class ColumnSelector
{
    /**
     * Select specific columns by name (cut in petl).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<int|string> $columns Column names to keep
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function select(iterable $data, array $columns): Generator
    {
        if ($columns === []) {
            throw new InvalidArgumentException('At least one column must be specified');
        }

        $headerProcessed = false;
        /** @var array<int, int|string>|null $columnIndices */
        $columnIndices = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // First row is header - build index mapping
                $columnIndices = self::buildColumnIndices($row, $columns);

                // Yield selected header columns
                $selectedHeader = [];
                foreach ($columnIndices as $index) {
                    $selectedHeader[] = $row[$index];
                }
                yield $selectedHeader;

                $headerProcessed = true;
                continue;
            }

            // Select data from specified columns
            $selectedRow = [];
            if ($columnIndices !== null) {
                foreach ($columnIndices as $index) {
                    $selectedRow[] = $row[$index] ?? null;
                }
            }
            yield $selectedRow;
        }
    }

    /**
     * Remove specific columns by name (cutout in petl).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<int|string> $columns Column names to remove
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function remove(iterable $data, array $columns): Generator
    {
        if ($columns === []) {
            // No columns to remove, yield all data
            yield from $data;
            return;
        }

        $headerProcessed = false;
        /** @var array<int, int|string>|null $keepIndices */
        $keepIndices = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // First row is header - determine which columns to keep
                $keepIndices = self::buildKeepIndices($row, $columns);

                // Yield remaining header columns
                $remainingHeader = [];
                foreach ($keepIndices as $index) {
                    $remainingHeader[] = $row[$index];
                }
                yield $remainingHeader;

                $headerProcessed = true;
                continue;
            }

            // Keep data from non-removed columns
            $remainingRow = [];
            if ($keepIndices !== null) {
                foreach ($keepIndices as $index) {
                    $remainingRow[] = $row[$index] ?? null;
                }
            }
            yield $remainingRow;
        }
    }

    /**
     * Build array of indices for selected columns.
     *
     * @param array<int|string, mixed> $header
     * @param array<int|string> $columns
     * @return array<int, int|string>
     */
    private static function buildColumnIndices(array $header, array $columns): array
    {
        $indices = [];

        foreach ($columns as $column) {
            $index = array_search($column, $header, true);

            if ($index === false) {
                throw new InvalidArgumentException("Column '$column' not found in header");
            }

            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * Build array of indices for columns to keep (excluding removed ones).
     *
     * @param array<int|string, mixed> $header
     * @param array<int|string> $columnsToRemove
     * @return array<int, int|string>
     */
    private static function buildKeepIndices(array $header, array $columnsToRemove): array
    {
        $keepIndices = [];

        foreach ($header as $index => $column) {
            if (!in_array($column, $columnsToRemove, true)) {
                $keepIndices[] = $index;
            }
        }

        return $keepIndices;
    }
}
