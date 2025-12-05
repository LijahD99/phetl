<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Values;

use InvalidArgumentException;
use Phetl\Transform\Values\ValueConverter;
use Phetl\Transform\Values\ValueReplacer;

beforeEach(function (): void {
    $this->headers = ['name', 'age', 'email'];
    $this->data = [
        ['Alice', 30, 'alice@example.com'],
        ['Bob', 25, 'bob@example.com'],
        ['Charlie', 35, 'CHARLIE@EXAMPLE.COM'],
    ];
});

// ====================
// Value Conversion Tests
// ====================

test('convert applies function to field values', function (): void {
    [$headers, $data] = ValueConverter::convert($this->headers, $this->data, 'name', 'strtoupper');

    expect($headers)->toBe(['name', 'age', 'email'])
        ->and($data[0])->toBe(['ALICE', 30, 'alice@example.com'])
        ->and($data[1])->toBe(['BOB', 25, 'bob@example.com'])
        ->and($data[2])->toBe(['CHARLIE', 35, 'CHARLIE@EXAMPLE.COM']);
});

test('convert applies closure to field values', function (): void {
    [$headers, $data] = ValueConverter::convert(
        $this->headers,
        $this->data,
        'age',
        fn ($age) => $age * 2
    );

    expect($data[0][1])->toBe(60)
        ->and($data[1][1])->toBe(50)
        ->and($data[2][1])->toBe(70);
});

test('convert works with string function names', function (): void {
    [$headers, $data] = ValueConverter::convert($this->headers, $this->data, 'email', 'strtolower');

    expect($data[0][2])->toBe('alice@example.com')
        ->and($data[1][2])->toBe('bob@example.com')
        ->and($data[2][2])->toBe('charlie@example.com');
});

test('convert throws exception for invalid field', function (): void {
    ValueConverter::convert($this->headers, $this->data, 'invalid_field', 'strtoupper');
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('convert handles empty data gracefully', function (): void {
    $emptyHeaders = ['name', 'age'];
    $emptyData = [];
    [$headers, $data] = ValueConverter::convert($emptyHeaders, $emptyData, 'name', 'strtoupper');

    expect($data)->toHaveCount(0)
        ->and($headers)->toBe(['name', 'age']);
});

test('convertMultiple applies multiple conversions', function (): void {
    [$headers, $data] = ValueConverter::convertMultiple($this->headers, $this->data, [
        'name' => 'strtoupper',
        'age' => fn ($age) => $age + 10,
    ]);

    expect($data[0])->toBe(['ALICE', 40, 'alice@example.com'])
        ->and($data[1])->toBe(['BOB', 35, 'bob@example.com'])
        ->and($data[2])->toBe(['CHARLIE', 45, 'CHARLIE@EXAMPLE.COM']);
});

test('convertMultiple ignores non-existent fields', function (): void {
    [$headers, $data] = ValueConverter::convertMultiple($this->headers, $this->data, [
        'name' => 'strtoupper',
        'invalid_field' => 'strtolower',
    ]);

    expect($data[0][0])->toBe('ALICE');
});

// ====================
// Value Replacement Tests
// ====================

test('replace replaces specific value in field', function (): void {
    [$headers, $data] = ValueReplacer::replace($this->headers, $this->data, 'age', 30, 999);

    expect($data[0][1])->toBe(999)
        ->and($data[1][1])->toBe(25)
        ->and($data[2][1])->toBe(35);
});

test('replace only affects exact matches', function (): void {
    [$headers, $data] = ValueReplacer::replace($this->headers, $this->data, 'name', 'Bob', 'Robert');

    expect($data[0][0])->toBe('Alice')
        ->and($data[1][0])->toBe('Robert')
        ->and($data[2][0])->toBe('Charlie');
});

test('replace throws exception for invalid field', function (): void {
    ValueReplacer::replace($this->headers, $this->data, 'invalid_field', 30, 999);
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('replaceMap replaces multiple values using mapping', function (): void {
    [$headers, $data] = ValueReplacer::replaceMap($this->headers, $this->data, 'age', [
        30 => 31,
        25 => 26,
    ]);

    expect($data[0][1])->toBe(31)
        ->and($data[1][1])->toBe(26)
        ->and($data[2][1])->toBe(35); // Unchanged
});

test('replaceMap ignores unmapped values', function (): void {
    [$headers, $data] = ValueReplacer::replaceMap($this->headers, $this->data, 'name', [
        'Alice' => 'Alicia',
        'Unknown' => 'N/A',
    ]);

    expect($data[0][0])->toBe('Alicia')
        ->and($data[1][0])->toBe('Bob') // Unchanged
        ->and($data[2][0])->toBe('Charlie'); // Unchanged
});

test('replaceMap throws exception for invalid field', function (): void {
    ValueReplacer::replaceMap($this->headers, $this->data, 'invalid_field', [30 => 31]);
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('replaceAll replaces value across all fields', function (): void {
    $headers = ['name', 'status', 'category'];
    $data = [
        ['Alice', 'active', 'VIP'],
        ['Bob', 'inactive', 'active'],
        ['Charlie', 'active', 'regular'],
    ];

    [$resultHeaders, $resultData] = ValueReplacer::replaceAll($headers, $data, 'active', 'ACTIVE');

    expect($resultData[0])->toBe(['Alice', 'ACTIVE', 'VIP'])
        ->and($resultData[1])->toBe(['Bob', 'inactive', 'ACTIVE'])
        ->and($resultData[2])->toBe(['Charlie', 'ACTIVE', 'regular']);
});

test('replaceAll preserves header', function (): void {
    [$headers, $data] = ValueReplacer::replaceAll($this->headers, $this->data, 'name', 'REPLACED');

    expect($headers)->toBe(['name', 'age', 'email']);
});

test('replaceAll handles no matches', function (): void {
    [$headers, $data] = ValueReplacer::replaceAll($this->headers, $this->data, 'nonexistent', 'replaced');

    expect($headers)->toBe($this->headers)
        ->and($data)->toEqual($this->data);
});
