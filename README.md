# PHETL - PHP ETL Library

A PHP implementation of Python's [petl](https://petl.readthedocs.io/) library for Extract, Transform, and Load operations on tabular data.

## About PETL

**petl** is a general-purpose Python package for extracting, transforming, and loading tables of data. It provides:

- **Lazy evaluation** - transformations are not executed until data is actually needed
- **Memory efficiency** - uses iterators to process data row-by-row
- **Flexible I/O** - read/write CSV, Excel, JSON, databases, and more
- **Rich transformations** - sort, filter, join, aggregate, reshape, and validate data
- **Fluent API** - supports both functional and method-chaining styles

## Core Concepts

### 1. Table Container Convention
A table is any object that:
- Implements an iterator interface
- First row is the header (field names)
- Subsequent rows are data rows
- All rows are sequences (arrays in PHP)

Example:
```php
$table = [
    ['foo', 'bar', 'baz'],  // header row
    ['a', 1, 3.4],          // data row 1
    ['b', 2, 7.8],          // data row 2
];
```

### 2. Lazy Evaluation & Pipelines
Transformations are chained but not executed until data is pulled:

```python
# Python petl example
table1 = etl.fromcsv('example.csv')
table2 = etl.convert(table1, 'foo', 'upper')
table3 = etl.convert(table2, 'bar', int)
table4 = etl.addfield(table3, 'quux', lambda row: row.bar * row.baz)
# Nothing executed yet until...
etl.look(table4)  # Now the pipeline executes
```

### 3. Functional Categories

#### **I/O Operations**
- Read/write CSV, TSV, JSON, Excel, databases
- Support for remote/cloud filesystems
- Memory-efficient streaming

#### **Basic Transformations**
- `head()` / `tail()` - select first/last N rows
- `cut()` / `cutout()` - select/remove fields
- `cat()` / `stack()` - concatenate tables
- `addfield()` / `addcolumn()` - add computed fields
- `rowslice()` - select row ranges

#### **Header Manipulations**
- `rename()` - rename fields
- `setheader()` / `pushheader()` / `extendheader()` - modify headers
- `skip()` - skip N rows including header

#### **Value Conversions**
- `convert()` - transform values via functions, methods, or dictionaries
- `replace()` / `replaceall()` - replace specific values
- `format()` / `interpolate()` - string formatting
- `convertnumbers()` - auto-convert to numeric types

#### **Selecting/Filtering Rows**
- `select()` - filter by lambda or expression
- `selecteq()`, `selectne()`, `selectgt()`, `selectlt()`, etc. - comparison filters
- `selectin()`, `selectcontains()` - membership tests
- `selecttrue()`, `selectfalse()`, `selectnone()` - boolean filters
- `facet()` - split table by field values
- `biselect()` - split into matching/non-matching tables

#### **Regular Expressions**
- `search()` / `searchcomplement()` - regex filtering
- `sub()` - regex substitution
- `split()` / `splitdown()` - split values
- `capture()` - extract via capture groups

#### **Unpacking**
- `unpack()` - unpack lists/tuples into separate fields
- `unpackdict()` - unpack dictionaries into fields

#### **Row Transformations**
- `fieldmap()` - arbitrary field mappings
- `rowmap()` - transform entire rows
- `rowmapmany()` - map one row to multiple rows

#### **Sorting**
- `sort()` - sort by key with disk-based merge for large datasets
- `mergesort()` - merge pre-sorted tables
- `issorted()` - check if sorted

#### **Joins**
- `join()` - inner join
- `leftjoin()` / `rightjoin()` / `outerjoin()` - outer joins
- `lookupjoin()` - left join with duplicate handling
- `crossjoin()` - cartesian product
- `antijoin()` - rows in left not in right
- `unjoin()` - reverse a join operation
- Hash-based variants: `hashjoin()`, `hashleftjoin()`, etc.

#### **Set Operations**
- `complement()` - rows in A not in B
- `diff()` - symmetric difference
- `intersection()` - rows in both tables
- `recordcomplement()` / `recorddiff()` - field-aware set ops

#### **Deduplication**
- `duplicates()` / `unique()` - find/select duplicates
- `distinct()` - remove duplicates
- `conflicts()` - find conflicting values
- `isunique()` - check uniqueness

#### **Aggregation**
- `aggregate()` - group and aggregate with custom functions
- `rowreduce()` - reduce row groups
- `fold()` - recursive reduction
- `mergeduplicates()` - merge duplicate rows
- `groupselectfirst()` / `groupselectlast()` - select from groups
- `groupselectmin()` / `groupselectmax()` - select by value

#### **Reshaping**
- `melt()` - unpivot wide to long format
- `recast()` - pivot long to wide format
- `transpose()` - swap rows and columns
- `pivot()` - create pivot tables
- `flatten()` / `unflatten()` - convert to/from flat sequences

#### **Filling Missing Values**
- `filldown()` - fill from above
- `fillright()` - fill from left
- `fillleft()` - fill from right

#### **Validation**
- `validate()` - validate against constraints
- Returns table of validation problems

#### **Intervals** (requires intervaltree)
- `intervaljoin()` / `intervalleftjoin()` - join by overlapping intervals
- `intervallookup()` - interval-based lookups
- `intervalsubtract()` - subtract intervals

## Design Goals for PHETL

1. **Memory Efficient**: Use PHP generators/iterators for lazy evaluation
2. **Fluent API**: Support method chaining like Laravel Collections
3. **Type Safe**: Leverage PHP 8+ type hints and strict types
4. **Composable**: Each transformation is a separate, testable function
5. **Extensible**: Easy to add custom extractors, transformations, and loaders
6. **Improved Naming**: Address petl's naming issues (see below)
7. **Compatible**: Match petl's behavior where possible

