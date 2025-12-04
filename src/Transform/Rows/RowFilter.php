<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Closure;

/**
 * Row filtering transformations.
 */
class RowFilter
{
    /**
     * Filter rows using a custom predicate function.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param Closure $predicate Function(array $row): bool
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function filter(array $headers, array $data, Closure $predicate): array
    {
        $result = [];

        foreach ($data as $row) {
            // Create associative array for easier access in predicate
            $assocRow = [];
            foreach ($headers as $index => $col) {
                $assocRow[$col] = $row[$index] ?? null;
            }

            if ($predicate($assocRow)) {
                $result[] = $row;
            }
        }

        return [$headers, $result];
    }

    /**
     * Filter rows where a field equals a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param mixed $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereEquals(array $headers, array $data, string $field, mixed $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) === $value);
    }

    /**
     * Filter rows where a field does not equal a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param mixed $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereNotEquals(array $headers, array $data, string $field, mixed $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) !== $value);
    }

    /**
     * Filter rows where a field is greater than a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int|float $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereGreaterThan(array $headers, array $data, string $field, int|float $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) > $value);
    }

    /**
     * Filter rows where a field is less than a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int|float $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereLessThan(array $headers, array $data, string $field, int|float $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) < $value);
    }

    /**
     * Filter rows where a field is greater than or equal to a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int|float $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereGreaterThanOrEqual(array $headers, array $data, string $field, int|float $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) >= $value);
    }

    /**
     * Filter rows where a field is less than or equal to a value.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int|float $value
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereLessThanOrEqual(array $headers, array $data, string $field, int|float $value): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) <= $value);
    }

    /**
     * Filter rows where a field's value is in an array of values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param array<mixed> $values
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereIn(array $headers, array $data, string $field, array $values): array
    {
        return self::filter($headers, $data, fn (array $row) => in_array($row[$field] ?? null, $values, true));
    }

    /**
     * Filter rows where a field's value is not in an array of values.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param array<mixed> $values
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereNotIn(array $headers, array $data, string $field, array $values): array
    {
        return self::filter($headers, $data, fn (array $row) => ! in_array($row[$field] ?? null, $values, true));
    }

    /**
     * Filter rows where a field is null.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereNull(array $headers, array $data, string $field): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) === null);
    }

    /**
     * Filter rows where a field is not null.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereNotNull(array $headers, array $data, string $field): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) !== null);
    }

    /**
     * Filter rows where a field value is true.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereTrue(array $headers, array $data, string $field): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) === true);
    }

    /**
     * Filter rows where a field value is false.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereFalse(array $headers, array $data, string $field): array
    {
        return self::filter($headers, $data, fn (array $row) => ($row[$field] ?? null) === false);
    }

    /**
     * Filter rows where a string field contains a substring.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $substring
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function whereContains(array $headers, array $data, string $field, string $substring): array
    {
        return self::filter(
            $headers,
            $data,
            fn (array $row) => is_string($row[$field] ?? null) && str_contains($row[$field], $substring)
        );
    }
}
