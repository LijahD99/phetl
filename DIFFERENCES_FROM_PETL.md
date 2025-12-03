# Differences from Python PETL

This document outlines the intentional differences between Phetl (this PHP implementation) and the original Python petl library.

## Philosophy

Phetl aims to maintain **functional compatibility** with petl while embracing **PHP idioms** and providing **enhanced observability** for production ETL workflows.

## Key Differences

### 1. Load Operation Return Values ⚠️ BREAKING CHANGE

**Python petl behavior:**
```python
import petl as etl
table = [['foo', 'bar'], ['a', 1], ['b', 2]]

# Returns None (void) - pure side effect
etl.tocsv(table, 'output.csv')
etl.todb(table, connection, 'tablename')
```

**Phetl behavior:**
```php
use Phetl\Table;

$table = Table::fromArray([
    ['foo', 'bar'],
    ['a', 1],
    ['b', 2]
]);

// Returns LoadResult object
$result = $table->toCsv('output.csv');
$rowCount = $result->rowCount();  // 2
```

#### Rationale

**Observability** is critical in production ETL pipelines. The `LoadResult` object provides:

1. **Row count validation** - Verify expected number of rows were loaded
2. **Success checking** - Detect failures without exceptions
3. **Error details** - Access specific error messages
4. **Warning tracking** - Capture non-fatal issues
5. **Performance metrics** - Monitor operation duration

#### LoadResult API

```php
class LoadResult
{
    public function rowCount(): int;           // Number of rows loaded (excluding header)
    public function success(): bool;           // True if no errors occurred
    public function errors(): array;           // Array of error messages
    public function warnings(): array;         // Array of warning messages
    public function duration(): ?float;        // Duration in seconds (if tracked)
    public function hasErrors(): bool;         // Quick check for errors
    public function hasWarnings(): bool;       // Quick check for warnings
}
```

#### Migration from PETL

**Before (Python petl):**
```python
# Silent operation - no feedback
etl.tocsv(table, 'output.csv')
```

**After (Phetl):**
```php
// Simple case - just get row count
$rowCount = $table->toCsv('output.csv')->rowCount();
$this->assertEquals(2, $rowCount);

// Production use - full observability
$result = $table->toCsv('output.csv');

if ($result->success()) {
    $this->logger->info("Loaded {$result->rowCount()} rows in {$result->duration()}s");

    if ($result->hasWarnings()) {
        foreach ($result->warnings() as $warning) {
            $this->logger->warning($warning);
        }
    }
} else {
    $this->logger->error("Load failed with {$result->rowCount()} errors");
    foreach ($result->errors() as $error) {
        $this->logger->error($error);
    }
    throw new Exception("Data load failed");
}
```

#### Affected Methods

All loader methods return `LoadResult`:
- `toCsv()` / `tocsv()`
- `toJson()` / `tojson()`
- `toDatabase()` / `todb()`
- `toExcel()` / `toexcel()`
- `toLoader()` (custom loaders)

### 2. Improved Naming (with Aliases)

Phetl provides **descriptive method names** while maintaining petl compatibility through aliases.

**Primary API (recommended):**
```php
$table->selectColumns('name', 'age')      // vs cut()
      ->removeColumns('temp_id')          // vs cutout()
      ->whereEquals('status', 'active')   // vs selecteq()
      ->whereGreaterThan('age', 18)       // vs selectgt()
      ->sortBy('name')                    // vs sort()
      ->groupBy('department')             // vs aggregate()
```

**petl-compatible aliases (available):**
```php
$table->cut('name', 'age')
      ->cutout('temp_id')
      ->selecteq('status', 'active')
      ->selectgt('age', 18)
      ->sort('name')
      ->aggregate('department', [...])
```

See README.md "Addressing petl's Naming Conventions" for complete mapping.

### 3. Type Safety

Phetl leverages PHP 8.1+ type hints for better IDE support and error detection:

