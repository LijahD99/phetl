<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

/**
 * Column renaming transformations.
 */
class ColumnRenamer
{
    /**
     * Rename columns using a mapping array.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param array<string, string> $mapping Old name => New name
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function rename(array $headers, array $data, array $mapping): array
    {
        if ($mapping === []) {
            return [$headers, $data];
        }

        // Rename header columns
        $newHeaders = [];
        foreach ($headers as $column) {
            $newHeaders[] = $mapping[$column] ?? $column;
        }

        // Data rows remain unchanged
        return [$newHeaders, $data];
    }

    /**
     * Rename a single column.
     *
     * @param array<string> $headers
     * @param array<int, array<int|string, mixed>> $data
     * @param string $oldName
     * @param string $newName
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public static function renameColumn(array $headers, array $data, string $oldName, string $newName): array
    {
        return self::rename($headers, $data, [$oldName => $newName]);
    }
}
