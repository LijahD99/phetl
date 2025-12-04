<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

use InvalidArgumentException;

/**
 * Column selection transformations for filtering table columns.
 */
class ColumnSelector
{
    /**
     * Select specific columns by name (cut in petl).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param array<int|string> $columns Column names to keep
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function select(array $headers, array $data, array $columns): array
    {
        if ($columns === []) {
            throw new InvalidArgumentException('At least one column must be specified');
        }

        $columnIndices = self::buildColumnIndices($headers, $columns);

        // Build selected header
        $selectedHeader = [];
        foreach ($columnIndices as $index) {
            $selectedHeader[] = $headers[$index];
        }

        // Build selected data rows
        $selectedData = [];
        foreach ($data as $row) {
            $selectedRow = [];
            foreach ($columnIndices as $index) {
                $selectedRow[] = $row[$index] ?? null;
            }
            $selectedData[] = $selectedRow;
        }

        return [$selectedHeader, $selectedData];
    }

    /**
     * Remove specific columns by name (cutout in petl).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param array<int|string> $columns Column names to remove
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function remove(array $headers, array $data, array $columns): array
    {
        if ($columns === []) {
            // No columns to remove, return all data
            return [$headers, $data];
        }

        $keepIndices = self::buildKeepIndices($headers, $columns);

        // Build remaining header
        $remainingHeader = [];
        foreach ($keepIndices as $index) {
            $remainingHeader[] = $headers[$index];
        }

        // Build remaining data rows
        $remainingData = [];
        foreach ($data as $row) {
            $remainingRow = [];
            foreach ($keepIndices as $index) {
                $remainingRow[] = $row[$index] ?? null;
            }
            $remainingData[] = $remainingRow;
        }

        return [$remainingHeader, $remainingData];
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
            if (! in_array($column, $columnsToRemove, true)) {
                $keepIndices[] = $index;
            }
        }

        return $keepIndices;
    }
}
