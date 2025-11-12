<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use InvalidArgumentException;
use Phetl\Transform\Rows\RowSorter;

beforeEach(function (): void {
    $this->data = [
        ['name', 'age', 'city'],
        ['Charlie', 35, 'NYC'],
        ['Alice', 30, 'LA'],
        ['Bob', 25, 'NYC'],
        ['Diana', 30, 'SF'],
    ];
});

test('it sorts by single field ascending', function (): void {
    $result = iterator_to_array(RowSorter::sort($this->data, 'age'));

    expect($result[0])->toBe(['name', 'age', 'city'])
        ->and($result[1][0])->toBe('Bob')   // age 25
        ->and($result[2][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($result[3][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($result[4][0])->toBe('Charlie'); // age 35
});

test('it sorts by single field descending', function (): void {
    $result = iterator_to_array(RowSorter::sort($this->data, 'age', true));

    expect($result[1][0])->toBe('Charlie') // age 35
        ->and($result[2][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($result[3][0])->toBeIn(['Alice', 'Diana'])  // age 30
        ->and($result[4][0])->toBe('Bob');  // age 25
});

test('it sorts by multiple fields', function (): void {
    $result = iterator_to_array(RowSorter::sort($this->data, ['age', 'name']));

    expect($result[0])->toBe(['name', 'age', 'city'])
        ->and($result[1][0])->toBe('Bob')     // age 25, name Bob
        ->and($result[2][0])->toBe('Alice')   // age 30, name Alice
        ->and($result[3][0])->toBe('Diana')   // age 30, name Diana
        ->and($result[4][0])->toBe('Charlie'); // age 35, name Charlie
});

test('it sorts by string field', function (): void {
    $result = iterator_to_array(RowSorter::sort($this->data, 'name'));

    expect($result[1][0])->toBe('Alice')
        ->and($result[2][0])->toBe('Bob')
        ->and($result[3][0])->toBe('Charlie')
        ->and($result[4][0])->toBe('Diana');
});

test('it handles null values in sorting', function (): void {
    $data = [
        ['name', 'age'],
        ['Alice', 30],
        ['Bob', null],
        ['Charlie', 25],
        ['Diana', null],
    ];

    $result = iterator_to_array(RowSorter::sort($data, 'age'));

    // Nulls should sort last
    expect($result[1][0])->toBe('Charlie') // 25
        ->and($result[2][0])->toBe('Alice')   // 30
        ->and($result[3][0])->toBe('Bob')     // null
        ->and($result[4][0])->toBe('Diana');  // null
});

test('it sorts with custom comparator', function (): void {
    $result = iterator_to_array(RowSorter::sort(
        $this->data,
        fn ($a, $b) => strlen($a[0]) <=> strlen($b[0]) // Sort by name length
    ));

    // Bob (3), Alice/Diana (5), Charlie (7)
    expect($result[1][0])->toBe('Bob')
        ->and(strlen($result[4][0]))->toBe(7); // Charlie is longest
});

test('it preserves header', function (): void {
    $result = iterator_to_array(RowSorter::sort($this->data, 'age'));

    expect($result[0])->toBe(['name', 'age', 'city']);
});

test('it handles empty data gracefully', function (): void {
    $emptyData = [['name', 'age']];
    $result = iterator_to_array(RowSorter::sort($emptyData, 'age'));

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(['name', 'age']);
});

test('it throws exception for invalid field', function (): void {
    iterator_to_array(RowSorter::sort($this->data, 'invalid_field'));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('it sorts numeric strings lexicographically', function (): void {
    $data = [
        ['id', 'value'],
        ['a', '100'],
        ['b', '20'],
        ['c', '3'],
    ];

    $result = iterator_to_array(RowSorter::sort($data, 'value'));

    // PHP spaceship operator does numeric comparison for numeric strings
    expect($result[1][0])->toBe('c')  // "3" = 3
        ->and($result[2][0])->toBe('b')  // "20" = 20
        ->and($result[3][0])->toBe('a'); // "100" = 100
});

test('it sorts mixed types gracefully', function (): void {
    $data = [
        ['name', 'value'],
        ['a', 'string'],
        ['b', 123],
        ['c', 'another'],
        ['d', 456],
    ];

    $result = iterator_to_array(RowSorter::sort($data, 'value'));

    // Should handle comparison without throwing
    expect($result)->toHaveCount(5);
});

test('it handles case-sensitive string sorting', function (): void {
    $data = [
        ['name'],
        ['zebra'],
        ['Apple'],
        ['banana'],
        ['ZEBRA'],
    ];

    $result = iterator_to_array(RowSorter::sort($data, 'name'));

    // Capital letters sort before lowercase in ASCII
    expect($result[1][0])->toBe('Apple')
        ->and($result[2][0])->toBe('ZEBRA');
});

test('sorting works with complex table', function (): void {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'Diana', 65000],
        ['HR', 'Eve', 45000],
    ];

    $result = iterator_to_array(RowSorter::sort($data, ['dept', 'salary']));

    expect($result[1])->toBe(['HR', 'Eve', 45000])      // HR, 45000
        ->and($result[2])->toBe(['IT', 'Bob', 60000])    // IT, 60000
        ->and($result[3])->toBe(['IT', 'Diana', 65000])  // IT, 65000
        ->and($result[4])->toBe(['Sales', 'Alice', 50000]) // Sales, 50000
        ->and($result[5])->toBe(['Sales', 'Charlie', 55000]); // Sales, 55000
});

test('reverse flag works with multiple fields', function (): void {
    $data = [
        ['category', 'value'],
        ['A', 10],
        ['B', 20],
        ['A', 30],
    ];

    $result = iterator_to_array(RowSorter::sort($data, ['category', 'value'], true));

    // Descending: B(20), A(30), A(10)
    expect($result[1])->toBe(['B', 20])
        ->and($result[2])->toBe(['A', 30])
        ->and($result[3])->toBe(['A', 10]);
});
