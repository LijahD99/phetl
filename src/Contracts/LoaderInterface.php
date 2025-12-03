<?php

declare(strict_types=1);

namespace Phetl\Contracts;

use Phetl\Support\LoadResult;

/**
 * Interface for data loaders that write tabular data to destinations.
 *
 * A loader is responsible for taking an iterable of rows and
 * persisting them to a target destination (file, database, etc.).
 */
interface LoaderInterface
{
    /**
     * Load data to the destination.
     *
     * Accepts an iterable where:
     * - First element is the header row (array of field names)
     * - Subsequent elements are data rows (arrays of values)
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return LoadResult Result containing row count, errors, warnings, etc.
     */
    public function load(iterable $data): LoadResult;
}
