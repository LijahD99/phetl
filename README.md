# PHETL - PHP ETL Library

A PHP implementation of Python's [petl](https://petl.readthedocs.io/) library for Extract, Transform, and Load operations on tabular data.

## Design Goals for PHETL

1. **Memory Efficient**: Use PHP generators/iterators for lazy evaluation
2. **Fluent API**: Support method chaining like Laravel Collections
3. **Type Safe**: Leverage PHP 8+ type hints and strict types
4. **Composable**: Each transformation is a separate, testable function
5. **Extensible**: Easy to add custom extractors, transformations, and loaders
6. **Improved Naming**: Address petl's naming issues (see [Differences from PETL](docs/DIFFERENCES_FROM_PETL.md))
7. **Compatible**: Match petl's behavior where possible

## Project Standards

- ✅ **PSR-12 Compliant** - Strict adherence to PSR-12 coding standards
- ✅ **SOLID Principles** - Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- ✅ **TDD (Test-Driven Development)** - Tests written before implementation
- ✅ **Static Analysis** - PHPStan at maximum level
- ✅ **Type Safety** - Strict types everywhere (`declare(strict_types=1)`)
- ✅ **100% Test Coverage** - Comprehensive unit and integration tests

## Implementation Strategy

### Phase 1: Core Foundation
- [ ] Table container interface/class
- [ ] Iterator-based lazy evaluation
- [ ] Basic I/O (CSV, array)
- [ ] Fluent wrapper for method chaining

### Phase 2: Basic Transformations
- [x] head, tail, rowslice
- [x] cut, cutout
- [x] cat, stack
- [x] addfield, addcolumn
- [x] rename, setheader

### Phase 3: Data Transformations
- [x] convert, replace
- [x] select, selecteq, selectgt, etc.
- [x] sort, mergesort
- [ ] unique, distinct, duplicates

### Phase 4: Advanced Operations
- [x] join, leftjoin, rightjoin
- [x] aggregate, rowreduce
- [ ] melt, recast, pivot
- [ ] regex operations

### Phase 5: Additional Features
- [ ] Validation framework
- [ ] Additional I/O formats (JSON, Excel)
- [ ] Database integration
- [ ] Optimization and caching

## Example Usage (Proposed PHP API)

```php
use Phetl\Table;

// Functional style
$table1 = Table::fromCsv('example.csv');
$table2 = Table::convert($table1, 'foo', 'strtoupper');
$table3 = Table::convert($table2, 'bar', 'intval');
$table4 = Table::addField($table3, 'quux', fn($row) => $row['bar'] * $row['baz']);
$table4->toCsv('output.csv');

// Method chaining style
Table::fromCsv('example.csv')
    ->convert('foo', 'strtoupper')
    ->convert('bar', 'intval')
    ->addField('quux', fn($row) => $row['bar'] * $row['baz'])
    ->toCsv('output.csv');

// Or with look() for inspection
Table::fromCsv('example.csv')
    ->convert('foo', 'strtoupper')
    ->look(10);  // Display first 10 rows
```

## Project Structure Rationale

### The ETL Organization Problem

petl's structure (`IO/`, `Transform/`) doesn't clearly communicate ETL intent:
- **IO** is ambiguous - both reading AND writing?
- **Transform** is a catch-all with too many subconcerns mixed together
- The organization doesn't reflect the natural ETL workflow

### Proposed Structure: ETL-First Architecture

