<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Closure;
use Generator;

/**
 * Row filtering transformations.
 */
class RowFilter
{
    /**
     * Filter rows using a custom predicate function.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param Closure $predicate Function(array $row): bool
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function filter(iterable $data, Closure $predicate): Generator
    {
        $headerProcessed = false;
        /** @var array<int|string, mixed>|null $header */
        $header = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                $header = $row;
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Create associative array for easier access in predicate
            $assocRow = [];
            if ($header !== null) {
                foreach ($header as $index => $col) {
                    $assocRow[$col] = $row[$index] ?? null;
                }
            }

            if ($predicate($assocRow)) {
                yield $row;
            }
        }
    }

    /**
     * Filter rows where a field equals a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereEquals(iterable $data, string $field, mixed $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) === $value);
    }

    /**
     * Filter rows where a field does not equal a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereNotEquals(iterable $data, string $field, mixed $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) !== $value);
    }

    /**
     * Filter rows where a field is greater than a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereGreaterThan(iterable $data, string $field, int|float $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) > $value);
    }

    /**
     * Filter rows where a field is less than a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereLessThan(iterable $data, string $field, int|float $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) < $value);
    }

    /**
     * Filter rows where a field is greater than or equal to a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereGreaterThanOrEqual(iterable $data, string $field, int|float $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) >= $value);
    }

    /**
     * Filter rows where a field is less than or equal to a value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function whereLessThanOrEqual(iterable $data, string $field, int|float $value): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) <= $value);
    }

    /**
     * Filter rows where a field's value is in an array of values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<mixed> $values
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereIn(iterable $data, string $field, array $values): Generator
    {
        return self::filter($data, fn(array $row) => in_array($row[$field] ?? null, $values, true));
    }

    /**
     * Filter rows where a field's value is not in an array of values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<mixed> $values
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereNotIn(iterable $data, string $field, array $values): Generator
    {
        return self::filter($data, fn(array $row) => !in_array($row[$field] ?? null, $values, true));
    }

    /**
     * Filter rows where a field is null.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereNull(iterable $data, string $field): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) === null);
    }

    /**
     * Filter rows where a field is not null.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereNotNull(iterable $data, string $field): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) !== null);
    }

    /**
     * Filter rows where a field value is true.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereTrue(iterable $data, string $field): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) === true);
    }

    /**
     * Filter rows where a field value is false.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereFalse(iterable $data, string $field): Generator
    {
        return self::filter($data, fn(array $row) => ($row[$field] ?? null) === false);
    }

    /**
     * Filter rows where a string field contains a substring.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function whereContains(iterable $data, string $field, string $substring): Generator
    {
        return self::filter(
            $data,
            fn(array $row) => is_string($row[$field] ?? null) && str_contains($row[$field], $substring)
        );
    }
}
