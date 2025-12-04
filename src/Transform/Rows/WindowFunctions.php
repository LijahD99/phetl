<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use InvalidArgumentException;

/**
 * Window functions for analytical operations over row windows.
 *
 * Window functions operate on a set of rows related to the current row,
 * similar to aggregate functions but without grouping rows into a single output row.
 */
final class WindowFunctions
{
    /**
     * Access value from previous row (look back).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field Field to get previous value from
     * @param string $targetField Field to store the lagged value
     * @param int $offset Number of rows to look back (default: 1)
     * @param mixed $default Default value when no previous row exists
     * @param string|null $partitionBy Field to partition by (resets lag within partitions)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function lag(
        array $headers,
        array $data,
        string $field,
        string $targetField,
        int $offset = 1,
        mixed $default = null,
        ?string $partitionBy = null
    ): array {
        $newHeaders = $headers;
        $fieldIndex = self::getFieldIndex($headers, $field);
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);
        $partitionIndex = $partitionBy ? self::getFieldIndex($headers, $partitionBy) : null;

        $rows = $data;

        // Group by partition if needed, preserving original order
        if ($partitionIndex !== null) {
            $partitions = [];
            $rowIndex = 0;
            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                if (! isset($partitions[$partition])) {
                    $partitions[$partition] = [];
                }
                $partitions[$partition][] = ['row' => $row, 'index' => $rowIndex++];
            }

            // Calculate lag values for each partition
            $results = [];
            foreach ($partitions as $partitionRows) {
                $buffer = [];
                foreach ($partitionRows as $item) {
                    $row = $item['row'];
                    // Extend row if target field is new
                    if ($targetIndex >= count($row)) {
                        $row[] = null;
                    }
                    // Get lagged value
                    $row[$targetIndex] = count($buffer) >= $offset ? $buffer[count($buffer) - $offset] : $default;

                    // Add current value to buffer
                    $buffer[] = $row[$fieldIndex] ?? null;

                    // Store with original index
                    $results[$item['index']] = $row;
                }
            }

            // Return rows in original order
            ksort($results);

            return [$newHeaders, array_values($results)];
        }
        else {
            // No partitioning
            $buffer = [];
            $newData = [];
            foreach ($rows as $row) {
                // Extend row if target field is new
                if ($targetIndex >= count($row)) {
                    $row[] = null;
                }
                // Get lagged value
                $row[$targetIndex] = count($buffer) >= $offset ? $buffer[count($buffer) - $offset] : $default;

                // Add current value to buffer
                $buffer[] = $row[$fieldIndex] ?? null;

                $newData[] = $row;
            }

            return [$newHeaders, $newData];
        }
    }

    /**
     * Access value from next row (look ahead).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field Field to get next value from
     * @param string $targetField Field to store the lead value
     * @param int $offset Number of rows to look ahead (default: 1)
     * @param mixed $default Default value when no next row exists
     * @param string|null $partitionBy Field to partition by (resets lead within partitions)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function lead(
        array $headers,
        array $data,
        string $field,
        string $targetField,
        int $offset = 1,
        mixed $default = null,
        ?string $partitionBy = null
    ): array {
        $newHeaders = $headers;
        $fieldIndex = self::getFieldIndex($headers, $field);
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);
        $partitionIndex = $partitionBy ? self::getFieldIndex($headers, $partitionBy) : null;

        $rows = $data;

        // Group rows by partition if partitioning, preserving original order
        if ($partitionIndex !== null) {
            $partitions = [];
            $rowIndex = 0;
            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = ['row' => $row, 'index' => $rowIndex++];
            }

            // Calculate lead values for each partition
            $results = [];
            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyLead(
                    array_column($partitionRows, 'row'),
                    $fieldIndex,
                    $targetIndex,
                    $offset,
                    $default
                );

                foreach ($processedRows as $i => $row) {
                    $results[$partitionRows[$i]['index']] = $row;
                }
            }

            // Return rows in original order
            ksort($results);

            return [$newHeaders, array_values($results)];
        }
        else {
            // Process all rows
            $processedRows = self::applyLead($rows, $fieldIndex, $targetIndex, $offset, $default);

            return [$newHeaders, $processedRows];
        }
    }

    /**
     * Apply lead logic to a set of rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param int|string $fieldIndex
     * @param int|string $targetIndex
     * @param int $offset
     * @param mixed $default
     * @return array<int, array<int|string, mixed>>
     */
    private static function applyLead(
        array $rows,
        int|string $fieldIndex,
        int|string $targetIndex,
        int $offset,
        mixed $default
    ): array {
        $count = count($rows);
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            // Extend row if target field is new
            if ($targetIndex >= count($rows[$i])) {
                $rows[$i][] = null;
            }

            $leadIndex = $i + $offset;
            $rows[$i][$targetIndex] = $leadIndex < $count
                ? ($rows[$leadIndex][$fieldIndex] ?? null)
                : $default;

            $result[] = $rows[$i];
        }

