<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use InvalidArgumentException;

/**
 * Value replacement transformations.
 */
class ValueReplacer
{
    /**
     * Replace a specific value in a field with another value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field Field name
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function replace(
        array $headers,
        array $data,
        string $field,
        mixed $oldValue,
        mixed $newValue
    ): array {
        $fieldIndex = array_search($field, $headers, true);
        if ($fieldIndex === false) {
            throw new InvalidArgumentException("Field '$field' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            // Replace value if it matches
            if (isset($row[$fieldIndex]) && $row[$fieldIndex] === $oldValue) {
                $row[$fieldIndex] = $newValue;
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Replace multiple values in a field using a mapping.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field Field name
     * @param array<mixed, mixed> $mapping Old value => New value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function replaceMap(array $headers, array $data, string $field, array $mapping): array
    {
        $fieldIndex = array_search($field, $headers, true);
        if ($fieldIndex === false) {
            throw new InvalidArgumentException("Field '$field' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            // Replace value if found in mapping
            if (isset($row[$fieldIndex])) {
                $currentValue = $row[$fieldIndex];
                if (array_key_exists($currentValue, $mapping)) {
                    $row[$fieldIndex] = $mapping[$currentValue];
                }
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Replace all occurrences across all fields.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function replaceAll(array $headers, array $data, mixed $oldValue, mixed $newValue): array
    {
        $newData = [];
        foreach ($data as $row) {
            // Replace in all fields
            foreach ($row as $index => $value) {
                if ($value === $oldValue) {
                    $row[$index] = $newValue;
                }
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }
}
