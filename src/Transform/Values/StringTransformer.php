<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use Generator;
use InvalidArgumentException;

/**
 * String transformation operations for text manipulation.
 */
class StringTransformer
{
    /**
     * Convert field values to uppercase.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function upper(iterable $data, string $field): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = strtoupper((string)$row[$fieldIndex]);
            }
            
            yield $row;
        }
    }

    /**
     * Convert field values to lowercase.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function lower(iterable $data, string $field): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = strtolower((string)$row[$fieldIndex]);
            }
            
            yield $row;
        }
    }

    /**
     * Trim whitespace (or other characters) from field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function trim(iterable $data, string $field, string $characters = " \t\n\r\0\x0B"): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = trim((string)$row[$fieldIndex], $characters);
            }
            
            yield $row;
        }
    }

    /**
     * Trim whitespace (or other characters) from left of field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function ltrim(iterable $data, string $field, string $characters = " \t\n\r\0\x0B"): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = ltrim((string)$row[$fieldIndex], $characters);
            }
            
            yield $row;
        }
    }

    /**
     * Trim whitespace (or other characters) from right of field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function rtrim(iterable $data, string $field, string $characters = " \t\n\r\0\x0B"): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = rtrim((string)$row[$fieldIndex], $characters);
            }
            
            yield $row;
        }
    }

    /**
     * Extract substring from field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $start
     * @param int|null $length
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function substring(iterable $data, string $field, int $start, ?int $length = null): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = $length === null 
                    ? substr((string)$row[$fieldIndex], $start)
                    : substr((string)$row[$fieldIndex], $start, $length);
            }
            
            yield $row;
        }
    }

    /**
     * Extract leftmost characters from field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function left(iterable $data, string $field, int $length): Generator
    {
        yield from self::substring($data, $field, 0, $length);
    }

    /**
     * Extract rightmost characters from field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function right(iterable $data, string $field, int $length): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = substr((string)$row[$fieldIndex], -$length);
            }
            
            yield $row;
        }
    }

    /**
     * Pad field values to specified length.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int $length
     * @param string $padString
     * @param int $padType STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function pad(
        iterable $data,
        string $field,
        int $length,
        string $padString = ' ',
        int $padType = STR_PAD_RIGHT
    ): Generator {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = str_pad((string)$row[$fieldIndex], $length, $padString, $padType);
            }
            
            yield $row;
        }
    }

    /**
     * Concatenate multiple fields into target field.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $targetField
     * @param array<string> $sourceFields
     * @param string $separator
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function concat(
        iterable $data,
        string $targetField,
        array $sourceFields,
        string $separator = ''
    ): Generator {
        $header = null;
        $sourceIndices = [];
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                foreach ($sourceFields as $field) {
                    $sourceIndices[] = self::getFieldIndex($header, $field);
                }
                $targetIndex = array_search($targetField, $header, true);
                if ($targetIndex === false) {
                    throw new InvalidArgumentException("Target field '$targetField' not found in header");
                }
                yield $row;
                continue;
            }

            $values = [];
            foreach ($sourceIndices as $idx) {
                $values[] = (string)($row[$idx] ?? '');
            }
            
            $row[$targetIndex] = implode($separator, $values);
            yield $row;
        }
    }

    /**
     * Split field value into array.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $delimiter
     * @param int $limit
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function split(iterable $data, string $field, string $delimiter, int $limit = PHP_INT_MAX): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = explode($delimiter, (string)$row[$fieldIndex], $limit);
            }
            
            yield $row;
        }
    }

    /**
     * Replace substring using regex pattern.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $pattern
     * @param string $replacement
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function replace(iterable $data, string $field, string $pattern, string $replacement): Generator
    {
        $header = null;
        $fieldIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);
                yield $row;
                continue;
            }

            if ($row[$fieldIndex] !== null) {
                $row[$fieldIndex] = preg_replace($pattern, $replacement, (string)$row[$fieldIndex]);
            }
            
            yield $row;
        }
    }

    /**
     * Extract pattern from field into new field.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @param string $pattern Regex pattern with capture group
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function extract(
        iterable $data,
        string $sourceField,
        string $targetField,
        string $pattern
    ): Generator {
        $header = null;
        $sourceIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $sourceIndex = self::getFieldIndex($header, $sourceField);
                $targetIndex = array_search($targetField, $header, true);
                if ($targetIndex === false) {
                    throw new InvalidArgumentException("Target field '$targetField' not found in header");
                }
                yield $row;
                continue;
            }

            if ($row[$sourceIndex] !== null && preg_match($pattern, (string)$row[$sourceIndex], $matches)) {
                $row[$targetIndex] = $matches[1] ?? null;
            } else {
                $row[$targetIndex] = null;
            }
            
            yield $row;
        }
    }

    /**
     * Check if field matches pattern, store result in target field.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @param string $pattern
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function match(
        iterable $data,
        string $sourceField,
        string $targetField,
        string $pattern
    ): Generator {
        $header = null;
        $sourceIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $sourceIndex = self::getFieldIndex($header, $sourceField);
                $targetIndex = array_search($targetField, $header, true);
                if ($targetIndex === false) {
                    throw new InvalidArgumentException("Target field '$targetField' not found in header");
                }
                yield $row;
                continue;
            }

            if ($row[$sourceIndex] !== null) {
                $row[$targetIndex] = (bool)preg_match($pattern, (string)$row[$sourceIndex]);
            } else {
                $row[$targetIndex] = false;
            }
            
            yield $row;
        }
    }

    /**
     * Calculate length of field values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $sourceField
     * @param string $targetField
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function length(iterable $data, string $sourceField, string $targetField): Generator
    {
        $header = null;
        $sourceIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $sourceIndex = self::getFieldIndex($header, $sourceField);
                $targetIndex = array_search($targetField, $header, true);
                if ($targetIndex === false) {
                    throw new InvalidArgumentException("Target field '$targetField' not found in header");
                }
                yield $row;
                continue;
            }

            if ($row[$sourceIndex] !== null) {
                $row[$targetIndex] = strlen((string)$row[$sourceIndex]);
            } else {
                $row[$targetIndex] = 0;
            }
            
            yield $row;
        }
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
