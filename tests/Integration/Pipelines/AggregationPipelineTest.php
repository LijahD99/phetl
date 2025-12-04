<?php

declare(strict_types=1);

use Phetl\Table;

test('Table aggregate groups and counts', function () {
    $sales = Table::fromArray([
        ['dept', 'employee', 'amount'],
        ['Sales', 'Alice', 1000],
        ['IT', 'Bob', 1500],
        ['Sales', 'Charlie', 1200],
        ['IT', 'David', 1800],
        ['Sales', 'Eve', 900],
    ]);

    $result = $sales->aggregate('dept', ['count' => 'count'])->toArray();

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 3],
        ['IT', 2],
    ]);
});

test('Table aggregate with custom functions', function () {
    $sales = Table::fromArray([
        ['dept', 'employee', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ]);

    $result = $sales->aggregate('dept', [
        'count' => 'count',
        'total_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);

            return array_sum(array_column($rows, $salaryIndex));
        },
        'avg_salary' => function ($rows, $header) {
            $salaryIndex = array_search('salary', $header, true);
            $salaries = array_column($rows, $salaryIndex);

            return array_sum($salaries) / count($salaries);
        },
    ])->toArray();

    expect($result)->toBe([
        ['dept', 'count', 'total_salary', 'avg_salary'],
        ['Sales', 2, 105000, 52500],
        ['IT', 2, 125000, 62500],
    ]);
});

test('Table aggregate with multiple group fields', function () {
    $sales = Table::fromArray([
        ['region', 'dept', 'sales'],
        ['East', 'Sales', 1000],
        ['East', 'Sales', 1500],
        ['West', 'Sales', 2000],
        ['East', 'IT', 500],
        ['West', 'IT', 750],
    ]);

    $result = $sales->aggregate(['region', 'dept'], ['count' => 'count'])->toArray();

    expect($result)->toBe([
        ['region', 'dept', 'count'],
        ['East', 'Sales', 2],
        ['West', 'Sales', 1],
        ['East', 'IT', 1],
        ['West', 'IT', 1],
    ]);
});

test('Table groupBy is alias for aggregate', function () {
    $data = Table::fromArray([
        ['category', 'value'],
        ['A', 10],
        ['B', 20],
        ['A', 15],
    ]);

    $result = $data->groupBy('category', ['count' => 'count'])->toArray();

    expect($result)->toBe([
        ['category', 'count'],
        ['A', 2],
        ['B', 1],
    ]);
});

test('Table countBy groups and counts', function () {
    $employees = Table::fromArray([
        ['dept', 'name', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
        ['Sales', 'Eve', 52000],
    ]);

    $result = $employees->countBy('dept')->toArray();

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 3],
        ['IT', 2],
    ]);
});

test('Table sumField without grouping', function () {
    $sales = Table::fromArray([
        ['product', 'amount'],
        ['A', 100],
        ['B', 200],
        ['C', 150],
    ]);

    $result = $sales->sumField('amount')->toArray();

    expect($result)->toBe([
        ['sum'],
        [450],
    ]);
});

test('Table sumField with grouping', function () {
    $sales = Table::fromArray([
        ['dept', 'employee', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
    ]);

    $result = $sales->sumField('salary', 'dept')->toArray();

    expect($result)->toBe([
        ['dept', 'sum'],
        ['Sales', 105000],
        ['IT', 125000],
    ]);
});

test('aggregation can be chained with other transformations', function () {
    $sales = Table::fromArray([
        ['dept', 'region', 'employee', 'amount'],
        ['Sales', 'East', 'Alice', 1000],
        ['IT', 'West', 'Bob', 1500],
        ['Sales', 'East', 'Charlie', 1200],
        ['IT', 'East', 'David', 1800],
        ['Sales', 'West', 'Eve', 900],
        ['IT', 'West', 'Frank', 2000],
    ]);

    $result = $sales
        ->whereEquals('region', 'East')
        ->aggregate('dept', [
            'count' => 'count',
            'total' => function ($rows, $header) {
                $amountIndex = array_search('amount', $header, true);

                return array_sum(array_column($rows, $amountIndex));
            },
        ])
        ->toArray();

    expect($result)->toBe([
        ['dept', 'count', 'total'],
        ['Sales', 2, 2200],
        ['IT', 1, 1800],
    ]);
});

test('aggregate then filter results', function () {
    $sales = Table::fromArray([
        ['dept', 'employee', 'salary'],
        ['Sales', 'Alice', 50000],
        ['IT', 'Bob', 60000],
        ['Sales', 'Charlie', 55000],
        ['IT', 'David', 65000],
        ['Sales', 'Eve', 52000],
        ['HR', 'Frank', 45000],
    ]);

    $result = $sales
        ->aggregate('dept', [
            'count' => 'count',
        ])
        ->whereGreaterThan('count', 1)
        ->toArray();

    expect($result)->toBe([
        ['dept', 'count'],
        ['Sales', 3],
        ['IT', 2],
    ]);
});

test('aggregate then join with another table', function () {
    $sales = Table::fromArray([
        ['dept', 'employee', 'amount'],
        ['Sales', 'Alice', 1000],
        ['IT', 'Bob', 1500],
        ['Sales', 'Charlie', 1200],
    ]);

    $budgets = Table::fromArray([
        ['dept', 'budget'],
        ['Sales', 5000],
        ['IT', 8000],
    ]);

    $result = $sales
        ->aggregate('dept', [
            'total_sales' => function ($rows, $header) {
                $amountIndex = array_search('amount', $header, true);

                return array_sum(array_column($rows, $amountIndex));
            },
        ])
        ->innerJoin($budgets, 'dept')
        ->toArray();

    expect($result)->toBe([
        ['dept', 'total_sales', 'budget'],
        ['Sales', 2200, 5000],
        ['IT', 1500, 8000],
    ]);
});

test('aggregate handles empty groups gracefully', function () {
    $data = Table::fromArray([
        ['category', 'value'],
        ['A', 10],
    ]);

    $result = $data->aggregate('category', ['count' => 'count'])->toArray();

    expect($result)->toBe([
        ['category', 'count'],
        ['A', 1],
    ]);
});
