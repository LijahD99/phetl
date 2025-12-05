<?php

declare(strict_types=1);

use Phetl\Transform\Joins\Join;

test('inner join with single key', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
        [4, 35],
    ];

    [$resultHeaders, $resultData] = Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'age']);
    expect($resultData)->toBe([
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('inner join with different key names', function () {
    $leftHeaders = ['user_id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
    ];

    [$resultHeaders, $resultData] = Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'user_id', 'id');

    expect($resultHeaders)->toBe(['user_id', 'name', 'age']);
    expect($resultData)->toBe([
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('inner join with multiple keys', function () {
    $leftHeaders = ['dept', 'region', 'name'];
    $leftData = [
        ['Sales', 'East', 'Alice'],
        ['Sales', 'West', 'Bob'],
        ['IT', 'East', 'Charlie'],
    ];

    $rightHeaders = ['dept', 'region', 'budget'];
    $rightData = [
        ['Sales', 'East', 10000],
        ['Sales', 'West', 12000],
        ['IT', 'West', 8000],
    ];

    [$resultHeaders, $resultData] = Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, ['dept', 'region']);

    expect($resultHeaders)->toBe(['dept', 'region', 'name', 'budget']);
    expect($resultData)->toBe([
        ['Sales', 'East', 'Alice', 10000],
        ['Sales', 'West', 'Bob', 12000],
    ]);
});

test('inner join with no matching rows', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [2, 25],
    ];

    [$resultHeaders, $resultData] = Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'age']);
    expect($resultData)->toBe([]);
});

test('inner join with duplicate keys in right table', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'score'];
    $rightData = [
        [1, 90],
        [1, 95],
        [2, 85],
    ];

    [$resultHeaders, $resultData] = Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'score']);
    expect($resultData)->toBe([
        [1, 'Alice', 90],
        [1, 'Alice', 95],
        [2, 'Bob', 85],
    ]);
});

test('left join with single key', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
    ];

    [$resultHeaders, $resultData] = Join::left($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'age']);
    expect($resultData)->toBe([
        [1, 'Alice', 25],
        [2, 'Bob', 30],
        [3, 'Charlie', null],
    ]);
});

test('left join with different key names', function () {
    $leftHeaders = ['user_id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
    ];

    [$resultHeaders, $resultData] = Join::left($leftHeaders, $leftData, $rightHeaders, $rightData, 'user_id', 'id');

    expect($resultHeaders)->toBe(['user_id', 'name', 'age']);
    expect($resultData)->toBe([
        [1, 'Alice', 25],
        [2, 'Bob', 30],
        [3, 'Charlie', null],
    ]);
});

test('left join with multiple keys', function () {
    $leftHeaders = ['dept', 'region', 'name'];
    $leftData = [
        ['Sales', 'East', 'Alice'],
        ['Sales', 'West', 'Bob'],
        ['IT', 'East', 'Charlie'],
    ];

    $rightHeaders = ['dept', 'region', 'budget'];
    $rightData = [
        ['Sales', 'East', 10000],
        ['IT', 'West', 8000],
    ];

    [$resultHeaders, $resultData] = Join::left($leftHeaders, $leftData, $rightHeaders, $rightData, ['dept', 'region']);

    expect($resultHeaders)->toBe(['dept', 'region', 'name', 'budget']);
    expect($resultData)->toBe([
        ['Sales', 'East', 'Alice', 10000],
        ['Sales', 'West', 'Bob', null],
        ['IT', 'East', 'Charlie', null],
    ]);
});

test('left join with all matching rows', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    [$resultHeaders, $resultData] = Join::left($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'age']);
    expect($resultData)->toBe([
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('left join with duplicate keys in right table', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'score'];
    $rightData = [
        [1, 90],
        [1, 95],
    ];

    [$resultHeaders, $resultData] = Join::left($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'name', 'score']);
    expect($resultData)->toBe([
        [1, 'Alice', 90],
        [1, 'Alice', 95],
        [2, 'Bob', null],
    ]);
});

test('right join with single key', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    [$resultHeaders, $resultData] = Join::right($leftHeaders, $leftData, $rightHeaders, $rightData, 'id');

    expect($resultHeaders)->toBe(['id', 'age', 'name']);
    expect($resultData)->toBe([
        [1, 25, 'Alice'],
        [2, 30, 'Bob'],
        [3, 35, null],
    ]);
});

test('right join with different key names', function () {
    $leftHeaders = ['user_id', 'name'];
    $leftData = [
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    [$resultHeaders, $resultData] = Join::right($leftHeaders, $leftData, $rightHeaders, $rightData, 'user_id', 'id');

    expect($resultHeaders)->toBe(['id', 'age', 'name']);
    expect($resultData)->toBe([
        [1, 25, 'Alice'],
        [2, 30, 'Bob'],
        [3, 35, null],
    ]);
});

test('right join with multiple keys', function () {
    $leftHeaders = ['dept', 'region', 'name'];
    $leftData = [
        ['Sales', 'East', 'Alice'],
        ['IT', 'West', 'Charlie'],
    ];

    $rightHeaders = ['dept', 'region', 'budget'];
    $rightData = [
        ['Sales', 'East', 10000],
        ['Sales', 'West', 12000],
        ['IT', 'East', 8000],
    ];

    [$resultHeaders, $resultData] = Join::right($leftHeaders, $leftData, $rightHeaders, $rightData, ['dept', 'region']);

    expect($resultHeaders)->toBe(['dept', 'region', 'budget', 'name']);
    expect($resultData)->toBe([
        ['Sales', 'East', 10000, 'Alice'],
        ['Sales', 'West', 12000, null],
        ['IT', 'East', 8000, null],
    ]);
});

test('join throws exception for invalid left key', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
    ];

    Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'invalid_key');
})->throws(InvalidArgumentException::class, "Field 'invalid_key' not found in left table header");

test('join throws exception for invalid right key', function () {
    $leftHeaders = ['id', 'name'];
    $leftData = [
        [1, 'Alice'],
    ];

    $rightHeaders = ['id', 'age'];
    $rightData = [
        [1, 25],
    ];

    Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, 'id', 'invalid_key');
})->throws(InvalidArgumentException::class, "Field 'invalid_key' not found in right table header");

test('join throws exception for invalid left key in array', function () {
    $leftHeaders = ['dept', 'region', 'name'];
    $leftData = [
        ['Sales', 'East', 'Alice'],
    ];

    $rightHeaders = ['dept', 'region', 'budget'];
    $rightData = [
        ['Sales', 'East', 10000],
    ];

    Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, ['dept', 'invalid']);
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in left table header");

test('join throws exception for invalid right key in array', function () {
    $leftHeaders = ['dept', 'region', 'name'];
    $leftData = [
        ['Sales', 'East', 'Alice'],
    ];

    $rightHeaders = ['dept', 'region', 'budget'];
    $rightData = [
        ['Sales', 'East', 10000],
    ];

    Join::inner($leftHeaders, $leftData, $rightHeaders, $rightData, ['dept', 'region'], ['dept', 'invalid']);
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in right table header");
