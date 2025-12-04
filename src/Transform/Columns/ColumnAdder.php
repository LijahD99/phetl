<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

use Closure;
use InvalidArgumentException;

/**
 * Column addition transformations.
 */
class ColumnAdder
{
    /**
     * Add a new column with a computed value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $columnName Name of the new column
     * @param mixed|Closure $value Value or function to compute value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function add(array $headers, array $data, string $columnName, mixed $value): array
    {
        if ($columnName === '') {
            throw new InvalidArgumentException('Column name cannot be empty');
        }

        // Add new column to header
        $newHeaders = $headers;
        $newHeaders[] = $columnName;

        // Add computed value to each data row
        $newData = [];
        foreach ($data as $row) {
            // Compute value for new column
            if ($value instanceof Closure) {
                // Create associative array for easier access in closure
                $assocRow = [];
                foreach ($headers as $index => $col) {
                    $assocRow[$col] = $row[$index] ?? null;
                }
                $computedValue = $value($assocRow);
            }
            else {
                $computedValue = $value;
            }

            $newRow = $row;
            $newRow[] = $computedValue;
            $newData[] = $newRow;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Add a column with a constant value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $columnName
     * @param mixed $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function addConstant(array $headers, array $data, string $columnName, mixed $value): array
    {
        return self::add($headers, $data, $columnName, $value);
    }

    /**
     * Add a row number column (1-indexed).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $columnName
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function addRowNumbers(array $headers, array $data, string $columnName = 'row_number'): array
    {
        // Add row number column to header
        $newHeaders = $headers;
        $newHeaders[] = $columnName;

        // Add row numbers to each data row (1-indexed)
        $newData = [];
        $rowNumber = 0;
        foreach ($data as $row) {
            $rowNumber++;
            $newRow = $row;
            $newRow[] = $rowNumber;
            $newData[] = $newRow;
        }

        return [$newHeaders, $newData];
    }
}