```php
// Strict types enforced
declare(strict_types=1);

// Type-safe method signatures
public function whereEquals(string $field, mixed $value): self
public function aggregate(string|array $groupBy, array $aggregations): self
public function toCsv(string $filePath, string $delimiter = ','): LoadResult
```

### 4. Excel Support

Phetl includes first-class Excel support via PhpSpreadsheet:

```php
// Read from Excel
$table = Table::fromExcel('data.xlsx', 'SheetName');

// Write to Excel
$table->toExcel('output.xlsx', 'Results');

// Not available in base petl (requires petl.io.xlsx extension)
```

### 5. RESTful API Extraction

Phetl provides built-in REST API extraction with modern features:

```php
$table = Table::fromRestApi('https://api.example.com/users', [
    'auth' => [
        'type' => 'bearer',
        'token' => $apiToken
    ],
    'pagination' => [
        'type' => 'offset',
        'page_size' => 100,
        'max_pages' => 10
    ],
    'mapping' => [
        'data_path' => 'results.users',
        'fields' => [
            'id' => 'user_id',
            'name' => 'profile.full_name'
        ]
    ]
]);

// petl requires manual HTTP handling
```

### 6. Fluent Method Chaining

Phetl emphasizes method chaining as the primary API style:

```php
// Preferred Phetl style
Table::fromCsv('input.csv')
    ->whereEquals('status', 'active')
    ->sortBy('created_at')
    ->selectColumns('id', 'name', 'email')
    ->toCsv('output.csv');

// petl style (also supported via static methods)
$table1 = Table::fromCsv('input.csv');
$table2 = Table::whereEquals($table1, 'status', 'active');
$table3 = Table::sortBy($table2, 'created_at');
// etc...
```

## Compatibility Notes

### What's Compatible

✅ **Core Concepts**: Table container, header row, lazy evaluation
✅ **Transformation Logic**: Same algorithms for sort, join, aggregate, etc.
✅ **API Names**: Aliases provided for petl method names
✅ **Data Formats**: CSV, JSON, database support

### What's Different

⚠️ **Return Values**: Load operations return `LoadResult` instead of `None`
⚠️ **Type Hints**: Strict typing enforced in PHP
⚠️ **Exceptions**: PHP exceptions instead of Python exceptions
⚠️ **Generators**: PHP generators instead of Python iterators

### Migration Checklist

When porting petl code to Phetl:

1. ✅ Change `import petl as etl` to `use Phetl\Table;`
2. ✅ Update `etl.fromcsv()` to `Table::fromCsv()`
3. ⚠️ Add `->rowCount()` to load operations that check row counts
4. ✅ Convert Python lambdas to PHP closures: `lambda row: ...` → `fn($row) => ...`
5. ✅ Update dictionary syntax: `{'key': value}` → `['key' => $value]`
6. ✅ Update method calls: `.method()` stays the same

## Future Differences

### Planned Enhancements

These features are planned for Phetl but not in petl:

- **Rate Limiting**: Built-in rate limiting for API extractors
- **Retry Logic**: Automatic retry with exponential backoff
- **Performance Metrics**: Built-in timing and memory profiling
- **Data Lineage**: Track data transformations for debugging
- **Validation Framework**: Enhanced data quality checking

### Philosophy

Phetl's enhancements follow these principles:

1. **Maintain Compatibility**: Provide aliases for petl methods
2. **Enhance Observability**: Return useful information from operations
3. **Embrace PHP Idioms**: Use PHP 8.1+ features appropriately
4. **Production Ready**: Design for real-world ETL pipelines
5. **Developer Experience**: Prioritize IDE support and type safety

## Questions?

If you're migrating from petl and encounter differences not documented here, please [open an issue](https://github.com/LijahD99/phetl/issues).

## See Also

- [README.md](README.md) - Full project overview
- [GETTING_STARTED_DEV.md](GETTING_STARTED_DEV.md) - Development guide
- [petl Documentation](https://petl.readthedocs.io/) - Original Python library
