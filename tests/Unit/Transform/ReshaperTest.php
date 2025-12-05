<?php

declare(strict_types=1);

use Phetl\Transform\Reshaping\Reshaper;

test('unpivot converts wide to long format', function () {
    $headers = ['id', 'name', 'Q1', 'Q2', 'Q3'];
    $data = [
        [1, 'Alice', 100, 150, 200],
        [2, 'Bob', 120, 140, 180],
    ];

    [$resultHeaders, $resultData] = Reshaper::unpivot($headers, $data, 'id');

    expect($resultHeaders)->toBe(['id', 'variable', 'value'])
        ->and($resultData)->toBe([
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
    $headers = ['dept', 'employee', 'Jan', 'Feb'];
    $data = [
        ['Sales', 'Alice', 100, 150],
        ['IT', 'Bob', 120, 140],
    ];

    [$resultHeaders, $resultData] = Reshaper::unpivot($headers, $data, ['dept', 'employee']);

    expect($resultHeaders)->toBe(['dept', 'employee', 'variable', 'value'])
        ->and($resultData)->toBe([
            ['Sales', 'Alice', 'Jan', 100],
            ['Sales', 'Alice', 'Feb', 150],
            ['IT', 'Bob', 'Jan', 120],
            ['IT', 'Bob', 'Feb', 140],
        ]);
});

test('unpivot with specific value fields', function () {
    $headers = ['id', 'name', 'Q1', 'Q2', 'Q3', 'total'];
    $data = [
        [1, 'Alice', 100, 150, 200, 450],
        [2, 'Bob', 120, 140, 180, 440],
    ];

    [$resultHeaders, $resultData] = Reshaper::unpivot($headers, $data, 'id', ['Q1', 'Q2', 'Q3']);

    expect($resultHeaders)->toBe(['id', 'variable', 'value'])
        ->and($resultData)->toBe([
            [1, 'Q1', 100],
            [1, 'Q2', 150],
            [1, 'Q3', 200],
            [2, 'Q1', 120],
            [2, 'Q2', 140],
            [2, 'Q3', 180],
        ]);
});

test('unpivot with custom column names', function () {
    $headers = ['id', 'Q1', 'Q2'];
    $data = [
        [1, 100, 150],
        [2, 120, 140],
    ];

    [$resultHeaders, $resultData] = Reshaper::unpivot($headers, $data, 'id', null, 'quarter', 'sales');

    expect($resultHeaders)->toBe(['id', 'quarter', 'sales'])
        ->and($resultData)->toBe([
            [1, 'Q1', 100],
            [1, 'Q2', 150],
            [2, 'Q1', 120],
            [2, 'Q2', 140],
        ]);
});

test('melt is alias for unpivot', function () {
    $headers = ['id', 'Q1', 'Q2'];
    $data = [
        [1, 100, 150],
    ];

    [$resultHeaders, $resultData] = Reshaper::melt($headers, $data, 'id');

    expect($resultHeaders)->toBe(['id', 'variable', 'value'])
        ->and($resultData)->toBe([
            [1, 'Q1', 100],
            [1, 'Q2', 150],
        ]);
});

test('unpivot throws exception for invalid id field', function () {
    $headers = ['id', 'name'];
    $data = [
        [1, 'Alice'],
    ];

    Reshaper::unpivot($headers, $data, 'invalid');
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('unpivot throws exception for invalid value field', function () {
    $headers = ['id', 'name'];
    $data = [
        [1, 'Alice'],
    ];

    Reshaper::unpivot($headers, $data, 'id', 'invalid');
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot converts long to wide format', function () {
    $headers = ['id', 'quarter', 'sales'];
    $data = [
        [1, 'Q1', 100],
        [1, 'Q2', 150],
        [2, 'Q1', 120],
        [2, 'Q2', 140],
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, 'id', 'quarter', 'sales');

    expect($resultHeaders)->toBe(['id', 'Q1', 'Q2'])
        ->and($resultData)->toBe([
            [1, 100, 150],
            [2, 120, 140],
        ]);
});

test('pivot with multiple index fields', function () {
    $headers = ['dept', 'employee', 'month', 'sales'];
    $data = [
        ['Sales', 'Alice', 'Jan', 100],
        ['Sales', 'Alice', 'Feb', 150],
        ['IT', 'Bob', 'Jan', 120],
        ['IT', 'Bob', 'Feb', 140],
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, ['dept', 'employee'], 'month', 'sales');

    expect($resultHeaders)->toBe(['dept', 'employee', 'Feb', 'Jan'])
        ->and($resultData)->toBe([
            ['Sales', 'Alice', 150, 100],
            ['IT', 'Bob', 140, 120],
        ]);
});

test('pivot with aggregation for duplicates', function () {
    $headers = ['category', 'month', 'sales'];
    $data = [
        ['A', 'Jan', 100],
        ['A', 'Jan', 150], // duplicate
        ['A', 'Feb', 200],
        ['B', 'Jan', 120],
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, 'category', 'month', 'sales', 'sum');

    expect($resultHeaders)->toBe(['category', 'Feb', 'Jan'])
        ->and($resultData)->toBe([
            ['A', 200, 250], // 100 + 150 = 250
            ['B', null, 120],
        ]);
});

test('pivot with custom aggregation function', function () {
    $headers = ['id', 'type', 'value'];
    $data = [
        [1, 'A', 10],
        [1, 'A', 20],
        [1, 'B', 30],
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, 'id', 'type', 'value', fn ($vals) => max($vals));

    expect($resultHeaders)->toBe(['id', 'A', 'B'])
        ->and($resultData)->toBe([
            [1, 20, 30], // max of [10, 20] = 20
        ]);
});

test('pivot uses first value when no aggregation specified', function () {
    $headers = ['id', 'type', 'value'];
    $data = [
        [1, 'A', 10],
        [1, 'A', 20], // duplicate - will keep first (10)
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, 'id', 'type', 'value');

    expect($resultHeaders)->toBe(['id', 'A'])
        ->and($resultData)->toBe([
            [1, 10], // keeps first value
        ]);
});

test('pivot handles missing combinations with null', function () {
    $headers = ['id', 'category', 'value'];
    $data = [
        [1, 'A', 100],
        [1, 'B', 150],
        [2, 'A', 120],
        // Missing: [2, 'B']
    ];

    [$resultHeaders, $resultData] = Reshaper::pivot($headers, $data, 'id', 'category', 'value');

    expect($resultHeaders)->toBe(['id', 'A', 'B'])
        ->and($resultData)->toBe([
            [1, 100, 150],
            [2, 120, null], // missing combination
        ]);
});

test('pivot throws exception for invalid index field', function () {
    $headers = ['id', 'type', 'value'];
    $data = [
        [1, 'A', 10],
    ];

    Reshaper::pivot($headers, $data, 'invalid', 'type', 'value');
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot throws exception for invalid column field', function () {
    $headers = ['id', 'type', 'value'];
    $data = [
        [1, 'A', 10],
    ];

    Reshaper::pivot($headers, $data, 'id', 'invalid', 'value');
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('pivot throws exception for invalid value field', function () {
    $headers = ['id', 'type', 'value'];
    $data = [
        [1, 'A', 10],
    ];

    Reshaper::pivot($headers, $data, 'id', 'type', 'invalid');
})->throws(InvalidArgumentException::class, "Field 'invalid' not found in header");

test('transpose swaps rows and columns', function () {
    $headers = ['A', 'B', 'C'];
    $data = [
        [1, 2, 3],
        [4, 5, 6],
    ];

    [$resultHeaders, $resultData] = Reshaper::transpose($headers, $data);

    // Transposed data: first row becomes headers
    // ['A', 1, 4], ['B', 2, 5], ['C', 3, 6]
    // After transpose, first element of each becomes the new header
    expect($resultHeaders)->toBe(['A', 1, 4])
        ->and($resultData)->toBe([
            ['B', 2, 5],
            ['C', 3, 6],
        ]);
});

test('transpose handles uneven rows with null', function () {
    $headers = ['A', 'B', 'C'];
    $data = [
        [1, 2],
        [4, 5, 6, 7],
    ];

    [$resultHeaders, $resultData] = Reshaper::transpose($headers, $data);

    expect($resultHeaders)->toBe(['A', 1, 4])
        ->and($resultData)->toBe([
            ['B', 2, 5],
            ['C', null, 6],
            [null, null, 7],
        ]);
});

test('transpose handles single row', function () {
    $headers = ['A', 'B', 'C'];
    $data = [];

    [$resultHeaders, $resultData] = Reshaper::transpose($headers, $data);

    expect($resultHeaders)->toBe(['A'])
        ->and($resultData)->toBe([
            ['B'],
            ['C'],
        ]);
});

test('transpose handles empty table', function () {
    $headers = [];
    $data = [];

    [$resultHeaders, $resultData] = Reshaper::transpose($headers, $data);

    expect($resultHeaders)->toBe(null)
        ->and($resultData)->toBe([]);
});