```
phetl/
├── src/
│   ├── Table.php                    # Main table class with fluent API
│   │
│   ├── Extract/                     # EXTRACT: Getting data IN
│   │   ├── Extractors/
│   │   │   ├── CsvExtractor.php
│   │   │   ├── JsonExtractor.php
│   │   │   ├── ExcelExtractor.php
│   │   │   ├── DatabaseExtractor.php
│   │   │   ├── ArrayExtractor.php
│   │   │   ├── XmlExtractor.php
│   │   │   └── RestApiExtractor.php     # 🔜 For consuming RESTful APIs
│   │   ├── ExtractorInterface.php
│   │   └── ExtractorFactory.php
│   │
│   ├── Load/                        # LOAD: Sending data OUT
│   │   ├── Loaders/
│   │   │   ├── CsvLoader.php
│   │   │   ├── JsonLoader.php
│   │   │   ├── ExcelLoader.php
│   │   │   ├── DatabaseLoader.php
│   │   │   └── XmlLoader.php
│   │   ├── LoaderInterface.php
│   │   └── LoaderFactory.php
│   │
│   ├── Transform/                   # TRANSFORM: Manipulating data
│   │   │
│   │   ├── Columns/                 # Column-level operations
│   │   │   ├── ColumnSelector.php   # selectColumns, removeColumns
│   │   │   ├── ColumnRenamer.php    # rename, prefixColumns
│   │   │   ├── ColumnAdder.php      # addColumn, addCalculated
│   │   │   └── ColumnReorder.php    # moveColumn, sortColumns
│   │   │
│   │   ├── Rows/                    # Row-level operations
│   │   │   ├── RowFilter.php        # where*, filter operations
│   │   │   ├── RowSelector.php      # head, tail, slice
│   │   │   ├── RowMapper.php        # mapRows, transformRows
│   │   │   ├── RowSorter.php        # sortBy, orderBy
│   │   │   └── RowDeduplicator.php  # distinct, unique, removeDuplicates
│   │   │
│   │   ├── Values/                  # Cell/value-level operations
│   │   │   ├── ValueConverter.php   # convert, cast, transform
│   │   │   ├── ValueReplacer.php    # replace, substitute
│   │   │   ├── ValueFormatter.php   # format, interpolate
│   │   │   └── ValueFiller.php      # fillDown, fillRight, coalesce
│   │   │
│   │   ├── Joins/                   # Combining tables
│   │   │   ├── InnerJoin.php
│   │   │   ├── LeftJoin.php
│   │   │   ├── RightJoin.php
│   │   │   ├── OuterJoin.php
│   │   │   ├── CrossJoin.php
│   │   │   └── JoinStrategy.php
│   │   │
│   │   ├── Aggregation/             # Grouping and aggregating
│   │   │   ├── GroupBy.php
│   │   │   ├── Aggregator.php
│   │   │   ├── Reducer.php
│   │   │   └── AggregateFunction.php
│   │   │
│   │   ├── Reshaping/               # Structural transformations
│   │   │   ├── Pivot.php            # pivot tables
│   │   │   ├── Unpivot.php          # melt/unpivot
│   │   │   ├── Transpose.php        # swap rows/columns
│   │   │   ├── Flatten.php          # flatten nested
│   │   │   └── Unflatten.php        # structure from flat
│   │   │
│   │   ├── Set/                     # Set operations
│   │   │   ├── Union.php
│   │   │   ├── Intersection.php
│   │   │   ├── Difference.php
│   │   │   ├── Complement.php
│   │   │   └── SetOperation.php
│   │   │
│   │   └── Validation/              # Data quality
│   │       ├── Validator.php
│   │       ├── Constraint.php
│   │       ├── ValidationRule.php
│   │       └── ValidationResult.php
│   │
│   ├── Engine/                      # Core execution engine
│   │   ├── Iterator/
│   │   │   ├── TableIterator.php
│   │   │   ├── TransformIterator.php
│   │   │   ├── LazyIterator.php
│   │   │   └── ChainIterator.php
│   │   ├── Pipeline/
│   │   │   ├── Pipeline.php
│   │   │   ├── PipelineBuilder.php
│   │   │   └── TransformStep.php
│   │   ├── Memory/
│   │   │   ├── BufferManager.php    # Memory management
│   │   │   ├── DiskSpiller.php      # Spill to disk for large ops
│   │   │   └── CacheManager.php
│   │   └── Optimizer/
│   │       ├── QueryOptimizer.php   # Optimize transform chains
│   │       └── IndexBuilder.php     # Build indexes for joins
│   │
│   ├── Support/                     # Shared utilities
│   │   ├── Lookups/
│   │   │   ├── Lookup.php
│   │   │   ├── FacetedLookup.php
│   │   │   └── IntervalLookup.php
│   │   ├── Functions/
│   │   │   ├── Comparison.php       # Comparison helpers
│   │   │   ├── Math.php             # Math helpers
│   │   │   └── String.php           # String helpers
│   │   ├── Regex/
│   │   │   ├── RegexMatcher.php
│   │   │   ├── RegexSplitter.php
│   │   │   └── RegexCapture.php
│   │   └── Types/
│   │       ├── TypeDetector.php
│   │       ├── TypeConverter.php
│   │       └── TypeRegistry.php
│   │
│   └── Contracts/                   # Interfaces
│       ├── TableInterface.php
│       ├── ExtractorInterface.php
│       ├── LoaderInterface.php
│       ├── TransformerInterface.php
│       ├── IteratorInterface.php
│       └── PipelineInterface.php
│
├── tests/
│   ├── Unit/
│   │   ├── Extract/
│   │   ├── Load/
│   │   ├── Transform/
│   │   └── Engine/
│   ├── Integration/
│   │   ├── Pipelines/
│   │   └── EndToEnd/
│   └── Fixtures/
│       └── sample_data/
│
├── docs/
│   ├── getting-started.md
│   ├── extractors.md
│   ├── transformations.md
│   ├── loaders.md
│   ├── advanced-pipelines.md
│   └── migration-from-petl.md
│
├── examples/
│   ├── basic-etl.php
│   ├── csv-processing.php
│   ├── database-migration.php
│   └── data-validation.php
│
├── composer.json
└── README.md
```

