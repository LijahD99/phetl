# Getting Started with PHETL

PHETL is a PHP library for Extract, Transform, and Load (ETL) operations on tabular data. This guide will help you get started quickly.

## Installation

```bash
composer require phetl/phetl
```

### Requirements

- PHP 8.1 or higher
- `ext-json` (usually enabled by default)
- `phpoffice/phpspreadsheet` (optional, for Excel support)

## Core Concepts

### Tables

The `Table` class is the central component of PHETL. A table consists of:
- **Headers** - Column names as an array of strings
- **Data rows** - Arrays of values, one per row

```php
use Phetl\Table;

// Headers are the first row, data follows
$table = Table::fromArray([
    ['name', 'age', 'city'],      // Header row
    ['Alice', 30, 'New York'],    // Data row 1
    ['Bob', 25, 'Los Angeles'],   // Data row 2
]);

// Access headers and row count
echo implode(', ', $table->header()); // name, age, city
echo $table->count();                  // 2 (data rows only)
```

### Method Chaining

All transformation methods return a new `Table` instance, allowing fluent chaining:

```php
$result = Table::fromCsv('input.csv')
    ->whereGreaterThan('age', 18)
    ->selectColumns('name', 'email')
    ->sortBy('name')
    ->toCsv('output.csv');
```

### LoadResult

All loader methods (`toCsv()`, `toJson()`, `toDatabase()`, `toExcel()`) return a `LoadResult` object for observability:

```php
$result = $table->toCsv('output.csv');

if ($result->success()) {
    echo "Exported {$result->rowCount()} rows";
} else {
    foreach ($result->errors() as $error) {
        echo "Error: $error\n";
    }
}
```

## Data Sources (Extractors)

### From Arrays

```php
// Header as first row (traditional)
$table = Table::fromArray([
    ['name', 'age'],
    ['Alice', 30],
    ['Bob', 25],
]);

// Explicit headers (recommended)
$table = Table::fromArray(
    [['Alice', 30], ['Bob', 25]],
    ['name', 'age']
);
```

### From CSV Files

```php
// Basic usage
$table = Table::fromCsv('data.csv');

// With options
$table = Table::fromCsv(
    'data.csv',
    delimiter: ';',      // Column separator
    enclosure: '"',      // Quote character
    escape: '\\',        // Escape character
    hasHeaders: true     // First row is headers
);

// CSV without headers
$table = Table::fromCsv('data.csv', hasHeaders: false);
// Creates headers: col_0, col_1, col_2, ...
```

### From JSON Files

```php
// Array of objects
$table = Table::fromJson('users.json');
// Expects: [{"name": "Alice", "age": 30}, {"name": "Bob", "age": 25}]
```

### From Excel Files

```php
// Default sheet
$table = Table::fromExcel('data.xlsx');

// Specific sheet by name
$table = Table::fromExcel('data.xlsx', 'Sheet2');

// Specific sheet by index (0-based)
$table = Table::fromExcel('data.xlsx', 1);
```

### From Database

```php
$pdo = new PDO('mysql:host=localhost;dbname=mydb', 'user', 'pass');

// Simple query
$table = Table::fromDatabase($pdo, 'SELECT * FROM users');

// With parameters
$table = Table::fromDatabase(
    $pdo,
    'SELECT * FROM users WHERE status = ?',
    ['active']
);
```

### From REST APIs

```php
$table = Table::fromRestApi('https://api.example.com/users', [
    'auth' => [
        'type' => 'bearer',
        'token' => 'your-api-token',
    ],
    'pagination' => [
        'type' => 'offset',
        'page_size' => 100,
    ],
    'mapping' => [
        'data_path' => 'results.users',
    ],
]);
```

## Basic Transformations

### Filtering Rows

