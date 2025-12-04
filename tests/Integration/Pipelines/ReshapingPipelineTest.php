<?php

declare(strict_types=1);

use Phetl\Table;

test('Table unpivot converts quarterly sales to long format', function () {
    $sales = Table::fromArray([
        ['product', 'Q1', 'Q2', 'Q3', 'Q4'],
        ['Widget', 100, 150, 200, 250],
        ['Gadget', 120, 140, 180, 220],
    ]);

    $result = $sales->unpivot('product')->toArray();

    expect($result)->toBe([
        ['product', 'variable', 'value'],
        ['Widget', 'Q1', 100],
        ['Widget', 'Q2', 150],
        ['Widget', 'Q3', 200],
        ['Widget', 'Q4', 250],
        ['Gadget', 'Q1', 120],
        ['Gadget', 'Q2', 140],
        ['Gadget', 'Q3', 180],
        ['Gadget', 'Q4', 220],
    ]);
});

test('Table unpivot with custom column names', function () {
    $data = Table::fromArray([
        ['id', 'Jan', 'Feb', 'Mar'],
        [1, 100, 150, 200],
        [2, 120, 140, 180],
    ]);

    $result = $data->unpivot('id', null, 'month', 'sales')->toArray();

    expect($result)->toBe([
        ['id', 'month', 'sales'],
        [1, 'Jan', 100],
        [1, 'Feb', 150],
        [1, 'Mar', 200],
        [2, 'Jan', 120],
        [2, 'Feb', 140],
        [2, 'Mar', 180],
    ]);
});

test('Table melt is alias for unpivot', function () {
    $data = Table::fromArray([
        ['id', 'A', 'B'],
        [1, 10, 20],
    ]);

    $result = $data->melt('id')->toArray();

    expect($result)->toBe([
        ['id', 'variable', 'value'],
        [1, 'A', 10],
        [1, 'B', 20],
    ]);
});

test('Table pivot converts monthly sales to wide format', function () {
    $sales = Table::fromArray([
        ['product', 'month', 'sales'],
        ['Widget', 'Jan', 100],
        ['Widget', 'Feb', 150],
        ['Widget', 'Mar', 200],
        ['Gadget', 'Jan', 120],
        ['Gadget', 'Feb', 140],
        ['Gadget', 'Mar', 180],
    ]);

    $result = $sales->pivot('product', 'month', 'sales')->toArray();

    expect($result)->toBe([
        ['product', 'Feb', 'Jan', 'Mar'],
        ['Widget', 150, 100, 200],
        ['Gadget', 140, 120, 180],
    ]);
});

test('Table pivot with aggregation', function () {
    $sales = Table::fromArray([
        ['region', 'month', 'sales'],
        ['East', 'Jan', 100],
        ['East', 'Jan', 150], // duplicate
        ['East', 'Feb', 200],
        ['West', 'Jan', 120],
        ['West', 'Feb', 140],
    ]);

    $result = $sales->pivot('region', 'month', 'sales', 'sum')->toArray();

    expect($result)->toBe([
        ['region', 'Feb', 'Jan'],
        ['East', 200, 250], // 100 + 150
        ['West', 140, 120],
    ]);
});

test('Table transpose swaps rows and columns', function () {
    $data = Table::fromArray([
        ['Name', 'Alice', 'Bob', 'Charlie'],
        ['Age', 25, 30, 35],
        ['City', 'NYC', 'LA', 'Chicago'],
    ]);

    $result = $data->transpose()->toArray();

    expect($result)->toBe([
        ['Name', 'Age', 'City'],
        ['Alice', 25, 'NYC'],
        ['Bob', 30, 'LA'],
        ['Charlie', 35, 'Chicago'],
    ]);
});

