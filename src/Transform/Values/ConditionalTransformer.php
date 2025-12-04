<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use InvalidArgumentException;

/**
 * Conditional transformation operations for ETL pipelines.
 *
 * Provides SQL-like conditional logic including CASE WHEN, COALESCE, NULL handling.
 */
final class ConditionalTransformer
{
    /**
     * Apply conditional logic: if condition is true, use then value, otherwise use else value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param callable $condition Function that returns bool given field value
     * @param string $target Target field for the result
     * @param mixed|callable $thenValue Value or callback for true condition
     * @param mixed|callable $elseValue Value or callback for false condition
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function when(
        array $headers,
        array $data,
        string $field,
        callable $condition,
        string $target,
        mixed $thenValue,
        mixed $elseValue
    ): array {
        $fieldIndex = self::getFieldIndex($headers, $field);

        // Add target field to header if not exists
        $targetIndex = array_search($target, $headers, true);
        $newHeaders = $headers;
        if ($targetIndex === false) {
            $newHeaders[] = $target;
            $targetIndex = count($newHeaders) - 1;
        }

        $newData = [];
        foreach ($data as $row) {
            $fieldValue = $row[$fieldIndex];
            $conditionResult = $condition($fieldValue);

            // Extend row if needed
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            if ($conditionResult) {
                $row[$targetIndex] = is_callable($thenValue) ? $thenValue($row) : $thenValue;
            }
            else {
                $row[$targetIndex] = is_callable($elseValue) ? $elseValue($row) : $elseValue;
            }

            $newData[] = $row;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Return the first non-null value from a list of fields.
     *
     * Similar to SQL COALESCE - returns the first non-null value in order.
     * Note: Empty strings, 0, and false are NOT considered null.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data The data to transform
     * @param string $target Target field for the result
     * @param array<string> $fields Fields to check in order
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     * @throws InvalidArgumentException If any field doesn't exist or fields array is empty
     */
    public static function coalesce(
        array $headers,
        array $data,
        string $target,
        array $fields
    ): array {
        if (empty($fields)) {
            throw new InvalidArgumentException('At least one field is required for coalesce');
        }

        $fieldIndices = [];
        foreach ($fields as $field) {
            $fieldIndices[] = self::getFieldIndex($headers, $field);
        }

        // Add target field to header if not exists
        $targetIndex = array_search($target, $headers, true);
        $newHeaders = $headers;
        if ($targetIndex === false) {
            $newHeaders[] = $target;
            $targetIndex = count($newHeaders) - 1;
        }

        $newData = [];
        foreach ($data as $row) {
            $result = null;
            foreach ($fieldIndices as $fieldIndex) {
                if ($row[$fieldIndex] !== null) {
                    $result = $row[$fieldIndex];

                    break;
                }
            }

            // Extend row if needed
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            $row[$targetIndex] = $result;
            $newData[] = $row;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Return null if the condition is true, otherwise return the original value.
     *
     * Useful for converting sentinel values to null (e.g., -999, 'N/A', etc.).
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param string $target Target field for the result
     * @param callable $condition Function that returns bool given field value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function nullIf(
        array $headers,
        array $data,
        string $field,
        string $target,
        callable $condition
    ): array {
        $fieldIndex = self::getFieldIndex($headers, $field);

        // Add target field to header if not exists
        $targetIndex = array_search($target, $headers, true);
        $newHeaders = $headers;
        if ($targetIndex === false) {
            $newHeaders[] = $target;
            $targetIndex = count($newHeaders) - 1;
        }

        $newData = [];
        foreach ($data as $row) {
            $fieldValue = $row[$fieldIndex];

            // Extend row if needed
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            $row[$targetIndex] = $condition($fieldValue) ? null : $fieldValue;
            $newData[] = $row;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Replace null values with a default value.
     *
     * Note: Only replaces actual null values, not empty strings or 0.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data The data to transform
     * @param string $field The field to check
     * @param string $target Target field for the result
     * @param mixed|callable $default Default value or callback if null
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function ifNull(
        array $headers,
        array $data,
        string $field,
        string $target,
        mixed $default
    ): array {
        $fieldIndex = self::getFieldIndex($headers, $field);

        // Add target field to header if not exists
        $targetIndex = array_search($target, $headers, true);
        $newHeaders = $headers;
        if ($targetIndex === false) {
            $newHeaders[] = $target;
            $targetIndex = count($newHeaders) - 1;
        }

        $newData = [];
        foreach ($data as $row) {
            $fieldValue = $row[$fieldIndex];

            // Extend row if needed
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            if ($fieldValue === null) {
                $row[$targetIndex] = is_callable($default) ? $default($row) : $default;
            }
            else {
                $row[$targetIndex] = $fieldValue;
            }

            $newData[] = $row;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Evaluate multiple conditions in order (like SQL CASE WHEN).
     *
     * Evaluates each condition in order and returns the corresponding value
     * for the first true condition. If no conditions match, returns the default.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param string $target Target field for the result
     * @param array<array{callable, mixed|callable}> $conditions Array of [condition, value] pairs
     * @param mixed|callable $default Default value if no conditions match
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function case(
        array $headers,
        array $data,
        string $field,
        string $target,
        array $conditions,
        mixed $default
    ): array {
        $fieldIndex = self::getFieldIndex($headers, $field);

        // Add target field to header if not exists
        $targetIndex = array_search($target, $headers, true);
        $newHeaders = $headers;
        if ($targetIndex === false) {
            $newHeaders[] = $target;
            $targetIndex = count($newHeaders) - 1;
        }

        $newData = [];
        foreach ($data as $row) {
            $fieldValue = $row[$fieldIndex];
            $result = is_callable($default) ? $default($row) : $default;

            foreach ($conditions as [$condition, $value]) {
                if ($condition($fieldValue)) {
                    $result = is_callable($value) ? $value($row) : $value;

                    break;
                }
            }

            // Extend row if needed
            if ($targetIndex >= count($row)) {
                $row[] = null;
            }

            $row[$targetIndex] = $result;
            $newData[] = $row;
        }

        return [$newHeaders, $newData];
    }

    /**
     * Get field index from header, throwing exception if not found.
     *
     * @param array<int|string, mixed> $header The header row
     * @param string $field The field name
     * @return int|string The field index
     * @throws InvalidArgumentException If field doesn't exist
     */
    private static function getFieldIndex(array $header, string $field): int|string
    {
        $index = array_search($field, $header, true);
        if ($index === false) {
            throw new InvalidArgumentException("Field '{$field}' does not exist");
        }

        return $index;
    }
}