```php
// Custom predicate
$table->filter(fn($row) => $row['age'] > 21);

// Comparison filters
$table->whereEquals('status', 'active');
$table->whereNotEquals('type', 'deleted');
$table->whereGreaterThan('price', 100);
$table->whereLessThan('quantity', 10);
$table->whereGreaterThanOrEqual('rating', 4.0);
$table->whereLessThanOrEqual('discount', 0.5);

// Collection filters
$table->whereIn('category', ['electronics', 'books']);
$table->whereNotIn('status', ['deleted', 'archived']);

// Null checks
$table->whereNull('deleted_at');
$table->whereNotNull('email');

// Boolean checks
$table->whereTrue('is_active');
$table->whereFalse('is_spam');

// String contains
$table->whereContains('name', 'Smith');
```

### Selecting and Removing Columns

```php
// Keep only specific columns
$table->selectColumns('name', 'email', 'created_at');

// Remove specific columns
$table->removeColumns('password', 'internal_id');

// Rename columns
$table->renameColumns([
    'fname' => 'first_name',
    'lname' => 'last_name',
]);
```

### Adding Columns

```php
// Static value
$table->addColumn('country', 'USA');

// Computed value
$table->addColumn('full_name', fn($row) => 
    $row['first_name'] . ' ' . $row['last_name']
);

// Add row numbers
$table->addRowNumbers('row_num');
```

### Sorting

```php
// Single field ascending
$table->sortBy('name');

// Single field descending
$table->sortByDesc('created_at');

// Multiple fields
$table->sortBy('category', 'price');

// Custom comparator
$table->sort(fn($a, $b) => strlen($a['name']) <=> strlen($b['name']));
```

### Row Selection

```php
$table->head(10);        // First 10 rows
$table->tail(10);        // Last 10 rows
$table->skip(5);         // Skip first 5 rows
$table->slice(10, 20);   // Rows 10-19
```

## Data Destinations (Loaders)

### To Array

```php
$array = $table->toArray();
// Returns: [['name', 'age'], ['Alice', 30], ['Bob', 25]]
```

### To CSV

```php
$result = $table->toCsv('output.csv');
$result = $table->toCsv('output.csv', delimiter: ';');
```

### To JSON

```php
$result = $table->toJson('output.json');
$result = $table->toJson('output.json', prettyPrint: true);
```

### To Excel

```php
$result = $table->toExcel('output.xlsx');
$result = $table->toExcel('output.xlsx', 'Results');
```

### To Database

```php
$result = $table->toDatabase($pdo, 'users');
```

## Complete Example

```php
<?php

use Phetl\Table;

// Extract from CSV
$orders = Table::fromCsv('orders.csv');

// Transform
$summary = $orders
    // Filter to recent, completed orders
    ->whereEquals('status', 'completed')
    ->whereGreaterThan('amount', 0)
    
    // Select relevant columns
    ->selectColumns('customer_id', 'product', 'amount', 'created_at')
    
    // Add computed columns
    ->addColumn('year', fn($row) => date('Y', strtotime($row['created_at'])))
    ->addColumn('month', fn($row) => date('m', strtotime($row['created_at'])))
    
    // Aggregate by customer
    ->aggregate(['customer_id', 'year'], [
        'total_orders' => 'count',
        'total_amount' => fn($rows) => array_sum(array_column($rows, 'amount')),
        'avg_order' => fn($rows) => 
            array_sum(array_column($rows, 'amount')) / count($rows),
    ])
    
    // Sort by total amount descending
    ->sortByDesc('total_amount');

// Load to multiple formats
$result = $summary->toCsv('customer_summary.csv');
echo "Exported {$result->rowCount()} customer records\n";

$summary->toJson('customer_summary.json', prettyPrint: true);
$summary->toExcel('customer_summary.xlsx', 'Summary');
```

## Next Steps

- See [Examples](../examples/) for more complete code examples
- Read the [API Reference](api/) for detailed method documentation
- Check [DIFFERENCES_FROM_PETL.md](DIFFERENCES_FROM_PETL.md) if migrating from Python petl
