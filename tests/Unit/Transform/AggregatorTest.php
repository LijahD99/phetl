<?php

declare(strict_types=1);

use Phetl\Transform\Aggregation\Aggregator;

test('aggregate with single group field and count', function () {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
        ['Sales', 'Eve', 52000],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'dept', ['count' => 'count']));

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 3],
        ['IT', 2],
    ]);
});

test('aggregate with multiple group fields', function () {
    $data = [
        ['region', 'dept', 'sales'],
        ['East', 'Sales', 1000],
        ['East', 'Sales', 1500],
        ['West', 'Sales', 2000],
        ['East', 'IT', 500],
        ['West', 'IT', 750],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, ['region', 'dept'], ['count' => 'count']));

    expect($result)->toBe([
        ['region', 'dept', 'count'],
        ['East', 'Sales', 2],
        ['West', 'Sales', 1],
        ['East', 'IT', 1],
        ['West', 'IT', 1],
    ]);
});

test('aggregate with custom aggregation function', function () {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'dept', [
        'total_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);

            return array_sum(array_column($rows, $salaryIndex));
        },
        'avg_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);
            $salaries = array_column($rows, $salaryIndex);

            return array_sum($salaries) / count($salaries);
        },
    ]));

    expect($result)->toBe([
        ['dept', 'total_salary', 'avg_salary'],
        ['Sales', 105000, 52500],
        ['IT', 125000, 62500],
    ]);
});

test('aggregate with multiple aggregations', function () {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'dept', [
        'count' => 'count',
        'first' => 'first',
        'last' => 'last',
    ]));

    expect($result[0])->toBe(['dept', 'count', 'first', 'last']);
    expect($result[1][0])->toBe('Sales');
    expect($result[1][1])->toBe(2);
    expect($result[2][0])->toBe('IT');
    expect($result[2][1])->toBe(1);
});

test('aggregate with empty aggregations throws exception', function () {
    $data = [
        ['dept', 'name'],
        ['Sales', 'Alice'],
    ];

    iterator_to_array(Aggregator::aggregate($data, 'dept', []));
})->throws(InvalidArgumentException::class, 'At least one aggregation must be specified');

test('aggregate with invalid field throws exception', function () {
    $data = [
        ['dept', 'name'],
        ['Sales', 'Alice'],
    ];

    iterator_to_array(Aggregator::aggregate($data, 'invalid_field', ['count' => 'count']));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('aggregate with unknown string aggregation throws exception', function () {
    $data = [
        ['dept', 'name'],
        ['Sales', 'Alice'],
    ];

    iterator_to_array(Aggregator::aggregate($data, 'dept', ['result' => 'unknown_func']));
})->throws(InvalidArgumentException::class, 'Unknown aggregation function: unknown_func');

test('count with no grouping returns total', function () {
    $data = [
        ['name', 'age'],
        ['Alice', 25],
        ['Bob', 30],
        ['Charlie', 35],
    ];

    $result = iterator_to_array(Aggregator::count($data));

    expect($result)->toBe([
        ['count'],
        [3],
    ]);
});

test('count with grouping returns counts per group', function () {
    $data = [
        ['dept', 'name'],
        ['Sales', 'Alice'],
        ['IT', 'Bob'],
        ['Sales', 'Charlie'],
        ['Sales', 'David'],
        ['IT', 'Eve'],
    ];

    $result = iterator_to_array(Aggregator::count($data, 'dept'));

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 3],
        ['IT', 2],
    ]);
});

test('sum with no grouping returns total', function () {
    $data = [
        ['name', 'amount'],
        ['Alice', 100],
        ['Bob', 200],
        ['Charlie', 150],
    ];

    $result = iterator_to_array(Aggregator::sum($data, 'amount'));

    expect($result)->toBe([
        ['sum'],
        [450],
    ]);
});

test('sum with grouping returns sums per group', function () {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ];

    $result = iterator_to_array(Aggregator::sum($data, 'salary', 'dept'));

    expect($result)->toBe([
        ['dept', 'sum'],
        ['Sales', 105000],
        ['IT', 125000],
    ]);
});

test('sum with invalid field throws exception', function () {
    $data = [
        ['name', 'amount'],
        ['Alice', 100],
    ];

    iterator_to_array(Aggregator::sum($data, 'invalid_field'));
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('aggregate handles null values', function () {
    $data = [
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['Sales', 'Bob', null],
        ['IT', 'Charlie', 60000],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'dept', [
        'count' => 'count',
    ]));

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 2],
        ['IT', 1],
    ]);
});

test('aggregate with single group field as string', function () {
    $data = [
        ['category', 'value'],
        ['A', 10],
        ['B', 20],
        ['A', 15],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'category', ['count' => 'count']));

    expect($result)->toBe([
        ['category', 'count'],
        ['A', 2],
        ['B', 1],
    ]);
});

test('aggregate preserves group order by first occurrence', function () {
    $data = [
        ['dept', 'name'],
        ['Sales', 'Alice'],
        ['IT', 'Bob'],
        ['HR', 'Charlie'],
        ['Sales', 'David'],
        ['IT', 'Eve'],
    ];

    $result = iterator_to_array(Aggregator::aggregate($data, 'dept', ['count' => 'count']));

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 2],
        ['IT', 2],
        ['HR', 1],
    ]);
});
