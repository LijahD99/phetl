# Differences from Python PETL

This document outlines the intentional differences between Phetl (this PHP implementation) and the original Python petl library.

## Philosophy

Phetl aims to maintain **functional compatibility** with petl while embracing **PHP idioms** and providing **enhanced observability** for production ETL workflows.

## About Python PETL

**petl** is a general-purpose Python package for extracting, transforming, and loading tables of data. It provides:

- **Lazy evaluation** - transformations are not executed until data is actually needed
- **Memory efficiency** - uses iterators to process data row-by-row
- **Flexible I/O** - read/write CSV, Excel, JSON, databases, and more
- **Rich transformations** - sort, filter, join, aggregate, reshape, and validate data
- **Fluent API** - supports both functional and method-chaining styles

### PETL Core Concepts

#### 1. Table Container Convention
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

#### 2. Lazy Evaluation & Pipelines
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

#### 3. Functional Categories

##### **I/O Operations**
- Read/write CSV, TSV, JSON, Excel, databases
- Support for remote/cloud filesystems
- Memory-efficient streaming

##### **Basic Transformations**
- `head()` / `tail()` - select first/last N rows
- `cut()` / `cutout()` - select/remove fields
- `cat()` / `stack()` - concatenate tables
- `addfield()` / `addcolumn()` - add computed fields
- `rowslice()` - select row ranges

##### **Header Manipulations**
- `rename()` - rename fields
- `setheader()` / `pushheader()` / `extendheader()` - modify headers
- `skip()` - skip N rows including header

##### **Value Conversions**
- `convert()` - transform values via functions, methods, or dictionaries
- `replace()` / `replaceall()` - replace specific values
- `format()` / `interpolate()` - string formatting
- `convertnumbers()` - auto-convert to numeric types

##### **Selecting/Filtering Rows**
- `select()` - filter by lambda or expression
- `selecteq()`, `selectne()`, `selectgt()`, `selectlt()`, etc. - comparison filters
- `selectin()`, `selectcontains()` - membership tests
- `selecttrue()`, `selectfalse()`, `selectnone()` - boolean filters
- `facet()` - split table by field values
- `biselect()` - split into matching/non-matching tables

##### **Regular Expressions**
- `search()` / `searchcomplement()` - regex filtering
- `sub()` - regex substitution
- `split()` / `splitdown()` - split values
- `capture()` - extract via capture groups

##### **Unpacking**
- `unpack()` - unpack lists/tuples into separate fields
- `unpackdict()` - unpack dictionaries into fields

##### **Row Transformations**
- `fieldmap()` - arbitrary field mappings
- `rowmap()` - transform entire rows
- `rowmapmany()` - map one row to multiple rows

##### **Sorting**
- `sort()` - sort by key with disk-based merge for large datasets
- `mergesort()` - merge pre-sorted tables
- `issorted()` - check if sorted

##### **Joins**
- `join()` - inner join
- `leftjoin()` / `rightjoin()` / `outerjoin()` - outer joins
- `lookupjoin()` - left join with duplicate handling
- `crossjoin()` - cartesian product
- `antijoin()` - rows in left not in right
- `unjoin()` - reverse a join operation
- Hash-based variants: `hashjoin()`, `hashleftjoin()`, etc.

##### **Set Operations**
- `complement()` - rows in A not in B
- `diff()` - symmetric difference
- `intersection()` - rows in both tables
- `recordcomplement()` / `recorddiff()` - field-aware set ops

##### **Deduplication**
- `duplicates()` / `unique()` - find/select duplicates
- `distinct()` - remove duplicates
- `conflicts()` - find conflicting values
- `isunique()` - check uniqueness

##### **Aggregation**
- `aggregate()` - group and aggregate with custom functions
- `rowreduce()` - reduce row groups
- `fold()` - recursive reduction
- `mergeduplicates()` - merge duplicate rows
- `groupselectfirst()` / `groupselectlast()` - select from groups
- `groupselectmin()` / `groupselectmax()` - select by value

##### **Reshaping**
- `melt()` - unpivot wide to long format
- `recast()` - pivot long to wide format
- `transpose()` - swap rows and columns
- `pivot()` - create pivot tables
- `flatten()` / `unflatten()` - convert to/from flat sequences

##### **Filling Missing Values**
- `filldown()` - fill from above
- `fillright()` - fill from left
- `fillleft()` - fill from right

##### **Validation**
- `validate()` - validate against constraints
- Returns table of validation problems

##### **Intervals** (requires intervaltree)
- `intervaljoin()` / `intervalleftjoin()` - join by overlapping intervals
- `intervallookup()` - interval-based lookups
- `intervalsubtract()` - subtract intervals

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

### Name Mappings

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

See the "Name Mappings" table above for complete mapping.

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

- [README.md](../README.md) - Full project overview
- [GETTING_STARTED_DEV.md](../GETTING_STARTED_DEV.md) - Development guide
- [petl Documentation](https://petl.readthedocs.io/) - Original Python library
