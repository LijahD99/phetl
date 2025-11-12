<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use Phetl\Table;

test('Table sort method sorts by single field', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['Charlie', 35],
        ['Alice', 30],
        ['Bob', 25],
    ]);

    $result = $table->sort('age')->toArray();

    expect($result[1][0])->toBe('Bob')
        ->and($result[2][0])->toBe('Alice')
        ->and($result[3][0])->toBe('Charlie');
});

test('Table sort method sorts descending', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['Alice', 30],
        ['Bob', 25],
        ['Charlie', 35],
    ]);

    $result = $table->sort('age', true)->toArray();

    expect($result[1][0])->toBe('Charlie')
        ->and($result[2][0])->toBe('Alice')
        ->and($result[3][0])->toBe('Bob');
});

test('Table sortBy method sorts by multiple fields', function (): void {
    $table = Table::fromArray([
        ['dept', 'name', 'age'],
        ['IT', 'Bob', 25],
        ['HR', 'Alice', 30],
        ['IT', 'Diana', 28],
        ['HR', 'Charlie', 30],
    ]);

    $result = $table->sortBy('dept', 'age')->toArray();

    expect($result[1])->toBe(['HR', 'Alice', 30])
        ->and($result[2])->toBe(['HR', 'Charlie', 30])
        ->and($result[3])->toBe(['IT', 'Bob', 25])
        ->and($result[4])->toBe(['IT', 'Diana', 28]);
});

test('Table sortByDesc method sorts descending', function (): void {
    $table = Table::fromArray([
        ['name', 'score'],
        ['Alice', 85],
        ['Bob', 92],
        ['Charlie', 78],
    ]);

    $result = $table->sortByDesc('score')->toArray();

    expect($result[1][0])->toBe('Bob')     // 92
        ->and($result[2][0])->toBe('Alice')   // 85
        ->and($result[3][0])->toBe('Charlie'); // 78
});

test('Table sort works with custom comparator', function (): void {
    $table = Table::fromArray([
        ['name', 'value'],
        ['Alice', 'short'],
        ['Bob', 'very long text'],
        ['Charlie', 'medium'],
    ]);

    $result = $table->sort(fn ($a, $b) => strlen($a[1]) <=> strlen($b[1]))->toArray();

    expect($result[1][0])->toBe('Alice')    // "short" = 5
        ->and($result[2][0])->toBe('Charlie')  // "medium" = 6
        ->and($result[3][0])->toBe('Bob');     // "very long text" = 14
});

test('sorting can be chained with other transformations', function (): void {
    $table = Table::fromArray([
        ['name', 'age', 'status'],
        ['Charlie', 35, 'active'],
        ['Alice', 30, 'inactive'],
        ['Bob', 25, 'active'],
        ['Diana', 40, 'active'],
    ]);

    $result = $table
        ->whereEquals('status', 'active')
        ->sortBy('age')
        ->selectColumns('name', 'age')
        ->toArray();

    expect($result)->toHaveCount(4) // header + 3 active users
        ->and($result[0])->toBe(['name', 'age'])
        ->and($result[1])->toBe(['Bob', 25])
        ->and($result[2])->toBe(['Charlie', 35])
        ->and($result[3])->toBe(['Diana', 40]);
});

test('sort preserves column transformations', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['charlie', 35],
        ['alice', 30],
        ['bob', 25],
    ]);

    $result = $table
        ->convert('name', 'strtoupper')
        ->sortBy('age')
        ->toArray();

    expect($result[1])->toBe(['BOB', 25])
        ->and($result[2])->toBe(['ALICE', 30])
        ->and($result[3])->toBe(['CHARLIE', 35]);
});

test('multiple sorts can be applied', function (): void {
    $table = Table::fromArray([
        ['category', 'priority', 'name'],
        ['B', 2, 'Item1'],
        ['A', 1, 'Item2'],
        ['A', 2, 'Item3'],
        ['B', 1, 'Item4'],
    ]);

    // First sort by category, then re-sort by priority
    // Last sort wins with current implementation
    $result = $table
        ->sortBy('category')
        ->sortBy('priority')
        ->toArray();

    expect($result[1][2])->toBeIn(['Item2', 'Item4']) // priority 1
        ->and($result[2][2])->toBeIn(['Item2', 'Item4']); // priority 1
});

test('sort works with empty table', function (): void {
    $table = Table::fromArray([['name', 'age']]);

    $result = $table->sortBy('age')->toArray();

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(['name', 'age']);
});

test('sort handles tables with single row', function (): void {
    $table = Table::fromArray([
        ['name', 'age'],
        ['Alice', 30],
    ]);

    $result = $table->sortBy('age')->toArray();

    expect($result)->toHaveCount(2)
        ->and($result[1])->toBe(['Alice', 30]);
});
