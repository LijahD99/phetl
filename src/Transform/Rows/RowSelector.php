<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use InvalidArgumentException;

/**
 * Row selection transformations for limiting and slicing table data.
 */
class RowSelector
{
    /**
     * Select the first N rows.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param int $limit
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function head(array $headers, array $data, int $limit): array
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be non-negative');
        }

        return [
            $headers,
            array_slice($data, 0, $limit),
        ];
    }

    /**
     * Select the last N rows.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param int $limit
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function tail(array $headers, array $data, int $limit): array
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be non-negative');
        }

        if ($limit === 0) {
            return [$headers, []];
        }

        return [
            $headers,
            array_slice($data, -$limit),
        ];
    }

    /**
     * Select a slice of rows by range.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param int $start
     * @param int|null $stop
     * @param int $step
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function slice(array $headers, array $data, int $start, ?int $stop = null, int $step = 1): array
    {
        if ($start < 0) {
            throw new InvalidArgumentException('Start index must be non-negative');
        }

        if ($stop !== null && $stop < $start) {
            throw new InvalidArgumentException('Stop index must be greater than or equal to start');
        }

        if ($step < 1) {
            throw new InvalidArgumentException('Step must be positive');
        }

        $result = [];
        $length = $stop ?? count($data);

        for ($i = $start; $i < $length && $i < count($data); $i += $step) {
            $result[] = $data[$i];
        }

        return [$headers, $result];
    }

    /**
     * Skip the first N rows.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param int $count
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function skip(array $headers, array $data, int $count): array
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count must be non-negative');
        }

        return [
            $headers,
            array_slice($data, $count),
        ];
    }
}
