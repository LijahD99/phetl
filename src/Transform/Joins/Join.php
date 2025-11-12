<?php

declare(strict_types=1);

namespace Phetl\Transform\Joins;

use Generator;
use InvalidArgumentException;

/**
 * Join operations for combining tables horizontally.
 */
class Join
{
    /**
     * Inner join - returns rows that have matching values in both tables.
     *
     * @param iterable<int, array<int|string, mixed>> $left Left table
     * @param iterable<int, array<int|string, mixed>> $right Right table
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function inner(
        iterable $left,
        iterable $right,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): Generator {
        $rightKey = $rightKey ?? $leftKey;
        $leftKeys = is_array($leftKey) ? $leftKey : [$leftKey];
        $rightKeys = is_array($rightKey) ? $rightKey : [$rightKey];

        // Process left table
        $leftData = self::processTable($left);
        $leftKeyIndices = self::getFieldIndices($leftData['header'], $leftKeys, 'left');

        // Build lookup from right table
        $rightLookup = self::buildRightLookup($right, $rightKeys);

        // Yield merged header
        $mergedHeader = self::mergeHeaders($leftData['header'], $rightLookup['header'], $rightKeys);
        yield $mergedHeader;

        // Join rows
        foreach ($leftData['rows'] as $leftRow) {
            $keyValue = self::extractKeyValue($leftRow, $leftKeyIndices);
            $key = serialize($keyValue);

            if (isset($rightLookup['data'][$key])) {
                foreach ($rightLookup['data'][$key] as $rightRow) {
                    yield self::mergeRows($leftRow, $rightRow, $rightLookup['keyIndices']);
                }
            }
        }
    }

    /**
     * Left join - returns all rows from left table, with matched rows from right table.
     *
     * @param iterable<int, array<int|string, mixed>> $left Left table
     * @param iterable<int, array<int|string, mixed>> $right Right table
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function left(
        iterable $left,
        iterable $right,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): Generator {
        $rightKey = $rightKey ?? $leftKey;
        $leftKeys = is_array($leftKey) ? $leftKey : [$leftKey];
        $rightKeys = is_array($rightKey) ? $rightKey : [$rightKey];

        // Process left table
        $leftData = self::processTable($left);
        $leftKeyIndices = self::getFieldIndices($leftData['header'], $leftKeys, 'left');

        // Build lookup from right table
        $rightLookup = self::buildRightLookup($right, $rightKeys);

        // Yield merged header
        $mergedHeader = self::mergeHeaders($leftData['header'], $rightLookup['header'], $rightKeys);
        yield $mergedHeader;

        // Calculate null row for unmatched records
        $nullRightRow = array_fill(0, count($rightLookup['header']) - count($rightKeys), null);

        // Join rows
        foreach ($leftData['rows'] as $leftRow) {
            $keyValue = self::extractKeyValue($leftRow, $leftKeyIndices);
            $key = serialize($keyValue);

            if (isset($rightLookup['data'][$key])) {
                foreach ($rightLookup['data'][$key] as $rightRow) {
                    yield self::mergeRows($leftRow, $rightRow, $rightLookup['keyIndices']);
                }
            } else {
                // No match - yield left row with nulls
                yield array_merge($leftRow, $nullRightRow);
            }
        }
    }

    /**
     * Right join - returns all rows from right table, with matched rows from left table.
     *
     * @param iterable<int, array<int|string, mixed>> $left Left table
     * @param iterable<int, array<int|string, mixed>> $right Right table
     * @param string|array<string> $leftKey Left join key(s)
     * @param string|array<string>|null $rightKey Right join key(s), defaults to leftKey
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function right(
        iterable $left,
        iterable $right,
        string|array $leftKey,
        string|array|null $rightKey = null
    ): Generator {
        // Right join is left join with tables swapped
        return self::left($right, $left, $rightKey ?? $leftKey, $leftKey);
    }

    /**
     * Build lookup table from right side of join.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<string> $keys
     * @return array{header: array<int|string, mixed>, data: array<string, array<int, array<int|string, mixed>>>, keyIndices: array<int|string>}
     */
    private static function buildRightLookup(iterable $data, array $keys): array
    {
        $tableData = self::processTable($data);
        $keyIndices = self::getFieldIndices($tableData['header'], $keys, 'right');
        $lookup = [];

        foreach ($tableData['rows'] as $row) {
            $keyValue = self::extractKeyValue($row, $keyIndices);
            $key = serialize($keyValue);

            if (!isset($lookup[$key])) {
                $lookup[$key] = [];
            }
            $lookup[$key][] = $row;
        }

        return [
            'header' => $tableData['header'],
            'data' => $lookup,
            'keyIndices' => $keyIndices,
        ];
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
            if (!in_array($field, $rightKeys, true)) {
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
            if (!in_array($index, $rightKeyIndices, true)) {
                $merged[] = $value;
            }
        }

        return $merged;
    }
}
