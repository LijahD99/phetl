<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Closure;
use InvalidArgumentException;

/**
 * Row sorting transformations.
 */
class RowSorter
{
    /**
     * Sort rows by one or more fields.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string|array<string>|Closure $key Field name, array of fields, or custom comparator
     * @param bool $reverse Sort in descending order
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function sort(
        array $headers,
        array $data,
        string|array|Closure $key,
        bool $reverse = false
    ): array {
        $rows = $data;

        // Sort the rows
        if ($key instanceof Closure) {
            // Custom comparator
            usort($rows, $key);
        }
        else {
            // Sort by field(s)
            $fields = is_array($key) ? $key : [$key];
            $fieldIndices = self::getFieldIndices($headers, $fields);

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

        return [$headers, $rows];
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
