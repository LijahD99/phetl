# PHETL - PHP ETL Library

[![PHP 8.1+](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Tests](https://img.shields.io/badge/tests-589%20passing-brightgreen.svg)](#)
[![PHPStan](https://img.shields.io/badge/PHPStan-max%20level-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12-compliant-brightgreen.svg)](https://www.php-fig.org/psr/psr-12/)

A modern PHP library for Extract, Transform, and Load (ETL) operations on tabular data. Inspired by Python's [petl](https://petl.readthedocs.io/) library, PHETL brings powerful data transformation capabilities to PHP with a fluent, chainable API.

## Features

- **Fluent API** - Chain transformations together with readable method calls
- **Multiple Data Sources** - CSV, JSON, Excel, Database, REST APIs
- **Rich Transformations** - Filter, sort, join, aggregate, pivot, and more
- **Memory Efficient** - Uses PHP generators for lazy evaluation
- **Type Safe** - Strict PHP 8.1+ types with PHPStan max level
- **Production Ready** - Load operations return `LoadResult` with row counts and errors
- **Well Tested** - 589 tests with comprehensive coverage

## Installation

```bash
composer require phetl/phetl
```

## Quick Start

```php
<?php

use Phetl\Table;

// Load, transform, and save data in one pipeline
Table::fromCsv('customers.csv')
    ->whereEquals('status', 'active')
    ->selectColumns('name', 'email', 'created_at')
    ->sortBy('created_at')
    ->toCsv('active_customers.csv');

// Get transformation results
$result = Table::fromCsv('orders.csv')
    ->whereGreaterThan('total', 100)
    ->toJson('large_orders.json');

echo "Exported {$result->rowCount()} orders";
```

## Documentation

- [Getting Started Guide](docs/getting-started.md) - Installation and basic usage
- [API Reference](docs/api/) - Complete method documentation
- [Examples](examples/) - Runnable code examples
- [Migration from petl](docs/DIFFERENCES_FROM_PETL.md) - For Python petl users

## Examples

### Basic Data Processing

```php
use Phetl\Table;

// Create from array
$table = Table::fromArray([
    ['name', 'age', 'city'],
    ['Alice', 30, 'New York'],
    ['Bob', 25, 'Los Angeles'],
    ['Charlie', 35, 'Chicago'],
]);

// Filter and transform
$result = $table
    ->whereGreaterThan('age', 25)
    ->addColumn('category', fn($row) => $row['age'] >= 30 ? 'senior' : 'junior')
    ->sortByDesc('age')
    ->toArray();
```

### Working with CSV Files

```php
// Read, transform, write
Table::fromCsv('input.csv')
    ->renameColumns(['old_name' => 'new_name'])
    ->removeColumns('temp_column')
    ->whereNotNull('email')
    ->toCsv('output.csv');
```

### Database Operations

```php
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');

// Extract from database
$users = Table::fromDatabase($pdo, 'SELECT * FROM users WHERE active = 1');

// Transform and load to another table
$users
    ->selectColumns('id', 'name', 'email')
    ->addColumn('exported_at', fn() => date('Y-m-d H:i:s'))
    ->toDatabase($pdo, 'user_exports');
```

### REST API Extraction

```php
$data = Table::fromRestApi('https://api.example.com/users', [
    'auth' => ['type' => 'bearer', 'token' => $apiToken],
    'pagination' => ['type' => 'offset', 'page_size' => 100],
    'mapping' => [
        'data_path' => 'results.users',
        'fields' => [
            'id' => 'user_id',
            'name' => 'profile.full_name',
        ],
    ],
]);
```

### Aggregation and Grouping

```php
Table::fromCsv('sales.csv')
    ->aggregate('department', [
        'total_sales' => fn($rows) => array_sum(array_column($rows, 'amount')),
        'avg_sale' => fn($rows) => array_sum(array_column($rows, 'amount')) / count($rows),
        'count' => 'count',
    ])
    ->sortByDesc('total_sales')
    ->toJson('department_summary.json', prettyPrint: true);
```

### Joining Tables

```php
$orders = Table::fromCsv('orders.csv');
$customers = Table::fromCsv('customers.csv');

$enriched = $orders
    ->leftJoin($customers, 'customer_id')
    ->selectColumns('order_id', 'customer_name', 'total', 'order_date')
    ->toCsv('enriched_orders.csv');
```

### Data Validation

```php
$result = Table::fromCsv('users.csv')
    ->validate([
        'email' => ['required', 'email'],
        'age' => ['required', 'integer', ['min', 18]],
        'status' => ['required', ['in', ['active', 'inactive']]],
    ]);

if (!$result['valid']) {
    foreach ($result['errors'] as $error) {
        echo "Row {$error['row']}: {$error['message']}\n";
    }
}
```

## Available Transformations

### Row Operations
- `head(n)`, `tail(n)`, `slice(start, stop)`, `skip(n)`
- `filter(fn)`, `whereEquals()`, `whereGreaterThan()`, `whereIn()`, etc.
- `sort()`, `sortBy()`, `sortByDesc()`
- `distinct()`, `unique()`, `duplicates()`

### Column Operations
- `selectColumns()`, `removeColumns()`
- `renameColumns()`, `addColumn()`
- `addRowNumbers()`

### Value Operations
- `convert()`, `convertMultiple()`
- `replace()`, `replaceMap()`, `replaceAll()`
- `upper()`, `lower()`, `trim()`
- `when()`, `coalesce()`, `ifNull()`, `case()`

### Combining Tables
- `concat()`, `union()`, `merge()`
- `innerJoin()`, `leftJoin()`, `rightJoin()`

### Reshaping
- `pivot()`, `unpivot()` / `melt()`
- `transpose()`
- `aggregate()`, `groupBy()`, `countBy()`

### Validation
- `validate()`, `validateOrFail()`
- `filterValid()`, `filterInvalid()`

## Extractors (Data Sources)

| Source | Method | Description |
|--------|--------|-------------|
| Array | `fromArray($data)` | PHP arrays |
| CSV | `fromCsv($path)` | CSV files |
| JSON | `fromJson($path)` | JSON files |
| Excel | `fromExcel($path, $sheet)` | Excel .xlsx files |
| Database | `fromDatabase($pdo, $query)` | PDO queries |
| REST API | `fromRestApi($url, $config)` | RESTful endpoints |

## Loaders (Data Destinations)

| Destination | Method | Returns |
|-------------|--------|---------|
| Array | `toArray()` | Array with header + rows |
| CSV | `toCsv($path)` | `LoadResult` |
| JSON | `toJson($path)` | `LoadResult` |
| Excel | `toExcel($path, $sheet)` | `LoadResult` |
| Database | `toDatabase($pdo, $table)` | `LoadResult` |

All loaders return a `LoadResult` object with:
- `rowCount()` - Number of rows written
- `success()` - Whether the operation succeeded
- `errors()` - Array of error messages
- `warnings()` - Array of warning messages

## Requirements

- PHP 8.1 or higher
- `ext-json` for JSON operations
- `phpoffice/phpspreadsheet` for Excel operations (optional)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

```bash
# Clone and install
git clone https://github.com/LijahD99/phetl.git
cd phetl
composer install

# Run tests
composer test

# Check code style
composer cs:check

# Run static analysis
composer phpstan

# All quality checks
composer quality
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Acknowledgments

PHETL is inspired by and aims to be compatible with Python's excellent [petl](https://petl.readthedocs.io/) library. For users familiar with petl, see our [migration guide](docs/DIFFERENCES_FROM_PETL.md).