        return $result;
    }

    /**
     * Assign sequential row numbers within optional partitions.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $targetField Field to store row numbers
     * @param string|null $partitionBy Field to partition by (resets numbering)
     * @param string|null $orderBy Field to order by before numbering
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function rowNumber(
        array $headers,
        array $data,
        string $targetField,
        ?string $partitionBy = null,
        ?string $orderBy = null
    ): array {
        $newHeaders = $headers;
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);

        $rows = $data;

        // Apply ordering if specified
        if ($orderBy !== null) {
            $orderIndex = self::getFieldIndex($headers, $orderBy);
            usort($rows, fn ($a, $b) => ($a[$orderIndex] ?? null) <=> ($b[$orderIndex] ?? null));
        }

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($headers, $partitionBy);
            $partitions = [];
            $rowIndex = 0;

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = ['row' => $row, 'index' => $rowIndex++];
            }

            // Calculate row numbers for each partition
            $results = [];
            foreach ($partitions as $partitionRows) {
                $rowNum = 1;
                foreach ($partitionRows as $item) {
                    $row = $item['row'];
                    // Extend row if target field is new
                    if ($targetIndex >= count($row)) {
                        $row[] = null;
                    }
                    $row[$targetIndex] = $rowNum++;
                    $results[$item['index']] = $row;
                }
            }

            // Return rows in original order
            ksort($results);

            return [$newHeaders, array_values($results)];
        }
        else {
            $rowNum = 1;
            $newData = [];
            foreach ($rows as $row) {
                // Extend row if target field is new
                if ($targetIndex >= count($row)) {
                    $row[] = null;
                }
                $row[$targetIndex] = $rowNum++;
                $newData[] = $row;
            }

            return [$newHeaders, $newData];
        }
    }

    /**
     * Assign rank with gaps for ties (1, 1, 3, 4, 4, 6).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order (highest value = rank 1)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function rank(
        array $headers,
        array $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): array {
        $newHeaders = $headers;
        $orderIndex = self::getFieldIndex($headers, $orderBy);
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);

        $rows = $data;

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($headers, $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            $newData = [];
            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    $newData[] = $row;
                }
            }

            return [$newHeaders, $newData];
        }
        else {
            $processedRows = self::applyRank($rows, $orderIndex, $targetIndex, $descending);

            return [$newHeaders, $processedRows];
        }
    }

    /**
     * Apply ranking logic to a set of rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param int|string $orderIndex
     * @param int|string $targetIndex
     * @param bool $descending
     * @return array<int, array<int|string, mixed>>
     */
    private static function applyRank(
        array $rows,
        int|string $orderIndex,
        int|string $targetIndex,
        bool $descending
    ): array {
        // Sort rows
        usort($rows, function ($a, $b) use ($orderIndex, $descending) {
            $result = ($a[$orderIndex] ?? null) <=> ($b[$orderIndex] ?? null);

            return $descending ? -$result : $result;
        });

        $rank = 1;
        $prevValue = null;
        $count = 0;
        $result = [];

        foreach ($rows as $row) {
            $count++;
            $currentValue = $row[$orderIndex] ?? null;

            if ($prevValue !== null && $currentValue !== $prevValue) {
                $rank = $count;
            }

            // Extend row if target field is new
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            $row[$targetIndex] = $rank;
            $prevValue = $currentValue;

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Assign rank without gaps for ties (1, 1, 2, 3, 3, 4).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order (highest value = rank 1)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function denseRank(
        array $headers,
        array $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): array {
        $newHeaders = $headers;
        $orderIndex = self::getFieldIndex($headers, $orderBy);
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);

        $rows = $data;

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($headers, $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            $newData = [];
            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyDenseRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    $newData[] = $row;
                }
            }

            return [$newHeaders, $newData];
        }
        else {
            $processedRows = self::applyDenseRank($rows, $orderIndex, $targetIndex, $descending);

            return [$newHeaders, $processedRows];
        }
    }

    /**
     * Apply dense ranking logic to a set of rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param int|string $orderIndex
     * @param int|string $targetIndex
     * @param bool $descending
     * @return array<int, array<int|string, mixed>>
     */
    private static function applyDenseRank(
        array $rows,
        int|string $orderIndex,
        int|string $targetIndex,
        bool $descending
    ): array {
        // Sort rows
        usort($rows, function ($a, $b) use ($orderIndex, $descending) {
            $result = ($a[$orderIndex] ?? null) <=> ($b[$orderIndex] ?? null);

            return $descending ? -$result : $result;
        });

        $rank = 1;
        $prevValue = null;
        $result = [];

        foreach ($rows as $row) {
            $currentValue = $row[$orderIndex] ?? null;

            if ($prevValue !== null && $currentValue !== $prevValue) {
                $rank++;
            }

            // Extend row if target field is new
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            $row[$targetIndex] = $rank;
            $prevValue = $currentValue;

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Calculate percentage rank (0.0 to 1.0).
     * Formula: (rank - 1) / (total rows - 1)
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store percentage ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function percentRank(
        array $headers,
        array $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): array {
        $newHeaders = $headers;
        $orderIndex = self::getFieldIndex($headers, $orderBy);
        $targetIndex = self::ensureTargetField($newHeaders, $targetField);

        $rows = $data;

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($headers, $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            $newData = [];
            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyPercentRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    $newData[] = $row;
                }
            }

            return [$newHeaders, $newData];
        }
        else {
            $processedRows = self::applyPercentRank($rows, $orderIndex, $targetIndex, $descending);

            return [$newHeaders, $processedRows];
        }
    }

    /**
     * Apply percent ranking logic to a set of rows.
     *
     * @param array<int, array<int|string, mixed>> $rows
     * @param int|string $orderIndex
     * @param int|string $targetIndex
     * @param bool $descending
     * @return array<int, array<int|string, mixed>>
     */
    private static function applyPercentRank(
        array $rows,
        int|string $orderIndex,
        int|string $targetIndex,
        bool $descending
    ): array {
        $count = count($rows);

        // Handle edge case of single row
        if ($count === 1) {
            if ($targetIndex >= count($rows[0])) {
                $rows[0][] = 0.0;
            }
            else {
                $rows[0][$targetIndex] = 0.0;
            }

            return [$rows[0]];
        }

        // Sort rows
        usort($rows, function ($a, $b) use ($orderIndex, $descending) {
            $result = ($a[$orderIndex] ?? null) <=> ($b[$orderIndex] ?? null);

            return $descending ? -$result : $result;
        });

        $rank = 1;
        $prevValue = null;
        $rowNum = 0;
        $result = [];

        foreach ($rows as $row) {
            $rowNum++;
            $currentValue = $row[$orderIndex] ?? null;

            if ($prevValue !== null && $currentValue !== $prevValue) {
                $rank = $rowNum;
            }

            // Extend row if target field is new
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            // Calculate percent rank: (rank - 1) / (total - 1)
            $row[$targetIndex] = (float) (($rank - 1) / ($count - 1));
            $prevValue = $currentValue;

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get field index from header.
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
     * Ensure target field exists in header, add if needed.
     *
     * @param array<int|string, mixed> $header
     * @param string $field
     * @return int|string
     */
    private static function ensureTargetField(array &$header, string $field): int|string
    {
        $index = array_search($field, $header, true);

        if ($index === false) {
            $header[] = $field;

            return count($header) - 1;
        }

        return $index;
    }
}
