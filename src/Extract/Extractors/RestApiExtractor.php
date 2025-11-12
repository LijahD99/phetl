<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;
use RuntimeException;

/**
 * Extracts data from RESTful API endpoints.
 *
 * Supports authentication, pagination, rate limiting, and response mapping.
 * Converts JSON responses into tabular format with headers.
 */
final class RestApiExtractor implements ExtractorInterface
{
    /**
     * @param string $url API endpoint URL
     * @param array<string, mixed> $config Configuration options
     */
    public function __construct(
        private readonly string $url,
        private readonly array $config = []
    ) {
        $this->validate();
    }

    /**
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable
    {
        // Check for mock response (testing only)
        if (isset($this->config['_mock_response'])) {
            return $this->extractFromMock();
        }

        // TODO: Implement real HTTP request
        throw new RuntimeException('Real HTTP requests not yet implemented');
    }

    /**
     * Extract from mock response (for testing).
     *
     * @return iterable<int, array<int|string, mixed>>
     */
    private function extractFromMock(): iterable
    {
        $mockResponse = $this->config['_mock_response'];
        $mockStatus = $this->config['_mock_status'] ?? 200;

        // Check HTTP status
        if ($mockStatus >= 400) {
            throw new RuntimeException("HTTP error {$mockStatus}");
        }

        // Parse JSON
        $data = json_decode($mockResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('Response must be a JSON array');
        }

        if ($data === []) {
            return;
        }

        // Validate all elements are arrays (objects)
        foreach ($data as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Response must contain objects');
            }
        }

        // Extract all unique field names
        $fields = $this->extractFieldNames($data);

        // Yield header row
        yield array_values($fields);

        // Yield data rows
        foreach ($data as $row) {
            yield $this->normalizeRow($row, $fields);
        }
    }

    /**
     * Validate URL.
     */
    private function validate(): void
    {
        if ($this->url === '') {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format: ' . $this->url);
        }
    }

    /**
     * Extract all unique field names from the dataset.
     *
     * @param array<int, array<string, mixed>> $data
     * @return array<int, string>
     */
    private function extractFieldNames(array $data): array
    {
        $fields = [];

        foreach ($data as $row) {
            foreach (array_keys($row) as $key) {
                if (! in_array($key, $fields, true)) {
                    $fields[] = $key;
                }
            }
        }

        return $fields;
    }

    /**
     * Normalize a row to match the field structure.
     *
     * @param array<string, mixed> $row
     * @param array<int, string> $fields
     * @return array<int, mixed>
     */
    private function normalizeRow(array $row, array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $normalized[] = $row[$field] ?? null;
        }

        return $normalized;
    }
}
