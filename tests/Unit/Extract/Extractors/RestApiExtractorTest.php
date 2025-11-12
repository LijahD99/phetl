<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\RestApiExtractor;
use RuntimeException;

describe('RestApiExtractor', function () {
    it('implements extractor interface', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users');

        expect($extractor)->toBeInstanceOf(ExtractorInterface::class);
    });

    it('validates url is not empty', function () {
        new RestApiExtractor('');
    })->throws(InvalidArgumentException::class, 'URL cannot be empty');

    it('validates url is valid format', function () {
        new RestApiExtractor('not-a-valid-url');
    })->throws(InvalidArgumentException::class, 'Invalid URL format');

    it('extracts json array of objects', function () {
        // Mock HTTP response
        $mockResponse = json_encode([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse, // Special key for testing
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result)->toBe([
            ['id', 'name', 'email'],
            [1, 'Alice', 'alice@example.com'],
            [2, 'Bob', 'bob@example.com'],
            [3, 'Charlie', 'charlie@example.com'],
        ]);
    });

    it('handles empty json array', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => json_encode([]),
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result)->toBe([]);
    });

    it('handles inconsistent fields across objects', function () {
        $mockResponse = json_encode([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'email' => 'charlie@example.com'],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse,
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result)->toBe([
            ['id', 'name', 'email'],
            [1, 'Alice', null],
            [2, 'Bob', 'bob@example.com'],
            [3, null, 'charlie@example.com'],
        ]);
    });

    it('throws exception for invalid json', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => 'not valid json',
        ]);

        iterator_to_array($extractor->extract());
    })->throws(InvalidArgumentException::class, 'Invalid JSON');

    it('throws exception for non-array json', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => json_encode('string value'),
        ]);

        iterator_to_array($extractor->extract());
    })->throws(InvalidArgumentException::class, 'Response must be a JSON array');

    it('throws exception for array of non-objects', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => json_encode(['string', 'values']),
        ]);

        iterator_to_array($extractor->extract());
    })->throws(InvalidArgumentException::class, 'Response must contain objects');

    it('throws exception for http error status', function () {
        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => json_encode(['error' => 'Not found']),
            '_mock_status' => 404,
        ]);

        iterator_to_array($extractor->extract());
    })->throws(RuntimeException::class, 'HTTP error 404');

    it('can be iterated multiple times', function () {
        $mockResponse = json_encode([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse,
        ]);

        $first = iterator_to_array($extractor->extract());
        $second = iterator_to_array($extractor->extract());

        expect($first)->toBe($second);
    });

    it('preserves numeric values', function () {
        $mockResponse = json_encode([
            ['id' => 1, 'age' => 30, 'score' => 95.5],
            ['id' => 2, 'age' => 25, 'score' => 87.3],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse,
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result[1])->toBe([1, 30, 95.5]);
        expect($result[2])->toBe([2, 25, 87.3]);
    });

    it('handles null values', function () {
        $mockResponse = json_encode([
            ['id' => 1, 'name' => 'Alice', 'email' => null],
            ['id' => 2, 'name' => null, 'email' => 'bob@example.com'],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse,
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result[1])->toBe([1, 'Alice', null]);
        expect($result[2])->toBe([2, null, 'bob@example.com']);
    });

    it('handles boolean values', function () {
        $mockResponse = json_encode([
            ['id' => 1, 'active' => true, 'verified' => false],
            ['id' => 2, 'active' => false, 'verified' => true],
        ]);

        $extractor = new RestApiExtractor('https://api.example.com/users', [
            '_mock_response' => $mockResponse,
        ]);

        $result = iterator_to_array($extractor->extract());

        expect($result[1])->toBe([1, true, false]);
        expect($result[2])->toBe([2, false, true]);
    });
});
