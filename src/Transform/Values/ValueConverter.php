<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use InvalidArgumentException;

/**
 * Value conversion transformations for transforming cell values.
 */
class ValueConverter
{
    /**
     * Convert values in a specific column using a function.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field Field name to convert
     * @param callable|string $converter Callable or function name
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function convert(array $headers, array $data, string $field, callable|string $converter): array
    {
        // Find field index
        $fieldIndex = array_search($field, $headers, true);
        if ($fieldIndex === false) {
            throw new InvalidArgumentException("Field '$field' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            // Convert the value
            if (isset($row[$fieldIndex])) {
                if (is_string($converter) && is_callable($converter)) {
                    // Use string as function name (e.g., 'strtoupper', 'intval')
                    $row[$fieldIndex] = $converter($row[$fieldIndex]);
                }
                else {
                    /** @var callable $converter */
                    $row[$fieldIndex] = $converter($row[$fieldIndex]);
                }
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Convert values in multiple columns.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param array<string, callable|string> $conversions Field => Converter mapping
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function convertMultiple(array $headers, array $data, array $conversions): array
    {
        // Build field index mapping
        $fieldIndices = [];
        foreach ($conversions as $field => $converter) {
            $index = array_search($field, $headers, true);
            if ($index !== false) {
                $fieldIndices[$field] = $index;
            }
        }

        $newData = [];
        foreach ($data as $row) {
            // Convert values
            foreach ($fieldIndices as $field => $index) {
                if (isset($row[$index])) {
                    $converter = $conversions[$field];
                    if (is_string($converter) && is_callable($converter)) {
                        $row[$index] = $converter($row[$index]);
                    }
                    else {
                        /** @var callable $converter */
                        $row[$index] = $converter($row[$index]);
                    }
                }
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }
}
