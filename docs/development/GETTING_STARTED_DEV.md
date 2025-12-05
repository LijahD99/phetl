# Next Steps - Development Guide

## 🎯 Immediate Next Steps

### 1. Install Dependencies
```bash
cd c:\Code\Web\Windsor\phetl
composer install
```

### 2. Initialize Git Repository
```bash
git init
git add .
git commit -m "Initial project scaffolding - PSR-12, SOLID, TDD"
```

### 3. Verify Quality Tools
```bash
# Check PHP CS Fixer
composer cs:check

# Run PHPStan (will initially have nothing to analyze)
composer phpstan

# Run tests (will initially pass with no tests)
composer test
```

## 📋 Phase 1: Core Foundation (TDD)

### Step 1: Define Core Interfaces
Following TDD, start with interfaces:

1. **TableInterface** (`src/Contracts/TableInterface.php`)
   - Define the contract for what a Table can do
   - Methods: `getIterator()`, `getHeaders()`, etc.

2. **ExtractorInterface** (`src/Contracts/ExtractorInterface.php`)
   - Contract for data extraction
   - Method: `extract(): iterable`

3. **LoaderInterface** (`src/Contracts/LoaderInterface.php`)
   - Contract for data loading
   - Method: `load(iterable $data): void`

### Step 2: First Test - ArrayExtractor (TDD)
```php
// tests/Unit/Extract/Extractors/ArrayExtractorTest.php
<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use PHPUnit\Framework\TestCase;
use Phetl\Extract\Extractors\ArrayExtractor;

final class ArrayExtractorTest extends TestCase
{
    public function test_it_extracts_array_data(): void
    {
        // Arrange
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $extractor = new ArrayExtractor($data);

        // Act
        $result = iterator_to_array($extractor->extract());

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_validates_headers(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = []; // Empty array should fail
        new ArrayExtractor($data);
    }
}
```

### Step 3: Implement ArrayExtractor
Only write code to make tests pass:

```php
// src/Extract/Extractors/ArrayExtractor.php
<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use Phetl\Contracts\ExtractorInterface;

final class ArrayExtractor implements ExtractorInterface
{
    public function __construct(
        private readonly array $data
    ) {
        if (empty($this->data)) {
            throw new \InvalidArgumentException('Data cannot be empty');
        }
    }

    public function extract(): iterable
    {
        foreach ($this->data as $row) {
            yield $row;
        }
    }
}
```

### Step 4: Build the Table Class (TDD)
```php
// tests/Unit/TableTest.php
<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phetl\Table;

final class TableTest extends TestCase
{
    public function test_it_creates_from_array(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data);

        $this->assertInstanceOf(Table::class, $table);
    }

    public function test_it_is_iterable(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data);

        $rows = iterator_to_array($table);
        $this->assertCount(2, $rows);
    }
}
```

## 🧪 TDD Workflow

For each feature:

1. **RED** - Write a failing test
   ```bash
   composer test
   # Test fails ❌
   ```

2. **GREEN** - Write minimal code to pass
   ```bash
   composer test
   # Test passes ✅
   ```

3. **REFACTOR** - Improve code quality
   ```bash
   composer quality
   # All checks pass ✅
   ```

## 📊 Development Checklist

### Core Interfaces (Week 1)
- [ ] `TableInterface`
- [ ] `ExtractorInterface`
- [ ] `LoaderInterface`
- [ ] `TransformerInterface`
- [ ] `IteratorInterface`

### Basic Extractors (Week 1-2)
- [ ] `ArrayExtractor` (simplest - start here)
- [ ] `CsvExtractor`
- [ ] `JsonExtractor`

### Basic Loaders (Week 2)
- [ ] `ArrayLoader`
- [ ] `CsvLoader`
- [ ] `JsonLoader`

### Table Core (Week 2-3)
- [ ] Basic `Table` class
- [ ] Iterator implementation
- [ ] `fromArray()` static factory
- [ ] `fromCsv()` static factory
- [ ] `toArray()` method
- [ ] `toCsv()` method

### First Transformations (Week 3-4)
- [ ] `head()` - select first N rows
- [ ] `tail()` - select last N rows
- [ ] `selectColumns()` - column selection
- [ ] `whereEquals()` - row filtering

### Pipeline Engine (Week 4-5)
- [ ] Lazy evaluation
- [ ] Transform chaining
- [ ] Iterator caching

## 🎨 SOLID Examples

### Single Responsibility
```php
// Good - One reason to change
class CsvExtractor { /* Only CSV extraction logic */ }
class CsvLoader { /* Only CSV writing logic */ }

// Bad - Multiple reasons to change
class CsvHandler { /* Both read and write */ }
```

### Open/Closed
```php
// Extensible through interfaces
interface ExtractorInterface { }

// New types don't modify existing code
class RestApiExtractor implements ExtractorInterface { }
```

### Dependency Inversion
```php
// Depend on abstractions
class Table {
    public function __construct(
        private ExtractorInterface $extractor  // Interface, not concrete
    ) { }
}
```

## 📚 Resources

- [PSR-12 Standard](https://www.php-fig.org/psr/psr-12/)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)

## 🚀 Ready to Start!

Run this to get started:
```bash
composer install
git init
git add .
git commit -m "Initial scaffolding"
composer test  # Should pass with 0 tests
```

Then begin with the first test for `ArrayExtractor`!