### Key Improvements

#### 1. **Clear ETL Separation**
- `Extract/` - Clear: "This is how I get data in"
- `Transform/` - Clear: "This is how I manipulate data"
- `Load/` - Clear: "This is how I send data out"

#### 2. **Transform Organization by Scope**
Instead of petl's flat `Transform/` with random groupings, organize by **what** is being transformed:

- **Columns/** - Operations that affect column structure
- **Rows/** - Operations that filter/select/order rows
- **Values/** - Operations that change individual cell values
- **Joins/** - Operations that combine tables
- **Aggregation/** - Operations that group and reduce
- **Reshaping/** - Operations that change table structure
- **Set/** - Set-theoretic operations
- **Validation/** - Data quality operations

This makes it **immediately clear** where to find or add functionality.

#### 3. **Engine Separation**
The `Engine/` namespace contains infrastructure that powers everything:
- Iterator management
- Pipeline building
- Memory management
- Query optimization

This keeps the "how it works" separate from the "what it does".

#### 4. **Support vs Util**
`Support/` instead of `Util/` - clearer that these are supporting helpers, not miscellaneous utilities.

### Benefits Over petl Structure

| Aspect | petl | PHETL |
|--------|------|-------|
| **Find read operations** | `io.csv`, `io.json`, etc. | `Extract/Extractors/` |
| **Find write operations** | `io.csv`, `io.json`, etc. | `Load/Loaders/` |
| **Find column operations** | Mixed in `Transform/` | `Transform/Columns/` |
| **Find row filtering** | `Transform/Selects` | `Transform/Rows/` |
| **Find joins** | `Transform/Joins` (ok) | `Transform/Joins/` (same) |
| **Find aggregation** | `Transform/Reductions` | `Transform/Aggregation/` |
| **Infrastructure** | Mixed everywhere | `Engine/` |

### Usage Implications

This structure makes the API more discoverable:

```php
use Phetl\Table;

// Clear where functionality comes from
use Phetl\Extract\Extractors\CsvExtractor;
use Phetl\Transform\Rows\RowFilter;
use Phetl\Transform\Aggregation\Aggregator;
use Phetl\Load\Loaders\DatabaseLoader;

// Or use the fluent API (which delegates internally)
Table::fromCsv('input.csv')      // Uses Extract/CsvExtractor
    ->whereEquals('status', 'active')  // Uses Transform/Rows/RowFilter
    ->groupBy('department')      // Uses Transform/Aggregation/GroupBy
    ->toDatabase($pdo, 'results');  // Uses Load/DatabaseLoader
```

### Migration Path

For petl compatibility, we can maintain a compatibility layer:

```php
// In src/Compat/Petl.php - maps petl function names to new structure
namespace Phetl\Compat;

class Petl {
    public static function fromcsv($path) {
        return \Phetl\Table::fromCsv($path);
    }

    public static function selecteq($table, $field, $value) {
        return $table->whereEquals($field, $value);
    }

    // etc...
}
```

This structure better reflects ETL workflows and makes the codebase more maintainable!

## Technology Stack

- **PHP 8.1+** - for modern features (enums, readonly properties, etc.)
- **Generators/Iterators** - for lazy evaluation
- **PHPUnit** - testing framework
- **PHP-CS-Fixer** - code style
- **PHPStan** - static analysis

## Differences from Python PETL

While Phetl maintains functional compatibility with Python's petl library, there are some intentional differences that enhance observability and embrace PHP idioms. Most notably:

⚠️ **Load operations return `LoadResult` objects instead of `None`**

```php
// Instead of: etl.tocsv(table, 'output.csv')  # Returns None
$result = $table->toCsv('output.csv');  // Returns LoadResult
$rowCount = $result->rowCount();        // Get rows loaded
```

This provides critical observability for production ETL pipelines including row counts, errors, warnings, and performance metrics.

📖 **See [DIFFERENCES_FROM_PETL.md](docs/DIFFERENCES_FROM_PETL.md) for complete documentation of differences**

## Contributing

This is a PHP port of the Python petl library. We aim to maintain API compatibility where possible while embracing PHP idioms and best practices.

## License

TBD (should align with petl's MIT license)

## References

- [petl documentation](https://petl.readthedocs.io/stable/)
- [petl GitHub](https://github.com/petl-developers/petl)
- [Differences from PETL](docs/DIFFERENCES_FROM_PETL.md) - Important for petl users migrating to Phetl
