# Getting Started with PHETL

## Installation

```bash
composer require windsor/phetl
```

## Quick Start

### Basic CSV Processing

```php
<?php

use Phetl\Table;

// Load, transform, and save
Table::fromCsv('input.csv')
    ->selectColumns('name', 'email', 'age')
    ->whereGreaterThan('age', 18)
    ->sortBy('name')
    ->toCsv('output.csv');
```

### Working with Arrays

```php
<?php

use Phetl\Table;

$data = [
    ['name', 'age', 'city'],
    ['Alice', 30, 'NYC'],
    ['Bob', 25, 'LA'],
    ['Charlie', 35, 'Chicago'],
];

$table = Table::fromArray($data);

// Preview data
$table->look(10);

// Filter and transform
$adults = $table
    ->whereGreaterThan('age', 21)
    ->addColumn('category', fn($row) => $row['age'] >= 30 ? 'senior' : 'junior');

// Convert to array
$result = $table->toArray();
```

### Aggregation

```php
<?php

use Phetl\Table;

Table::fromCsv('sales.csv')
    ->groupBy('department')
    ->aggregate([
        'total_sales' => fn($rows) => array_sum(array_column($rows, 'amount')),
        'avg_sale' => fn($rows) => array_sum(array_column($rows, 'amount')) / count($rows),
        'count' => 'count',
    ])
    ->sortBy('total_sales', descending: true)
    ->toCsv('summary.csv');
```

## Documentation

- [Extractors](docs/extractors.md) - Loading data
- [Transformations](docs/transformations.md) - Manipulating data
- [Loaders](docs/loaders.md) - Saving data
- [Advanced Pipelines](docs/advanced-pipelines.md) - Complex workflows

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

```bash
# Install dependencies
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
