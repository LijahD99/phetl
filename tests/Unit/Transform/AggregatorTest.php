<?php

declare(strict_types=1);

use Phetl\Transform\Aggregation\Aggregator;

test('aggregate with single group field and count', function () {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
        ['Sales', 'Eve', 52000],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'dept', ['count' => 'count']);

    expect($resultHeaders)->toBe(['dept', 'count'])
        ->and($resultData)->toBe([
            ['Sales', 3],
            ['IT', 2],
        ]);
});

test('aggregate with multiple group fields', function () {
    $headers = ['region', 'dept', 'sales'];
    $data = [
        ['East', 'Sales', 1000],
        ['East', 'Sales', 1500],
        ['West', 'Sales', 2000],
        ['East', 'IT', 500],
        ['West', 'IT', 750],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, ['region', 'dept'], ['count' => 'count']);

    expect($resultHeaders)->toBe(['region', 'dept', 'count'])
        ->and($resultData)->toBe([
            ['East', 'Sales', 2],
            ['West', 'Sales', 1],
            ['East', 'IT', 1],
            ['West', 'IT', 1],
        ]);
});

test('aggregate with custom aggregation function', function () {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'dept', [
        'total_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);

            return array_sum(array_column($rows, $salaryIndex));
        },
        'avg_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);
            $salaries = array_column($rows, $salaryIndex);

            return array_sum($salaries) / count($salaries);
        },
    ]);

    expect($resultHeaders)->toBe(['dept', 'total_salary', 'avg_salary'])
        ->and($resultData)->toBe([
            ['Sales', 105000, 52500],
            ['IT', 125000, 62500],
        ]);
});

test('aggregate with multiple aggregations', function () {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'dept', [
        'count' => 'count',
        'first' => 'first',
        'last' => 'last',
    ]);

    expect($resultHeaders)->toBe(['dept', 'count', 'first', 'last']);
    expect($resultData[0][0])->toBe('Sales');
    expect($resultData[0][1])->toBe(2);
    expect($resultData[1][0])->toBe('IT');
    expect($resultData[1][1])->toBe(1);
});

test('aggregate with empty aggregations throws exception', function () {
    $headers = ['dept', 'name'];
    $data = [
        ['Sales', 'Alice'],
    ];

    Aggregator::aggregate($headers, $data, 'dept', []);
})->throws(InvalidArgumentException::class, 'At least one aggregation must be specified');

test('aggregate with invalid field throws exception', function () {
    $headers = ['dept', 'name'];
    $data = [
        ['Sales', 'Alice'],
    ];

    Aggregator::aggregate($headers, $data, 'invalid_field', ['count' => 'count']);
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('aggregate with unknown string aggregation throws exception', function () {
    $headers = ['dept', 'name'];
    $data = [
        ['Sales', 'Alice'],
    ];

    Aggregator::aggregate($headers, $data, 'dept', ['result' => 'unknown_func']);
})->throws(InvalidArgumentException::class, 'Unknown aggregation function: unknown_func');

test('count with no grouping returns total', function () {
    $headers = ['name', 'age'];
    $data = [
        ['Alice', 25],
        ['Bob', 30],
        ['Charlie', 35],
    ];

    [$resultHeaders, $resultData] = Aggregator::count($headers, $data);

    expect($resultHeaders)->toBe(['count'])
        ->and($resultData)->toBe([[3]]);
});

test('count with grouping returns counts per group', function () {
    $headers = ['dept', 'name'];
    $data = [
        ['Sales', 'Alice'],
        ['IT', 'Bob'],
        ['Sales', 'Charlie'],
        ['Sales', 'David'],
        ['IT', 'Eve'],
    ];

    [$resultHeaders, $resultData] = Aggregator::count($headers, $data, 'dept');

    expect($resultHeaders)->toBe(['dept', 'count'])
        ->and($resultData)->toBe([
            ['Sales', 3],
            ['IT', 2],
        ]);
});

test('sum with no grouping returns total', function () {
    $headers = ['name', 'amount'];
    $data = [
        ['Alice', 100],
        ['Bob', 200],
        ['Charlie', 150],
    ];

    [$resultHeaders, $resultData] = Aggregator::sum($headers, $data, 'amount');

    expect($resultHeaders)->toBe(['sum'])
        ->and($resultData)->toBe([[450]]);
});

test('sum with grouping returns sums per group', function () {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ];

    [$resultHeaders, $resultData] = Aggregator::sum($headers, $data, 'salary', 'dept');

    expect($resultHeaders)->toBe(['dept', 'sum'])
        ->and($resultData)->toBe([
            ['Sales', 105000],
            ['IT', 125000],
        ]);
});

test('sum with invalid field throws exception', function () {
    $headers = ['name', 'amount'];
    $data = [
        ['Alice', 100],
    ];

    Aggregator::sum($headers, $data, 'invalid_field');
})->throws(InvalidArgumentException::class, "Field 'invalid_field' not found in header");

test('aggregate handles null values', function () {
    $headers = ['dept', 'name', 'salary'];
    $data = [
        ['Sales', 'Alice', 50000],
        ['Sales', 'Bob', null],
        ['IT', 'Charlie', 60000],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'dept', [
        'count' => 'count',
    ]);

    expect($resultHeaders)->toBe(['dept', 'count'])
        ->and($resultData)->toBe([
            ['Sales', 2],
            ['IT', 1],
        ]);
});

test('aggregate with single group field as string', function () {
    $headers = ['category', 'value'];
    $data = [
        ['A', 10],
        ['B', 20],
        ['A', 15],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'category', ['count' => 'count']);

    expect($resultHeaders)->toBe(['category', 'count'])
        ->and($resultData)->toBe([
            ['A', 2],
            ['B', 1],
        ]);
});

test('aggregate preserves group order by first occurrence', function () {
    $headers = ['dept', 'name'];
    $data = [
        ['Sales', 'Alice'],
        ['IT', 'Bob'],
        ['HR', 'Charlie'],
        ['Sales', 'David'],
        ['IT', 'Eve'],
    ];

    [$resultHeaders, $resultData] = Aggregator::aggregate($headers, $data, 'dept', ['count' => 'count']);

    expect($resultHeaders)->toBe(['dept', 'count'])
        ->and($resultData)->toBe([
            ['Sales', 2],
            ['IT', 2],
            ['HR', 1],
        ]);
});
