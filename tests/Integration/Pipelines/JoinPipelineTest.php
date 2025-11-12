<?php

declare(strict_types=1);

use Phetl\Table;

test('Table innerJoin with single key', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
        [3, 'Charlie', 10],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
        [30, 'HR'],
    ]);

    $result = $employees->innerJoin($departments, 'dept_id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'dept_id', 'dept_name'],
        [1, 'Alice', 10, 'Sales'],
        [2, 'Bob', 20, 'IT'],
        [3, 'Charlie', 10, 'Sales'],
    ]);
});

test('Table innerJoin with different key names', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'department'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
    ]);

    $departments = Table::fromArray([
        ['id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
    ]);

    $result = $employees->innerJoin($departments, 'department', 'id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'department', 'dept_name'],
        [1, 'Alice', 10, 'Sales'],
        [2, 'Bob', 20, 'IT'],
    ]);
});

test('Table innerJoin with multiple keys', function () {
    $sales = Table::fromArray([
        ['region', 'product', 'sales'],
        ['East', 'A', 1000],
        ['East', 'B', 1500],
        ['West', 'A', 2000],
    ]);

    $targets = Table::fromArray([
        ['region', 'product', 'target'],
        ['East', 'A', 900],
        ['East', 'B', 1400],
        ['West', 'B', 1800],
    ]);

    $result = $sales->innerJoin($targets, ['region', 'product'])->toArray();

    expect($result)->toBe([
        ['region', 'product', 'sales', 'target'],
        ['East', 'A', 1000, 900],
        ['East', 'B', 1500, 1400],
    ]);
});

test('Table leftJoin with single key', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
        [3, 'Charlie', 30],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
    ]);

    $result = $employees->leftJoin($departments, 'dept_id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'dept_id', 'dept_name'],
        [1, 'Alice', 10, 'Sales'],
        [2, 'Bob', 20, 'IT'],
        [3, 'Charlie', 30, null],
    ]);
});

test('Table leftJoin with different key names', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'department'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
        [3, 'Charlie', 30],
    ]);

    $departments = Table::fromArray([
        ['id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
    ]);

    $result = $employees->leftJoin($departments, 'department', 'id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'department', 'dept_name'],
        [1, 'Alice', 10, 'Sales'],
        [2, 'Bob', 20, 'IT'],
        [3, 'Charlie', 30, null],
    ]);
});

test('Table leftJoin with multiple keys', function () {
    $sales = Table::fromArray([
        ['region', 'product', 'sales'],
        ['East', 'A', 1000],
        ['East', 'B', 1500],
        ['West', 'A', 2000],
    ]);

    $targets = Table::fromArray([
        ['region', 'product', 'target'],
        ['East', 'A', 900],
        ['West', 'B', 1800],
    ]);

    $result = $sales->leftJoin($targets, ['region', 'product'])->toArray();

    expect($result)->toBe([
        ['region', 'product', 'sales', 'target'],
        ['East', 'A', 1000, 900],
        ['East', 'B', 1500, null],
        ['West', 'A', 2000, null],
    ]);
});

test('Table rightJoin with single key', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
        [30, 'HR'],
    ]);

    $result = $employees->rightJoin($departments, 'dept_id')->toArray();

    expect($result)->toBe([
        ['dept_id', 'dept_name', 'emp_id', 'name'],
        [10, 'Sales', 1, 'Alice'],
        [20, 'IT', 2, 'Bob'],
        [30, 'HR', null, null],
    ]);
});

test('Table rightJoin with different key names', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'department'],
        [1, 'Alice', 10],
        [2, 'Bob', 20],
    ]);

    $departments = Table::fromArray([
        ['id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
        [30, 'HR'],
    ]);

    $result = $employees->rightJoin($departments, 'department', 'id')->toArray();

    expect($result)->toBe([
        ['id', 'dept_name', 'emp_id', 'name'],
        [10, 'Sales', 1, 'Alice'],
        [20, 'IT', 2, 'Bob'],
        [30, 'HR', null, null],
    ]);
});

test('Table rightJoin with multiple keys', function () {
    $sales = Table::fromArray([
        ['region', 'product', 'sales'],
        ['East', 'A', 1000],
        ['West', 'B', 2000],
    ]);

    $targets = Table::fromArray([
        ['region', 'product', 'target'],
        ['East', 'A', 900],
        ['East', 'B', 1400],
        ['West', 'A', 1800],
    ]);

    $result = $sales->rightJoin($targets, ['region', 'product'])->toArray();

    expect($result)->toBe([
        ['region', 'product', 'target', 'sales'],
        ['East', 'A', 900, 1000],
        ['East', 'B', 1400, null],
        ['West', 'A', 1800, null],
    ]);
});

test('Table join can be chained with other transformations', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id', 'salary'],
        [1, 'Alice', 10, 50000],
        [2, 'Bob', 20, 60000],
        [3, 'Charlie', 10, 55000],
        [4, 'David', 30, 45000],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
        [10, 'Sales'],
        [20, 'IT'],
    ]);

    $result = $employees
        ->innerJoin($departments, 'dept_id')
        ->selectColumns('name', 'dept_name', 'salary')
        ->whereGreaterThan('salary', 50000)
        ->toArray();

    expect($result)->toBe([
        ['name', 'dept_name', 'salary'],
        ['Bob', 'IT', 60000],
        ['Charlie', 'Sales', 55000],
    ]);
});

test('Table join with empty left table', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id'],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
        [10, 'Sales'],
    ]);

    $result = $employees->innerJoin($departments, 'dept_id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'dept_id', 'dept_name'],
    ]);
});

test('Table join with empty right table', function () {
    $employees = Table::fromArray([
        ['emp_id', 'name', 'dept_id'],
        [1, 'Alice', 10],
    ]);

    $departments = Table::fromArray([
        ['dept_id', 'dept_name'],
    ]);

    $result = $employees->leftJoin($departments, 'dept_id')->toArray();

    expect($result)->toBe([
        ['emp_id', 'name', 'dept_id', 'dept_name'],
        [1, 'Alice', 10, null],
    ]);
});
