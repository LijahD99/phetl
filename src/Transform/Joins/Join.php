<?php

declare(strict_types=1);

namespace Phetl\Transform\Joins;

use InvalidArgumentException;

/**
 * Join operations for combining tables horizontally.
 */
class Join
{
    /**
     * Inner join - returns rows that have matching values in both tables.
     *
     * @param array<string> $leftHeaders
     * @param array<int, array<int|string, mixed>> $leftData
     * @param array<string> $rightHeaders
     * @param array<int, array<int|string, mixed>> $rightData
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function inner(
        array $leftHeaders,
        array $leftData,
        array $rightHeaders,
        array $rightData,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): array {
        $rightKey ??= $leftKey;
        $leftKeys = is_array($leftKey) ? $leftKey : [$leftKey];
        $rightKeys = is_array($rightKey) ? $rightKey : [$rightKey];

        $leftKeyIndices = self::getFieldIndices($leftHeaders, $leftKeys, 'left');

        // Build lookup from right table
        $rightLookup = self::buildRightLookup($rightHeaders, $rightData, $rightKeys);

        // Merged header
        $mergedHeader = self::mergeHeaders($leftHeaders, $rightHeaders, $rightKeys);

        // Join rows
        $resultData = [];
        foreach ($leftData as $leftRow) {
            $keyValue = self::extractKeyValue($leftRow, $leftKeyIndices);
            $key = serialize($keyValue);

            if (isset($rightLookup['data'][$key])) {
                foreach ($rightLookup['data'][$key] as $rightRow) {
                    $resultData[] = self::mergeRows($leftRow, $rightRow, $rightLookup['keyIndices']);
                }
            }
        }

        return [$mergedHeader, $resultData];
    }

    /**
     * Left join - returns all rows from left table, with matched rows from right table.
     *
     * @param array<string> $leftHeaders
     * @param array<int, array<int|string, mixed>> $leftData
     * @param array<string> $rightHeaders
     * @param array<int, array<int|string, mixed>> $rightData
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function left(
        array $leftHeaders,
        array $leftData,
        array $rightHeaders,
        array $rightData,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): array {
        $rightKey ??= $leftKey;
        $leftKeys = is_array($leftKey) ? $leftKey : [$leftKey];
        $rightKeys = is_array($rightKey) ? $rightKey : [$rightKey];

        $leftKeyIndices = self::getFieldIndices($leftHeaders, $leftKeys, 'left');

        // Build lookup from right table
        $rightLookup = self::buildRightLookup($rightHeaders, $rightData, $rightKeys);

        // Merged header
        $mergedHeader = self::mergeHeaders($leftHeaders, $rightHeaders, $rightKeys);

        // Calculate null row for unmatched records
        $nullRightRow = array_fill(0, count($rightHeaders) - count($rightKeys), null);

        // Join rows
        $resultData = [];
        foreach ($leftData as $leftRow) {
            $keyValue = self::extractKeyValue($leftRow, $leftKeyIndices);
            $key = serialize($keyValue);

            if (isset($rightLookup['data'][$key])) {
                foreach ($rightLookup['data'][$key] as $rightRow) {
                    $resultData[] = self::mergeRows($leftRow, $rightRow, $rightLookup['keyIndices']);
                }
            }
            else {
                // No match - yield left row with nulls
                $resultData[] = array_merge($leftRow, $nullRightRow);
            }
        }

        return [$mergedHeader, $resultData];
    }

    /**
     * Right join - returns all rows from right table, with matched rows from left table.
     *
     * @param array<string> $leftHeaders
     * @param array<int, array<int|string, mixed>> $leftData
     * @param array<string> $rightHeaders
     * @param array<int, array<int|string, mixed>> $rightData
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function right(
        array $leftHeaders,
        array $leftData,
        array $rightHeaders,
        array $rightData,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): array {
        // Right join is left join with tables swapped
        return self::left($rightHeaders, $rightData, $leftHeaders, $leftData, $rightKey ?? $leftKey, $leftKey);
    }

    /**
     * Build lookup table from right side of join.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param array<string> $keys
     * @return array{header: array<string>, data: array<string, array<int, array<int|string, mixed>>>, keyIndices: array<int|string>}
     */
    private static function buildRightLookup(array $headers, array $data, array $keys): array
    {
        $keyIndices = self::getFieldIndices($headers, $keys, 'right');
        $lookup = [];

        foreach ($data as $row) {
            $keyValue = self::extractKeyValue($row, $keyIndices);
            $key = serialize($keyValue);

            if (! isset($lookup[$key])) {
                $lookup[$key] = [];
            }
            $lookup[$key][] = $row;
        }

        return [
            'header' => $headers,
            'data' => $lookup,
            'keyIndices' => $keyIndices,
        ];
    }

    /**
     * Get field indices from header.
     *
     * @param array<int|string, mixed> $header
     * @param array<string> $fields
     * @param string $tableSide 'left' or 'right' for error messages
     * @return array<int|string>
     */
    private static function getFieldIndices(array $header, array $fields, string $tableSide = ''): array
    {
        $indices = [];

        foreach ($fields as $field) {
            $index = array_search($field, $header, true);
            if ($index === false) {
                $message = "Field '$field' not found in " . ($tableSide ? $tableSide . ' table ' : '') . 'header';

                throw new InvalidArgumentException($message);
            }
            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * Extract key value from row.
     *
     * @param array<int|string, mixed> $row
     * @param array<int|string> $indices
     * @return array<mixed>
     */
    private static function extractKeyValue(array $row, array $indices): array
    {
        $values = [];
        foreach ($indices as $index) {
            $values[] = $row[$index] ?? null;
        }

        return $values;
    }

    /**
     * Merge headers, excluding duplicate join keys from right table.
     *
     * @param array<int|string, mixed> $leftHeader
     * @param array<int|string, mixed> $rightHeader
     * @param array<string> $rightKeys
     * @return array<int|string, mixed>
     */
    private static function mergeHeaders(array $leftHeader, array $rightHeader, array $rightKeys): array
    {
        $merged = $leftHeader;

        foreach ($rightHeader as $field) {
            if (! in_array($field, $rightKeys, true)) {
                $merged[] = $field;
            }
        }

        return $merged;
    }

    /**
     * Merge rows, excluding duplicate join keys from right row.
     *
     * @param array<int|string, mixed> $leftRow
     * @param array<int|string, mixed> $rightRow
     * @param array<int|string> $rightKeyIndices
     * @return array<int|string, mixed>
     */
    private static function mergeRows(array $leftRow, array $rightRow, array $rightKeyIndices): array
    {
        $merged = $leftRow;

        foreach ($rightRow as $index => $value) {
            if (! in_array($index, $rightKeyIndices, true)) {
                $merged[] = $value;
            }
        }

        return $merged;
    }
}
