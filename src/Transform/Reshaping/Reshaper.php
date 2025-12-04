<?php

declare(strict_types=1);

namespace Phetl\Transform\Reshaping;

use InvalidArgumentException;

/**
 * Reshaping operations for transforming table structure.
 */
class Reshaper
{
    /**
     * Unpivot (melt) table from wide to long format.
     * Converts columns into rows.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to unpivot (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function unpivot(
        array $headers,
        array $data,
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): array {
        $idFields = is_array($idFields) ? $idFields : [$idFields];

        $idIndices = self::getFieldIndices($headers, $idFields);

        // Determine value fields
        if ($valueFields === null) {
            // All fields except id fields
            $valueIndices = [];
            foreach ($headers as $index => $field) {
                if (! in_array($index, $idIndices, true)) {
                    $valueIndices[$index] = $field;
                }
            }
        }
        else {
            $valueFields = is_array($valueFields) ? $valueFields : [$valueFields];
            $valueIndices = [];
            foreach ($valueFields as $field) {
                $index = array_search($field, $headers, true);
                if ($index === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                $valueIndices[$index] = $field;
            }
        }

        // Build new header
        $outputHeaders = array_merge($idFields, [$variableName, $valueName]);

        // Unpivot rows
        $outputData = [];
        foreach ($data as $row) {
            // Extract id values
            $idValues = [];
            foreach ($idIndices as $idIndex) {
                $idValues[] = $row[$idIndex] ?? null;
            }

            // Create one row per value field
            foreach ($valueIndices as $valueIndex => $fieldName) {
                $outputData[] = array_merge($idValues, [$fieldName, $row[$valueIndex] ?? null]);
            }
        }

        return [$outputHeaders, $outputData];
    }

    /**
     * Alias for unpivot - petl compatibility.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to melt (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function melt(
        array $headers,
        array $data,
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): array {
        return self::unpivot($headers, $data, $idFields, $valueFields, $variableName, $valueName);
    }

    /**
     * Pivot table from long to wide format.
     * Converts rows into columns.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string|array<string> $indexFields Field(s) to use as row identifiers
     * @param string $columnField Field to pivot into columns
     * @param string $valueField Field to use for values
     * @param callable|string|null $aggregation Aggregation function for duplicate combinations (default: first value)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function pivot(
        array $headers,
        array $data,
        string|array $indexFields,
        string $columnField,
        string $valueField,
        callable|string|null $aggregation = null
    ): array {
        $indexFields = is_array($indexFields) ? $indexFields : [$indexFields];

        $indexIndices = self::getFieldIndices($headers, $indexFields);
        $columnIndex = self::getFieldIndex($headers, $columnField);
        $valueIndex = self::getFieldIndex($headers, $valueField);

        // Collect unique column values and build pivot structure
        $columnValues = [];
        $pivotData = [];

        foreach ($data as $row) {
            // Extract index key
            $indexKey = serialize(array_map(fn ($i) => $row[$i] ?? null, $indexIndices));

            // Extract column and value
            $colValue = $row[$columnIndex] ?? null;
            $value = $row[$valueIndex] ?? null;

            // Track unique column values
            if (! in_array($colValue, $columnValues, true)) {
                $columnValues[] = $colValue;
            }

            // Store data
            if (! isset($pivotData[$indexKey])) {
                $pivotData[$indexKey] = [
                    'index_values' => array_map(fn ($i) => $row[$i] ?? null, $indexIndices),
                    'columns' => [],
                ];
            }

            // Handle aggregation if key already exists
            if (isset($pivotData[$indexKey]['columns'][$colValue])) {
                if ($aggregation !== null) {
                    $existing = $pivotData[$indexKey]['columns'][$colValue];
                    $pivotData[$indexKey]['columns'][$colValue] = self::applyAggregation(
                        $aggregation,
                        [$existing, $value]
                    );
                }
                // Otherwise keep first value (default behavior)
            }
            else {
                $pivotData[$indexKey]['columns'][$colValue] = $value;
            }
        }

        // Sort column values for consistent output
        sort($columnValues);

        // Build output header
        $outputHeaders = array_merge($indexFields, $columnValues);

        // Build pivoted rows
        $outputData = [];
        foreach ($pivotData as $rowData) {
            $row = $rowData['index_values'];

            // Add values for each column (null if missing)
            foreach ($columnValues as $colValue) {
                $row[] = $rowData['columns'][$colValue] ?? null;
            }

            $outputData[] = $row;
        }

        return [$outputHeaders, $outputData];
    }

    /**
     * Transpose table - swap rows and columns.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function transpose(array $headers, array $data): array
    {
        // Combine headers and data for transposition
        $allRows = array_merge([$headers], $data);

        if (empty($allRows)) {
            return [[], []];
        }

        // Determine max columns
        $maxCols = max(array_map('count', $allRows));

        // Transpose all rows
        $transposed = [];
        for ($col = 0; $col < $maxCols; $col++) {
            $newRow = [];
            foreach ($allRows as $row) {
                $newRow[] = $row[$col] ?? null;
            }
            $transposed[] = $newRow;
        }

        // First transposed row becomes headers
        $outputHeaders = array_shift($transposed);

        return [$outputHeaders, $transposed];
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

    /**
     * Get single field index from header.
     *
     * @param array<int|string, mixed> $header
     * @param string $field
     * @return int|string
     */
    private static function getFieldIndex(array $header, string $field): int|string
    {
        $index = array_search($field, $header, true);
        if ($index === false) {
            throw new InvalidArgumentException("Field '$field' not found in header");
        }

        return $index;
    }

    /**
     * Apply aggregation to values.
     *
     * @param callable|string $aggregation
     * @param array<mixed> $values
     * @return mixed
     */
    private static function applyAggregation(callable|string $aggregation, array $values): mixed
    {
        if (is_string($aggregation)) {
            return match ($aggregation) {
                'sum' => array_sum($values),
                'avg', 'average', 'mean' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values),
                'count' => count($values),
                'first' => $values[0] ?? null,
                'last' => $values[array_key_last($values)] ?? null,
                default => throw new InvalidArgumentException("Unknown aggregation: $aggregation"),
            };
        }

        return $aggregation($values);
    }
}
