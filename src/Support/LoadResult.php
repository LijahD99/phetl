<?php

declare(strict_types=1);

namespace Phetl\Support;

/**
 * Result object returned from load operations.
 *
 * Provides observability into load operations while maintaining a simple API.
 * Call ->rowCount() to get the number of rows loaded, or use other methods
 * for more detailed information about the operation.
 */
final class LoadResult
{
    /**
     * Create a new LoadResult.
     *
     * @param int $rowCount Number of rows successfully loaded (excluding header)
     * @param array<int, string> $errors Error messages encountered during loading
     * @param array<int, string> $warnings Warning messages from the load operation
     * @param float|null $durationSeconds Time taken to complete the operation
     */
    public function __construct(
        private readonly int $rowCount,
        private readonly array $errors = [],
        private readonly array $warnings = [],
        private readonly ?float $durationSeconds = null
    ) {}

    /**
     * Get the number of rows successfully loaded.
     *
     * @return int Number of rows loaded (excluding header)
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Check if the load operation was successful.
     *
     * @return bool True if no errors occurred
     */
    public function success(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all error messages from the load operation.
     *
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warning messages from the load operation.
     *
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get the duration of the load operation.
     *
     * @return float|null Duration in seconds, or null if not tracked
     */
    public function duration(): ?float
    {
        return $this->durationSeconds;
    }

    /**
     * Check if there are any warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Check if there are any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
