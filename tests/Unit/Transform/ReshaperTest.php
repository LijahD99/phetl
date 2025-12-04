<?php

declare(strict_types=1);

use Phetl\Transform\Reshaping\Reshaper;

test('unpivot converts wide to long format', function () {
    $data = [
        ['id', 'name', 'Q1', 'Q2', 'Q3'],
        [1, 'Alice', 100, 150, 200],
        [2, 'Bob', 120, 140, 180],
    ];

    $result = iterator_to_array(Reshaper::unpivot($data, 'id'));

    expect($result)->toBe([
        ['id', 'variable', 'value'],
        [1, 'name', 'Alice'],
        [1, 'Q1', 100],
        [1, 'Q2', 150],
        [1, 'Q3', 200],
        [2, 'name', 'Bob'],
        [2, 'Q1', 120],
        [2, 'Q2', 140],
        [2, 'Q3', 180],
    ]);
});

test('unpivot with multiple id fields', function () {
    $data = [
        ['dept', 'employee', 'Jan', 'Feb'],
        ['Sales', 'Alice', 100, 150],
        ['IT', 'Bob', 120, 140],
    ];

    $result = iterator_to_array(Reshaper::unpivot($data, ['dept', 'employee']));

    expect($result)->toBe([
        ['dept', 'employee', 'variable', 'value'],
        ['Sales', 'Alice', 'Jan', 100],
        ['Sales', 'Alice', 'Feb', 150],
        ['IT', 'Bob', 'Jan', 120],
        ['IT', 'Bob', 'Feb', 140],
    ]);
});

test('unpivot with specific value fields', function () {
    $data = [
        ['id', 'name', 'Q1', 'Q2', 'Q3', 'total'],
        [1, 'Alice', 100, 150, 200, 450],
        [2, 'Bob', 120, 140, 180, 440],
    ];

    $result = iterator_to_array(Reshaper::unpivot($data, 'id', ['Q1', 'Q2', 'Q3']));

    expect($result)->toBe([
        ['id', 'variable', 'value'],
        [1, 'Q1', 100],
        [1, 'Q2', 150],
        [1, 'Q3', 200],
        [2, 'Q1', 120],
        [2, 'Q2', 140],
        [2, 'Q3', 180],
    ]);
});

test('unpivot with custom column names', function () {
    $data = [
        ['id', 'Q1', 'Q2'],
        [1, 100, 150],
        [2, 120, 140],
    ];

    $result = iterator_to_array(Reshaper::unpivot($data, 'id', null, 'quarter', 'sales'));

    expect($result)->toBe([
        ['id', 'quarter', 'sales'],
        [1, 'Q1', 100],
        [1, 'Q2', 150],
        [2, 'Q1', 120],
        [2, 'Q2', 140],
    ]);
});

test('melt is alias for unpivot', function () {
    $data = [
        ['id', 'Q1', 'Q2'],
        [1, 100, 150],
    ];

    $result = iterator_to_array(Reshaper::melt($data, 'id'));

    expect($result)->toBe([
        ['id', 'variable', 'value'],
        [1, 'Q1', 100],
        [1, 'Q2', 150],
    ]);
});

