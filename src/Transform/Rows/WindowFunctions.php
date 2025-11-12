<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Generator;
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
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field Field to get previous value from
     * @param string $targetField Field to store the lagged value
     * @param int $offset Number of rows to look back (default: 1)
     * @param mixed $default Default value when no previous row exists
     * @param string|null $partitionBy Field to partition by (resets lag within partitions)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function lag(
        iterable $data,
        string $field,
        string $targetField,
        int $offset = 1,
        mixed $default = null,
        ?string $partitionBy = null
    ): Generator {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);
        $partitionIndex = $partitionBy ? self::getFieldIndex($tableData['header'], $partitionBy) : null;

        // Yield updated header
        yield $tableData['header'];

        // Group by partition if needed
        if ($partitionIndex !== null) {
            $partitions = [];
            foreach ($tableData['rows'] as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                if (!isset($partitions[$partition])) {
                    $partitions[$partition] = [];
                }
                $partitions[$partition][] = $row;
            }

            // Process each partition
            foreach ($partitions as $partitionRows) {
                $buffer = [];
                foreach ($partitionRows as $row) {
                    // Get lagged value
                    $row[$targetIndex] = count($buffer) >= $offset ? $buffer[count($buffer) - $offset] : $default;

                    // Add current value to buffer
                    $buffer[] = $row[$fieldIndex] ?? null;

                    yield $row;
                }
            }
        } else {
            // No partitioning
            $buffer = [];
            foreach ($tableData['rows'] as $row) {
                // Get lagged value
                $row[$targetIndex] = count($buffer) >= $offset ? $buffer[count($buffer) - $offset] : $default;

                // Add current value to buffer
                $buffer[] = $row[$fieldIndex] ?? null;

                yield $row;
            }
        }
    }

    /**
     * Access value from next row (look ahead).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field Field to get next value from
     * @param string $targetField Field to store the lead value
     * @param int $offset Number of rows to look ahead (default: 1)
     * @param mixed $default Default value when no next row exists
     * @param string|null $partitionBy Field to partition by (resets lead within partitions)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function lead(
        iterable $data,
        string $field,
        string $targetField,
        int $offset = 1,
        mixed $default = null,
        ?string $partitionBy = null
    ): Generator {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);
        $partitionIndex = $partitionBy ? self::getFieldIndex($tableData['header'], $partitionBy) : null;

        // Yield updated header
        yield $tableData['header'];

        // Group rows by partition if partitioning
        if ($partitionIndex !== null) {
            $partitions = [];
            foreach ($tableData['rows'] as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            // Process each partition
            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyLead($partitionRows, $fieldIndex, $targetIndex, $offset, $default);
                foreach ($processedRows as $row) {
                    yield $row;
                }
            }
        } else {
            // Process all rows
            $processedRows = self::applyLead($tableData['rows'], $fieldIndex, $targetIndex, $offset, $default);
            foreach ($processedRows as $row) {
                yield $row;
            }
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
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $targetField Field to store row numbers
     * @param string|null $partitionBy Field to partition by (resets numbering)
     * @param string|null $orderBy Field to order by before numbering
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function rowNumber(
        iterable $data,
        string $targetField,
        ?string $partitionBy = null,
        ?string $orderBy = null
    ): Generator {
        $tableData = self::processTable($data);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);

        // Yield updated header
        yield $tableData['header'];

        $rows = $tableData['rows'];

        // Apply ordering if specified
        if ($orderBy !== null) {
            $orderIndex = self::getFieldIndex($tableData['header'], $orderBy);
            usort($rows, fn($a, $b) => ($a[$orderIndex] ?? null) <=> ($b[$orderIndex] ?? null));
        }

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($tableData['header'], $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            foreach ($partitions as $partitionRows) {
                $rowNum = 1;
                foreach ($partitionRows as $row) {
                    $row[$targetIndex] = $rowNum++;
                    yield $row;
                }
            }
        } else {
            $rowNum = 1;
            foreach ($rows as $row) {
                $row[$targetIndex] = $rowNum++;
                yield $row;
            }
        }
    }

    /**
     * Assign rank with gaps for ties (1, 1, 3, 4, 4, 6).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order (highest value = rank 1)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function rank(
        iterable $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): Generator {
        $tableData = self::processTable($data);
        $orderIndex = self::getFieldIndex($tableData['header'], $orderBy);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);

        // Yield updated header
        yield $tableData['header'];

        $rows = $tableData['rows'];

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($tableData['header'], $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    yield $row;
                }
            }
        } else {
            $processedRows = self::applyRank($rows, $orderIndex, $targetIndex, $descending);
            foreach ($processedRows as $row) {
                yield $row;
            }
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

            $row[$targetIndex] = $rank;
            $prevValue = $currentValue;

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Assign rank without gaps for ties (1, 1, 2, 3, 3, 4).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order (highest value = rank 1)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function denseRank(
        iterable $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): Generator {
        $tableData = self::processTable($data);
        $orderIndex = self::getFieldIndex($tableData['header'], $orderBy);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);

        // Yield updated header
        yield $tableData['header'];

        $rows = $tableData['rows'];

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($tableData['header'], $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyDenseRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    yield $row;
                }
            }
        } else {
            $processedRows = self::applyDenseRank($rows, $orderIndex, $targetIndex, $descending);
            foreach ($processedRows as $row) {
                yield $row;
            }
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
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $orderBy Field to rank by
     * @param string $targetField Field to store percentage ranks
     * @param string|null $partitionBy Field to partition by (separate rankings)
     * @param bool $descending Rank in descending order
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function percentRank(
        iterable $data,
        string $orderBy,
        string $targetField,
        ?string $partitionBy = null,
        bool $descending = false
    ): Generator {
        $tableData = self::processTable($data);
        $orderIndex = self::getFieldIndex($tableData['header'], $orderBy);
        $targetIndex = self::ensureTargetField($tableData['header'], $targetField);

        // Yield updated header
        yield $tableData['header'];

        $rows = $tableData['rows'];

        // Apply partitioning if specified
        if ($partitionBy !== null) {
            $partitionIndex = self::getFieldIndex($tableData['header'], $partitionBy);
            $partitions = [];

            foreach ($rows as $row) {
                $partition = serialize($row[$partitionIndex] ?? null);
                $partitions[$partition][] = $row;
            }

            foreach ($partitions as $partitionRows) {
                $processedRows = self::applyPercentRank($partitionRows, $orderIndex, $targetIndex, $descending);
                foreach ($processedRows as $row) {
                    yield $row;
                }
            }
        } else {
            $processedRows = self::applyPercentRank($rows, $orderIndex, $targetIndex, $descending);
            foreach ($processedRows as $row) {
                yield $row;
            }
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
            $rows[0][$targetIndex] = 0.0;
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

            // Calculate percent rank: (rank - 1) / (total - 1)
            $row[$targetIndex] = (float)(($rank - 1) / ($count - 1));
            $prevValue = $currentValue;

            $result[] = $row;
        }

        return $result;
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
