<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use InvalidArgumentException;

/**
 * String transformation operations for text manipulation.
 */
class StringTransformer
{
    /**
     * Convert field values to uppercase.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function upper(array $headers, array $data, string $field): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = strtoupper((string) $row[$fieldIndex]);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Convert field values to lowercase.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function lower(array $headers, array $data, string $field): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = strtolower((string) $row[$fieldIndex]);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Trim whitespace (or other characters) from field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function trim(array $headers, array $data, string $field, string $characters = " \t\n\r\0\x0B"): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = trim((string) $row[$fieldIndex], $characters);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Trim whitespace (or other characters) from left of field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function ltrim(array $headers, array $data, string $field, string $characters = " \t\n\r\0\x0B"): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = ltrim((string) $row[$fieldIndex], $characters);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Trim whitespace (or other characters) from right of field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function rtrim(array $headers, array $data, string $field, string $characters = " \t\n\r\0\x0B"): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = rtrim((string) $row[$fieldIndex], $characters);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Extract substring from field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $start
     * @param int|null $length
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function substring(array $headers, array $data, string $field, int $start, ?int $length = null): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = $length === null
                    ? substr((string) $row[$fieldIndex], $start)
                    : substr((string) $row[$fieldIndex], $start, $length);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Extract leftmost characters from field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function left(array $headers, array $data, string $field, int $length): array
    {
        return self::substring($headers, $data, $field, 0, $length);
    }

    /**
     * Extract rightmost characters from field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function right(array $headers, array $data, string $field, int $length): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = substr((string) $row[$fieldIndex], -$length);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Pad field values to specified length.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @param string $padString
     * @param int $padType STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function pad(
        array $headers,
        array $data,
        string $field,
        int $length,
        string $padString = ' ',
        int $padType = STR_PAD_RIGHT
    ): array {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = str_pad((string) $row[$fieldIndex], $length, $padString, $padType);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Concatenate multiple fields into target field.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $targetField
     * @param array<string> $sourceFields
     * @param string $separator
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function concat(
        array $headers,
        array $data,
        string $targetField,
        array $sourceFields,
        string $separator = ''
    ): array {
        $sourceIndices = [];
        foreach ($sourceFields as $field) {
            $sourceIndices[] = self::getFieldIndex($headers, $field);
        }
        $targetIndex = array_search($targetField, $headers, true);
        if ($targetIndex === false) {
            throw new InvalidArgumentException("Target field '$targetField' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            $values = [];
            foreach ($sourceIndices as $idx) {
                $values[] = (string) ($row[$idx] ?? '');
            }
            $row[$targetIndex] = implode($separator, $values);
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Split field value into array.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $delimiter
     * @param int $limit
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function split(array $headers, array $data, string $field, string $delimiter, int $limit = PHP_INT_MAX): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = explode($delimiter, (string) $row[$fieldIndex], $limit);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Replace substring using regex pattern.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $pattern
     * @param string $replacement
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function replace(array $headers, array $data, string $field, string $pattern, string $replacement): array
    {
        $fieldIndex = self::getFieldIndex($headers, $field);
        $newData = [];

        foreach ($data as $row) {
            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = preg_replace($pattern, $replacement, (string) $row[$fieldIndex]);
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Extract pattern from field into new field.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @param string $pattern Regex pattern with capture group
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function extract(
        array $headers,
        array $data,
        string $sourceField,
        string $targetField,
        string $pattern
    ): array {
        $sourceIndex = self::getFieldIndex($headers, $sourceField);
        $targetIndex = array_search($targetField, $headers, true);
        if ($targetIndex === false) {
            throw new InvalidArgumentException("Target field '$targetField' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            if ($row[$sourceIndex] !== null && preg_match($pattern, (string) $row[$sourceIndex], $matches)) {
                $row[$targetIndex] = $matches[1] ?? null;
            }
            else {
                $row[$targetIndex] = null;
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Check if field matches pattern, store result in target field.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @param string $pattern
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function match(
        array $headers,
        array $data,
        string $sourceField,
        string $targetField,
        string $pattern
    ): array {
        $sourceIndex = self::getFieldIndex($headers, $sourceField);
        $targetIndex = array_search($targetField, $headers, true);
        if ($targetIndex === false) {
            throw new InvalidArgumentException("Target field '$targetField' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            if ($row[$sourceIndex] !== null) {
                $row[$targetIndex] = (bool) preg_match($pattern, (string) $row[$sourceIndex]);
            }
            else {
                $row[$targetIndex] = false;
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
    }

    /**
     * Calculate length of field values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function length(array $headers, array $data, string $sourceField, string $targetField): array
    {
        $sourceIndex = self::getFieldIndex($headers, $sourceField);
        $targetIndex = array_search($targetField, $headers, true);
        if ($targetIndex === false) {
            throw new InvalidArgumentException("Target field '$targetField' not found in header");
        }

        $newData = [];
        foreach ($data as $row) {
            if ($row[$sourceIndex] !== null) {
                $row[$targetIndex] = strlen((string) $row[$sourceIndex]);
            }
            else {
                $row[$targetIndex] = 0;
            }
            $newData[] = $row;
        }

        return [$headers, $newData];
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
}