test('unpivot throws exception for invalid id field', function () {
    $data = [
        ['id', 'name'],
        [1, 'Alice'],
    ];

    iterator_to_array(Reshaper::unpivot($data, 'invalid'));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('unpivot throws exception for invalid value field', function () {
    $data = [
        ['id', 'name'],
        [1, 'Alice'],
    ];

    iterator_to_array(Reshaper::unpivot($data, 'id', 'invalid'));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot converts long to wide format', function () {
    $data = [
        ['id', 'quarter', 'sales'],
        [1, 'Q1', 100],
        [1, 'Q2', 150],
        [2, 'Q1', 120],
        [2, 'Q2', 140],
    ];

    $result = iterator_to_array(Reshaper::pivot($data, 'id', 'quarter', 'sales'));

    expect($result)->toBe([
        ['id', 'Q1', 'Q2'],
        [1, 100, 150],
        [2, 120, 140],
    ]);
});

test('pivot with multiple index fields', function () {
    $data = [
        ['dept', 'employee', 'month', 'sales'],
        ['Sales', 'Alice', 'Jan', 100],
        ['Sales', 'Alice', 'Feb', 150],
        ['IT', 'Bob', 'Jan', 120],
        ['IT', 'Bob', 'Feb', 140],
    ];

    $result = iterator_to_array(Reshaper::pivot($data, ['dept', 'employee'], 'month', 'sales'));

    expect($result)->toBe([
        ['dept', 'employee', 'Feb', 'Jan'],
        ['Sales', 'Alice', 150, 100],
        ['IT', 'Bob', 140, 120],
    ]);
});

test('pivot with aggregation for duplicates', function () {
    $data = [
        ['category', 'month', 'sales'],
        ['A', 'Jan', 100],
        ['A', 'Jan', 150], // duplicate
        ['A', 'Feb', 200],
        ['B', 'Jan', 120],
    ];

    $result = iterator_to_array(Reshaper::pivot($data, 'category', 'month', 'sales', 'sum'));

    expect($result)->toBe([
        ['category', 'Feb', 'Jan'],
        ['A', 200, 250], // 100 + 150 = 250
        ['B', null, 120],
    ]);
});

test('pivot with custom aggregation function', function () {
    $data = [
        ['id', 'type', 'value'],
        [1, 'A', 10],
        [1, 'A', 20],
        [1, 'B', 30],
    ];

    $result = iterator_to_array(Reshaper::pivot($data, 'id', 'type', 'value', fn ($vals) => max($vals)));

    expect($result)->toBe([
        ['id', 'A', 'B'],
        [1, 20, 30], // max of [10, 20] = 20
    ]);
});

test('pivot uses first value when no aggregation specified', function () {
    $data = [
        ['id', 'type', 'value'],
        [1, 'A', 10],
        [1, 'A', 20], // duplicate - will keep first (10)
    ];

    $result = iterator_to_array(Reshaper::pivot($data, 'id', 'type', 'value'));

    expect($result)->toBe([
        ['id', 'A'],
        [1, 10], // keeps first value
    ]);
});

test('pivot handles missing combinations with null', function () {
    $data = [
        ['id', 'category', 'value'],
        [1, 'A', 100],
        [1, 'B', 150],
        [2, 'A', 120],
        // Missing: [2, 'B']
    ];

    $result = iterator_to_array(Reshaper::pivot($data, 'id', 'category', 'value'));

    expect($result)->toBe([
        ['id', 'A', 'B'],
        [1, 100, 150],
        [2, 120, null], // missing combination
    ]);
});

test('pivot throws exception for invalid index field', function () {
    $data = [
        ['id', 'type', 'value'],
        [1, 'A', 10],
    ];

    iterator_to_array(Reshaper::pivot($data, 'invalid', 'type', 'value'));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot throws exception for invalid column field', function () {
    $data = [
        ['id', 'type', 'value'],
        [1, 'A', 10],
    ];

    iterator_to_array(Reshaper::pivot($data, 'id', 'invalid', 'value'));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot throws exception for invalid value field', function () {
    $data = [
        ['id', 'type', 'value'],
        [1, 'A', 10],
    ];

    iterator_to_array(Reshaper::pivot($data, 'id', 'type', 'invalid'));
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('transpose swaps rows and columns', function () {
    $data = [
        ['A', 'B', 'C'],
        [1, 2, 3],
        [4, 5, 6],
    ];

    $result = iterator_to_array(Reshaper::transpose($data));

    expect($result)->toBe([
        ['A', 1, 4],
        ['B', 2, 5],
        ['C', 3, 6],
    ]);
});

test('transpose handles uneven rows with null', function () {
    $data = [
        ['A', 'B', 'C'],
        [1, 2],
        [4, 5, 6, 7],
    ];

    $result = iterator_to_array(Reshaper::transpose($data));

    expect($result)->toBe([
        ['A', 1, 4],
        ['B', 2, 5],
        ['C', null, 6],
        [null, null, 7],
    ]);
});

test('transpose handles single row', function () {
    $data = [
        ['A', 'B', 'C'],
    ];

    $result = iterator_to_array(Reshaper::transpose($data));

    expect($result)->toBe([
        ['A'],
        ['B'],
        ['C'],
    ]);
});

test('transpose handles empty table', function () {
    $data = [];

    $result = iterator_to_array(Reshaper::transpose($data));

    expect($result)->toBe([]);
});
