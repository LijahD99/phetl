<?php

declare(strict_types=1);

use Phetl\Transform\Joins\Join;

test('inner join with single key', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
        [4, 35],
    ];

    $result = iterator_to_array(Join::inner($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'age'],
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('inner join with different key names', function () {
    $left = [
        ['user_id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
    ];

    $result = iterator_to_array(Join::inner($left, $right, 'user_id', 'id'));

    expect($result)->toBe([
        ['user_id', 'name', 'age'],
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('inner join with multiple keys', function () {
    $left = [
        ['dept', 'region', 'name'],
        ['Sales', 'East', 'Alice'],
        ['Sales', 'West', 'Bob'],
        ['IT', 'East', 'Charlie'],
    ];

    $right = [
        ['dept', 'region', 'budget'],
        ['Sales', 'East', 10000],
        ['Sales', 'West', 12000],
        ['IT', 'West', 8000],
    ];

    $result = iterator_to_array(Join::inner($left, $right, ['dept', 'region']));

    expect($result)->toBe([
        ['dept', 'region', 'name', 'budget'],
        ['Sales', 'East', 'Alice', 10000],
        ['Sales', 'West', 'Bob', 12000],
    ]);
});

test('inner join with no matching rows', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
    ];

    $right = [
        ['id', 'age'],
        [2, 25],
    ];

    $result = iterator_to_array(Join::inner($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'age'],
    ]);
});

test('inner join with duplicate keys in right table', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'score'],
        [1, 90],
        [1, 95],
        [2, 85],
    ];

    $result = iterator_to_array(Join::inner($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'score'],
        [1, 'Alice', 90],
        [1, 'Alice', 95],
        [2, 'Bob', 85],
    ]);
});

test('left join with single key', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
    ];

    $result = iterator_to_array(Join::left($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'age'],
        [1, 'Alice', 25],
        [2, 'Bob', 30],
        [3, 'Charlie', null],
    ]);
});

test('left join with different key names', function () {
    $left = [
        ['user_id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
        [3, 'Charlie'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
    ];

    $result = iterator_to_array(Join::left($left, $right, 'user_id', 'id'));

    expect($result)->toBe([
        ['user_id', 'name', 'age'],
        [1, 'Alice', 25],
        [2, 'Bob', 30],
        [3, 'Charlie', null],
    ]);
});

test('left join with multiple keys', function () {
    $left = [
        ['dept', 'region', 'name'],
        ['Sales', 'East', 'Alice'],
        ['Sales', 'West', 'Bob'],
        ['IT', 'East', 'Charlie'],
    ];

    $right = [
        ['dept', 'region', 'budget'],
        ['Sales', 'East', 10000],
        ['IT', 'West', 8000],
    ];

    $result = iterator_to_array(Join::left($left, $right, ['dept', 'region']));

    expect($result)->toBe([
        ['dept', 'region', 'name', 'budget'],
        ['Sales', 'East', 'Alice', 10000],
        ['Sales', 'West', 'Bob', null],
        ['IT', 'East', 'Charlie', null],
    ]);
});

test('left join with all matching rows', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    $result = iterator_to_array(Join::left($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'age'],
        [1, 'Alice', 25],
        [2, 'Bob', 30],
    ]);
});

test('left join with duplicate keys in right table', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'score'],
        [1, 90],
        [1, 95],
    ];

    $result = iterator_to_array(Join::left($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'name', 'score'],
        [1, 'Alice', 90],
        [1, 'Alice', 95],
        [2, 'Bob', null],
    ]);
});

test('right join with single key', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    $result = iterator_to_array(Join::right($left, $right, 'id'));

    expect($result)->toBe([
        ['id', 'age', 'name'],
        [1, 25, 'Alice'],
        [2, 30, 'Bob'],
        [3, 35, null],
    ]);
});

test('right join with different key names', function () {
    $left = [
        ['user_id', 'name'],
        [1, 'Alice'],
        [2, 'Bob'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
        [2, 30],
        [3, 35],
    ];

    $result = iterator_to_array(Join::right($left, $right, 'user_id', 'id'));

    expect($result)->toBe([
        ['id', 'age', 'name'],
        [1, 25, 'Alice'],
        [2, 30, 'Bob'],
        [3, 35, null],
    ]);
});

test('right join with multiple keys', function () {
    $left = [
        ['dept', 'region', 'name'],
        ['Sales', 'East', 'Alice'],
        ['IT', 'West', 'Charlie'],
    ];

    $right = [
        ['dept', 'region', 'budget'],
        ['Sales', 'East', 10000],
        ['Sales', 'West', 12000],
        ['IT', 'East', 8000],
    ];

    $result = iterator_to_array(Join::right($left, $right, ['dept', 'region']));

    expect($result)->toBe([
        ['dept', 'region', 'budget', 'name'],
        ['Sales', 'East', 10000, 'Alice'],
        ['Sales', 'West', 12000, null],
        ['IT', 'East', 8000, null],
    ]);
});

test('join throws exception for invalid left key', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
    ];

    iterator_to_array(Join::inner($left, $right, 'invalid_key'));
})->throws(InvalidArgumentException::class, "Field 'invalid_key' not found in left table header");

test('join throws exception for invalid right key', function () {
    $left = [
        ['id', 'name'],
        [1, 'Alice'],
    ];

    $right = [
        ['id', 'age'],
        [1, 25],
    ];

    iterator_to_array(Join::inner($left, $right, 'id', 'invalid_key'));
})->throws(InvalidArgumentException::class, "Field 'invalid_key' not found in right table header");

test('join throws exception for invalid left key in array', function () {
    $left = [
        ['dept', 'region', 'name'],
        ['Sales', 'East', 'Alice'],
    ];

    $right = [
        ['dept', 'region', 'budget'],
        ['Sales', 'East', 10000],
    ];

    iterator_to_array(Join::inner($left, $right, ['dept', 'invalid']));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in left table header");

test('join throws exception for invalid right key in array', function () {
    $left = [
        ['dept', 'region', 'name'],
        ['Sales', 'East', 'Alice'],
    ];

    $right = [
        ['dept', 'region', 'budget'],
        ['Sales', 'East', 10000],
    ];

    iterator_to_array(Join::inner($left, $right, ['dept', 'region'], ['dept', 'invalid']));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in right table header");
