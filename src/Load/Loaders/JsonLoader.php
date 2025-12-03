<?php

declare(strict_types=1);

namespace Phetl\Load\Loaders;

use InvalidArgumentException;
use Phetl\Contracts\LoaderInterface;
use Phetl\Support\LoadResult;

/**
 * Loads data to JSON files as an array of objects.
 *
 * Converts tabular data (header + rows) into JSON array of objects
 * with optional pretty printing for readability.
 */
final class JsonLoader implements LoaderInterface
{
    /**
     * @param string $filePath Path to the JSON file
     * @param bool $prettyPrint Enable pretty printing (default: false)
     */
    public function __construct(
        private readonly string $filePath,
        private readonly bool $prettyPrint = false
    ) {
        $this->validate();
    }

    /**
     * @param iterable<int, array<int|string, mixed>> $data
     * @return LoadResult Result containing row count and operation details
     */
    public function load(iterable $data): LoadResult
    {
        $headers = null;
        $objects = [];

        foreach ($data as $row) {
            if ($headers === null) {
                $headers = array_values($row);

                continue;
            }

            // Convert row array to object using headers as keys
            $object = [];
            foreach ($headers as $index => $header) {
                $object[$header] = $row[$index] ?? null;
            }
            $objects[] = $object;
        }

        $flags = JSON_THROW_ON_ERROR;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($objects, $flags);

        if (file_put_contents($this->filePath, $json) === false) {
            throw new InvalidArgumentException('Cannot write to file: ' . $this->filePath);
        }

        return new LoadResult(count($objects));
    }

    /**
     * Validate file path is writable.
     */
    private function validate(): void
    {
        $directory = dirname($this->filePath);

        // Create directory if it doesn't exist
        if (! is_dir($directory)) {
            if (! @mkdir($directory, 0o755, true)) {
                throw new InvalidArgumentException('Cannot create directory: ' . $directory);
            }
        }

        // Check if directory is writable
        if (! is_writable($directory)) {
            throw new InvalidArgumentException('Directory is not writable: ' . $directory);
        }
    }
}