## Project Standards

- ✅ **PSR-12 Compliant** - Strict adherence to PSR-12 coding standards
- ✅ **SOLID Principles** - Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- ✅ **TDD (Test-Driven Development)** - Tests written before implementation
- ✅ **Static Analysis** - PHPStan at maximum level
- ✅ **Type Safety** - Strict types everywhere (`declare(strict_types=1)`)
- ✅ **100% Test Coverage** - Comprehensive unit and integration tests

## Addressing petl's Naming Conventions

### The Problem
Many of petl's method names are:
- **Non-descriptive**: `cat()`, `cut()`, `annex()`
- **Obscure**: What does `rowslice()` do vs `head()` vs `tail()`?
- **Misleading**: `distinct()` vs `unique()` - which removes duplicates?
- **Inconsistent**: `selecteq()` vs `selectgt()` - why not `whereEquals()` or `filterGreaterThan()`?
- **Esoteric**: `facet()`, `unjoin()`, `recast()`

### Proposed Solutions

#### Option 1: Dual API (Recommended)
Provide both petl-compatible names AND improved alternatives:

```php
// petl-compatible (for migration & documentation reference)
$table->cut('name', 'age');
$table->selecteq('status', 'active');

// Improved names (recommended for new code)
$table->selectColumns('name', 'age');
$table->whereEquals('status', 'active');
```

**Pros**:
- Easy migration from Python petl
- Better discoverability for PHP developers
- Can deprecate old names over time

**Cons**:
- Larger API surface
- Need to maintain both

#### Option 2: Clean Break
Use only improved naming from the start:

```php
// Clear, descriptive names
$table->selectColumns('name', 'age')
      ->removeColumns('temp_id')
      ->whereEquals('status', 'active')
      ->whereGreaterThan('age', 18)
      ->sortBy('name')
      ->groupBy('department')
      ->aggregate(['count' => 'count', 'avg_age' => fn($rows) => avg($rows, 'age')]);
```

**Pros**:
- Clean, consistent, PHP-idiomatic API
- No confusion about which name to use
- Easier to learn

**Cons**:
- Harder to reference petl documentation
- Migration from Python petl requires translation

#### Option 3: Namespaced Approaches
Different namespaces for different styles:

```php
use Phetl\Table;           // Improved names
use Phetl\Compat\Table;    // petl-compatible names

// Or via factory
Table::compatible()->cut('name')->selecteq('status', 'active');
Table::fluent()->selectColumns('name')->whereEquals('status', 'active');
```

### Naming Improvement Guidelines

If we improve names, let's follow these principles:

1. **Verb-Noun Pattern**: `selectColumns()`, `removeRows()`, `groupByField()`
2. **Clear Intent**: `whereEquals()` instead of `selecteq()`
3. **Standard Conventions**: Use `where*()` for filtering, `sortBy()` for ordering
4. **Avoid Abbreviations**: `selectColumns()` not `selCols()`
5. **Consistent Prefixes**:
   - `where*()` - row filtering
   - `select*()` / `remove*()` - column operations
   - `groupBy*()` / `aggregateBy*()` - aggregations
   - `joinWith()` / `leftJoinWith()` - joins

### Example Name Mappings

| petl Original | Improved Name | Rationale |
|---------------|---------------|-----------|
| `cut()` | `selectColumns()` | Clearer what it does |
| `cutout()` | `removeColumns()` | Opposite of select |
| `cat()` | `concatenate()` or `append()` | Standard term |
| `annex()` | `appendColumns()` | Describes row-wise join |
| `rowslice()` | `sliceRows()` | More natural order |
| `selecteq()` | `whereEquals()` | SQL-like, clearer |
| `selectgt()` | `whereGreaterThan()` | Explicit comparison |
| `selectlt()` | `whereLessThan()` | Explicit comparison |
| `selectin()` | `whereIn()` | SQL-familiar |
| `facet()` | `groupIntoTables()` or `partitionBy()` | Clearer intent |
| `biselect()` | `partition()` | Standard functional term |
| `duplicates()` | `findDuplicates()` | Verb makes it active |
| `unique()` | `removeDuplicates()` or `distinct()` | Clearer action |
| `distinct()` | `unique()` or `distinct()` | Keep one, be clear |
| `melt()` | `unpivot()` | Standard database term |
| `recast()` | `pivot()` | Standard database term |
| `unjoin()` | `splitByForeignKey()` | Describes the action |
| `rowreduce()` | `reduceGroups()` | Clearer scope |
| `rowmap()` | `mapRows()` | More natural order |
| `rowmapmany()` | `flatMapRows()` | Standard functional term |
| `fieldmap()` | `mapFields()` or `transformFields()` | Clearer |
| `addfield()` | `addColumn()` | More common term |
| `addrownumbers()` | `addRowNumbers()` | Standard casing |

### Recommendation

**Start with Option 1 (Dual API)** for Phase 1-2 of development:
- Implement core functionality with improved names
- Add aliases for petl compatibility
- Document both in PHPDoc with cross-references
- After community feedback, potentially deprecate old names in future major version

This gives us:
- Best developer experience
- Easy reference to petl docs during development
- Path to cleaner API in the future

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

📖 **See [DIFFERENCES_FROM_PETL.md](DIFFERENCES_FROM_PETL.md) for complete documentation of differences**

## Contributing

This is a PHP port of the Python petl library. We aim to maintain API compatibility where possible while embracing PHP idioms and best practices.

## License

TBD (should align with petl's MIT license)

## References

- [petl documentation](https://petl.readthedocs.io/stable/)
- [petl GitHub](https://github.com/petl-developers/petl)
- [Differences from PETL](DIFFERENCES_FROM_PETL.md) - Important for petl users migrating to Phetl