test('unpivot then aggregate for summary', function () {
    $sales = Table::fromArray([
        ['product', 'Q1', 'Q2', 'Q3', 'Q4'],
        ['Widget', 100, 150, 200, 250],
        ['Gadget', 120, 140, 180, 220],
    ]);

    $result = $sales
        ->unpivot('product', null, 'quarter', 'sales')
        ->aggregate('product', [
            'total_sales' => function ($rows, $header) {
                $salesIndex = array_search('sales', $header, true);

                return array_sum(array_column($rows, $salesIndex));
            },
            'avg_sales' => function ($rows, $header) {
                $salesIndex = array_search('sales', $header, true);
                $sales = array_column($rows, $salesIndex);

                return array_sum($sales) / count($sales);
            },
        ])
        ->toArray();

    expect($result)->toBe([
        ['product', 'total_sales', 'avg_sales'],
        ['Widget', 700, 175],
        ['Gadget', 660, 165],
    ]);
});

test('pivot then join with another table', function () {
    $sales = Table::fromArray([
        ['product', 'month', 'amount'],
        ['Widget', 'Jan', 100],
        ['Widget', 'Feb', 150],
        ['Gadget', 'Jan', 120],
        ['Gadget', 'Feb', 140],
    ]);

    $targets = Table::fromArray([
        ['product', 'target'],
        ['Widget', 200],
        ['Gadget', 250],
    ]);

    $result = $sales
        ->pivot('product', 'month', 'amount')
        ->innerJoin($targets, 'product')
        ->toArray();

    expect($result)->toBe([
        ['product', 'Feb', 'Jan', 'target'],
        ['Widget', 150, 100, 200],
        ['Gadget', 140, 120, 250],
    ]);
});

test('unpivot with filtering', function () {
    $sales = Table::fromArray([
        ['product', 'Q1', 'Q2', 'Q3', 'Q4'],
        ['Widget', 100, 150, 200, 250],
        ['Gadget', 120, 140, 180, 220],
    ]);

    $result = $sales
        ->unpivot('product', null, 'quarter', 'sales')
        ->whereGreaterThan('sales', 150)
        ->toArray();

    expect($result)->toBe([
        ['product', 'quarter', 'sales'],
        ['Widget', 'Q3', 200],
        ['Widget', 'Q4', 250],
        ['Gadget', 'Q3', 180],
        ['Gadget', 'Q4', 220],
    ]);
});

test('pivot handles missing data', function () {
    $data = Table::fromArray([
        ['id', 'category', 'value'],
        [1, 'A', 100],
        [1, 'B', 150],
        [2, 'A', 120],
        // Note: Missing [2, 'B']
        [3, 'A', 110],
        [3, 'B', 160],
    ]);

    $result = $data->pivot('id', 'category', 'value')->toArray();

    expect($result)->toBe([
        ['id', 'A', 'B'],
        [1, 100, 150],
        [2, 120, null],
        [3, 110, 160],
    ]);
});

test('unpivot pivot round trip preserves data', function () {
    $original = Table::fromArray([
        ['id', 'A', 'B'],
        [1, 100, 150],
        [2, 120, 140],
    ]);

    $result = $original
        ->unpivot('id', null, 'category', 'value')
        ->pivot('id', 'category', 'value')
        ->toArray();

    expect($result)->toBe([
        ['id', 'A', 'B'],
        [1, 100, 150],
        [2, 120, 140],
    ]);
});

test('transpose then transpose returns to original', function () {
    $original = Table::fromArray([
        ['A', 'B', 'C'],
        [1, 2, 3],
        [4, 5, 6],
    ]);

    $result = $original
        ->transpose()
        ->transpose()
        ->toArray();

    expect($result)->toBe([
        ['A', 'B', 'C'],
        [1, 2, 3],
        [4, 5, 6],
    ]);
});

test('reshape operations can be chained', function () {
    $data = Table::fromArray([
        ['product', 'region', 'Q1', 'Q2'],
        ['Widget', 'East', 100, 150],
        ['Widget', 'West', 120, 140],
    ]);

    $result = $data
        ->unpivot(['product', 'region'], null, 'quarter', 'sales')
        ->whereEquals('region', 'East')
        ->pivot('product', 'quarter', 'sales')
        ->toArray();

    expect($result)->toBe([
        ['product', 'Q1', 'Q2'],
        ['Widget', 100, 150],
    ]);
});
