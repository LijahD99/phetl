<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Closure;
use Generator;
use InvalidArgumentException;

/**
 * Row sorting transformations.
 */
class RowSorter
{
    /**
     * Sort rows by one or more fields.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string>|Closure $key Field name, array of fields, or custom comparator
     * @param bool $reverse Sort in descending order
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function sort(
        iterable $data,
        string|array|Closure $key,
        bool $reverse = false
    ): Generator {
        $headerProcessed = false;
        /** @var array<int|string, mixed> $header */
        $header = [];
        /** @var array<int, array<int|string, mixed>> $rows */
        $rows = [];

        // Collect all data
        foreach ($data as $row) {
            if (!$headerProcessed) {
                $header = $row;
                yield $row;
                $headerProcessed = true;
                continue;
            }
            $rows[] = $row;
        }

        // Sort the rows
        if ($key instanceof Closure) {
            // Custom comparator
            usort($rows, $key);
        } else {
            // Sort by field(s)
            $fields = is_array($key) ? $key : [$key];
            $fieldIndices = self::getFieldIndices($header, $fields);

            usort($rows, function ($a, $b) use ($fieldIndices, $reverse): int {
                foreach ($fieldIndices as $index) {
                    $aVal = $a[$index] ?? null;
                    $bVal = $b[$index] ?? null;

                    // Handle nulls - nulls sort last
                    if ($aVal === null && $bVal === null) {
                        continue;
                    }
                    if ($aVal === null) {
                        return 1;
                    }
                    if ($bVal === null) {
                        return -1;
                    }

                    // Compare values
                    $result = $aVal <=> $bVal;

                    if ($result !== 0) {
                        return $reverse ? -$result : $result;
                    }
                }

                return 0;
            });
        }

        // Yield sorted rows
        foreach ($rows as $row) {
            yield $row;
        }
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
}
