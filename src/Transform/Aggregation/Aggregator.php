<?php

declare(strict_types=1);

namespace Phetl\Transform\Aggregation;

use InvalidArgumentException;

/**
 * Aggregation operations for grouping and summarizing data.
 */
class Aggregator
{
    /**
     * Group rows by field(s) and aggregate.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string|array<string> $groupBy Field(s) to group by
     * @param array<string, callable|string> $aggregations Map of output field => aggregation function
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function aggregate(
        array $headers,
        array $data,
        string|array $groupBy,
        array $aggregations
    ): array {
        $groupByFields = is_array($groupBy) ? $groupBy : [$groupBy];

        if (empty($aggregations)) {
            throw new InvalidArgumentException('At least one aggregation must be specified');
        }

        // Get indices for groupBy fields
        $groupByIndices = self::getFieldIndices($headers, $groupByFields);

        // Build groups
        $groups = self::buildGroups($data, $groupByIndices);

        // Build output header
        $outputHeaders = array_merge($groupByFields, array_keys($aggregations));

        // Build output data
        $outputData = [];

        // Apply aggregations to each group
        foreach ($groups as $keyValues => $rows) {
            $groupKeyValues = unserialize($keyValues);
            $result = $groupKeyValues;

            foreach ($aggregations as $field => $aggregation) {
                $result[] = self::applyAggregation($aggregation, $rows, $headers);
            }

            $outputData[] = $result;
        }

        return [$outputHeaders, $outputData];
    }

    /**
     * Count rows, optionally grouped by field(s).
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string|array<string>|null $groupBy Field(s) to group by (null for total count)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function count(
        array $headers,
        array $data,
        string|array|null $groupBy = null
    ): array {
        if ($groupBy === null) {
            // Total count
            return [['count'], [[count($data)]]];
        }

        // Grouped count
        $groupByFields = is_array($groupBy) ? $groupBy : [$groupBy];

        return self::aggregate($headers, $data, $groupByFields, ['count' => 'count']);
    }

    /**
     * Sum values of a field, optionally grouped.
     *
     * @param array<string> $headers Column headers
     * @param array<int, array<int|string, mixed>> $data Table data (without header row)
     * @param string $field Field to sum
     * @param string|array<string>|null $groupBy Field(s) to group by
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function sum(
        array $headers,
        array $data,
        string $field,
        string|array|null $groupBy = null
    ): array {
        if ($groupBy === null) {
            $fieldIndex = self::getFieldIndex($headers, $field);
            $total = 0;

            foreach ($data as $row) {
                $total += $row[$fieldIndex] ?? 0;
            }

            return [['sum'], [[$total]]];
        }

        $groupByFields = is_array($groupBy) ? $groupBy : [$groupBy];

        return self::aggregate($headers, $data, $groupByFields, [
            'sum' => function ($rows, $header) use ($field) {
                $fieldIndex = array_search($field, $header, true);
                if ($fieldIndex === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                $sum = 0;
                foreach ($rows as $row) {
                    $sum += $row[$fieldIndex] ?? 0;
                }

                return $sum;
            },
        ]);
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
     * Build groups from rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param array<int|string> $groupByIndices
     * @return array<string, array<int, array<int|string, mixed>>>
     */
    private static function buildGroups(array $rows, array $groupByIndices): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $keyValues = [];
            foreach ($groupByIndices as $index) {
                $keyValues[] = $row[$index] ?? null;
            }

            $key = serialize($keyValues);
            if (! isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $row;
        }

        return $groups;
    }

    /**
     * Apply aggregation function to a group of rows.
     *
     * @param callable|string $aggregation
     * @param array<int, array<int|string, mixed>> $rows
     * @param array<int|string, mixed> $header
     * @return mixed
     */
    private static function applyAggregation(callable|string $aggregation, array $rows, array $header): mixed
    {
        if (is_string($aggregation)) {
            return match ($aggregation) {
                'count' => count($rows),
                'sum' => self::sumAllNumericFields($rows),
                'avg', 'average', 'mean' => self::avgAllNumericFields($rows),
                'min' => self::minAllFields($rows),
                'max' => self::maxAllFields($rows),
                'first' => $rows[0] ?? null,
                'last' => $rows[array_key_last($rows)] ?? null,
                default => throw new InvalidArgumentException("Unknown aggregation function: $aggregation"),
            };
        }

        // Custom callable - pass rows and header
        return $aggregation($rows, $header);
    }

    /**
     * Sum all numeric fields in rows (for 'sum' string aggregation).
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @return float|int
     */
    private static function sumAllNumericFields(array $rows): float|int
    {
        if (empty($rows)) {
            return 0;
        }

        $sums = [];
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                if (is_numeric($value)) {
                    $sums[$index] = ($sums[$index] ?? 0) + $value;
                }
            }
        }

        return ! empty($sums) ? array_sum($sums) : 0;
    }

    /**
     * Average all numeric fields in rows (for 'avg' string aggregation).
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @return float|int|null
     */
    private static function avgAllNumericFields(array $rows): float|int|null
    {
        if (empty($rows)) {
            return null;
        }

        $sum = self::sumAllNumericFields($rows);
        $count = count($rows);

        return $count > 0 ? $sum / $count : null;
    }

    /**
     * Get minimum value across all fields.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @return mixed
     */
    private static function minAllFields(array $rows): mixed
    {
        if (empty($rows)) {
            return null;
        }

        $values = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    $values[] = $value;
                }
            }
        }

        return ! empty($values) ? min($values) : null;
    }

    /**
     * Get maximum value across all fields.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @return mixed
     */
    private static function maxAllFields(array $rows): mixed
    {
        if (empty($rows)) {
            return null;
        }

        $values = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    $values[] = $value;
                }
            }
        }

        return ! empty($values) ? max($values) : null;
    }

    /**
     * Sum a specific field across rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param string $field
     * @return float|int
     */
    private static function sumField(array $rows, string $field): float|int
    {
        $sum = 0;
        foreach ($rows as $row) {
            $index = array_search($field, array_keys($row), true);
            if ($index !== false && is_numeric($row[$index])) {
                $sum += $row[$index];
            }
        }

        return $sum;
    }
}
