# Contributing to PHETL

Thank you for considering contributing to PHETL!

## Development Principles

This project follows:
- **PSR-12** code style
- **SOLID** principles
- **TDD** (Test-Driven Development)

## Getting Started

1. Fork the repository
2. Clone your fork
3. Install dependencies:
   ```bash
   composer install
   ```

## Development Workflow

### 1. Write Tests First (TDD)
Before implementing a feature, write the test:

```php
// tests/Unit/Transform/Rows/RowFilterTest.php
<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use Phetl\Table;
use PHPUnit\Framework\TestCase;

final class RowFilterTest extends TestCase
{
    public function test_where_equals_filters_rows(): void
    {
        $table = Table::fromArray([
            ['name', 'age', 'status'],
            ['Alice', 30, 'active'],
            ['Bob', 25, 'inactive'],
            ['Charlie', 30, 'active'],
        ]);

        $result = $table->whereEquals('status', 'active');

        $this->assertCount(2, iterator_to_array($result));
    }
}
```

### 2. Implement the Feature
Follow SOLID principles:

```php
// Single Responsibility
// Open/Closed
// Liskov Substitution
// Interface Segregation
// Dependency Inversion
```

### 3. Run Quality Checks

```bash
# Check code style (PSR-12)
composer cs:check

# Fix code style
composer cs:fix

# Run static analysis
composer phpstan

# Run tests
composer test

# Run all quality checks
composer quality
```

## Code Style

We follow PSR-12 strictly:

- `declare(strict_types=1);` at the top of every file
- Type hints for all parameters and return types
- No unused imports
- Proper spacing and formatting

## Testing

- Write unit tests for all new functionality
- Maintain 100% code coverage where practical
- Use descriptive test method names
- Follow the Arrange-Act-Assert pattern

```php
public function test_specific_behavior_under_specific_conditions(): void
{
    // Arrange
    $table = $this->createTestTable();

    // Act
    $result = $table->someOperation();

    // Assert
    $this->assertEquals($expected, $result);
}
```

## SOLID Principles Examples

### Single Responsibility Principle
Each class should have one reason to change:

```php
// Good: Separate concerns
class CsvExtractor implements ExtractorInterface { }
class CsvLoader implements LoaderInterface { }

// Bad: Mixed concerns
class CsvHandler { } // Does both read and write
```

### Open/Closed Principle
Open for extension, closed for modification:

```php
// Use interfaces and abstract classes
interface ExtractorInterface {
    public function extract(): iterable;
}

// New extractors extend without modifying existing code
class RestApiExtractor implements ExtractorInterface { }
```

### Interface Segregation Principle
Clients shouldn't depend on interfaces they don't use:

```php
// Good: Specific interfaces
interface Readable { }
interface Writable { }

// Bad: Fat interface
interface DataSource extends Readable, Writable, Seekable, Cacheable { }
```

## Pull Request Process

1. Create a feature branch from `main`
2. Write tests for your changes (TDD)
3. Implement your changes
4. Ensure all quality checks pass
5. Update documentation if needed
6. Submit a pull request with clear description

## Questions?

Feel free to open an issue for discussion!
