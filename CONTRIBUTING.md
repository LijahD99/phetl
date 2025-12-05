# Contributing to PHETL

Thank you for your interest in contributing to PHETL! This document provides guidelines for contributing to the project.

## Quick Start

```bash
# Fork and clone the repository
git clone https://github.com/YOUR_USERNAME/phetl.git
cd phetl

# Install dependencies
composer install

# Run tests to verify setup
composer test

# Run all quality checks
composer quality
```

## Development Standards

### Code Style

We follow **PSR-12** coding standards:

- `declare(strict_types=1);` at the top of every file
- Type hints for all parameters and return types
- No unused imports
- Proper spacing and formatting

### Quality Commands

```bash
composer cs:check    # Check code style
composer cs:fix      # Fix code style issues
composer phpstan     # Static analysis (max level)
composer test        # Run all tests
composer quality     # Run all checks
```

## Testing

### Test-Driven Development (TDD)

We encourage TDD workflow:

1. **Write a failing test** that describes the behavior
2. **Write minimal code** to make the test pass
3. **Refactor** while keeping tests green

### Test Structure

Follow the Arrange-Act-Assert pattern:

```php
public function test_filters_rows_matching_condition(): void
{
    // Arrange
    $table = Table::fromArray([
        ['name', 'status'],
        ['Alice', 'active'],
        ['Bob', 'inactive'],
    ]);

    // Act
    $result = $table->whereEquals('status', 'active');

    // Assert
    $this->assertEquals(1, $result->count());
}
```

### Test Naming

Use descriptive test method names:

```php
// Good
public function test_aggregate_groups_by_multiple_fields(): void
public function test_throws_exception_for_invalid_column(): void

// Avoid
public function testAggregate(): void
public function test1(): void
```

## Architecture

### Project Structure

```
src/
├── Table.php              # Main fluent API class
├── Extract/Extractors/    # Data sources (CSV, JSON, etc.)
├── Load/Loaders/          # Data destinations
├── Transform/             # Transformation operations
│   ├── Rows/              # Row-level operations
│   ├── Columns/           # Column-level operations
│   ├── Values/            # Value-level operations
│   ├── Joins/             # Table joining
│   ├── Aggregation/       # Grouping and aggregation
│   └── Validation/        # Data validation
└── Contracts/             # Interfaces
```

### Design Principles

**Single Responsibility**: Each class has one reason to change.

```php
// Good: Separate concerns
class CsvExtractor { }  // Only extracts from CSV
class CsvLoader { }     // Only loads to CSV
```

**Open/Closed**: Extend via interfaces, not modification.

```php
interface ExtractorInterface {
    public function extract(): array;
}

// New extractors implement the interface
class XmlExtractor implements ExtractorInterface { }
```

## Pull Request Process

1. **Create a feature branch** from `main`
2. **Write tests** for your changes
3. **Implement** your changes
4. **Run quality checks**: `composer quality`
5. **Update documentation** if needed
6. **Submit PR** with clear description

### PR Checklist

- [ ] Tests pass (`composer test`)
- [ ] Code style passes (`composer cs:check`)
- [ ] Static analysis passes (`composer phpstan`)
- [ ] New features have tests
- [ ] Documentation updated if needed

## Reporting Issues

When reporting bugs, please include:

- PHP version
- PHETL version
- Minimal code to reproduce
- Expected vs actual behavior

## Questions?

Open an issue with the "question" label or start a discussion.
