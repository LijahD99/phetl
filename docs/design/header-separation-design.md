# Header Separation Design

## Problem

The current design treats headers as "just another row" in the data array, which creates several issues:

1. **Semantic confusion**: Headers are metadata, not data
2. **Count ambiguity**: Does `count()` include the header?
3. **Awkward for headerless data**: Users must manually prepend headers
4. **Error-prone**: Easy to treat header as data accidentally
5. **Inflexible**: Hard to swap headers or work with headerless sources

## Solution

Separate headers as a distinct property from data rows.

## Design

### Table Class

```php
class Table implements IteratorAggregate, Countable
{
    private function __construct(
        private readonly array $headers,
        private readonly array $materializedData  // Now contains ONLY data rows
    ) {}

    // Headers accessor - returns array of column names
    public function header(): array

    // Count - returns number of DATA rows (excludes header)
    public function count(): int

    // Iterator - yields DATA rows only (not header)
    public function getIterator(): Traversable

    // toArray - returns header + data for compatibility
    public function toArray(): array
}
```

### Factory Methods

All factory methods will accept optional explicit headers:

```php
// Backward compatible - first row is header
Table::fromArray([
    ['name', 'age'],
    ['Alice', 30]
])

// Explicit headers (preferred for clarity)
Table::fromArray(
    [['Alice', 30]],
    ['name', 'age']
)

// CSV with headers (default)
Table::fromCsv('file.csv')  // hasHeaders = true

// CSV without headers (auto-generate)
Table::fromCsv('file.csv', hasHeaders: false)
// Creates headers: ['col_0', 'col_1', 'col_2']

// Database - auto-extract from result keys
Table::fromDatabase($pdo, 'SELECT name, age FROM users')
```

### Behavior Changes

| Method | Old Behavior | New Behavior |
|--------|-------------|--------------|
| `count()` | Includes header | Data rows only |
| `getIterator()` | Yields header + data | Yields data only |
| `toArray()` | Returns all rows | Returns header + data (same) |
| `header()` | Returns first row | Returns headers property |
| `look()` | Shows header + N rows | Shows header + N data rows (same) |

### Migration Impact

**Breaking Changes:**
- `count()` will return 1 less (doesn't count header)
- Direct iteration won't yield header row
- Transformations receive data-only, not header+data

**Backward Compatible:**
- `toArray()` still returns header + data
- `fromArray()` accepts old format (first row = header)
- `header()` works the same
- `look()` works the same

## Implementation Plan

### Phase 1: Core Table Class
1. Update `Table` constructor
2. Update `header()`, `count()`, `getIterator()`
3. Update `toArray()` to combine headers + data
4. Add backward-compatible `fromArray()` signatures

### Phase 2: Extractors
Update to return separate headers and data:
- ArrayExtractor
- CsvExtractor
- JsonExtractor
- DatabaseExtractor
- ExcelExtractor
- RestApiExtractor

### Phase 3: Transformations
Update all to work with separate headers/data:
- Row transformations (filter, sort, select)
- Column transformations (select, rename, add)
- Value transformations (convert, replace)
- Joins, aggregations, reshaping
- Set operations

### Phase 4: Loaders
Update to correctly write headers + data:
- CsvLoader
- JsonLoader
- DatabaseLoader
- ExcelLoader

### Phase 5: Tests
Update all test assertions for new behavior

## Benefits

1. **Clear semantics**: Headers are metadata, clearly separated
2. **Flexible**: Easy to work with headerless data sources
3. **Intuitive**: `count()` means "data rows"
4. **Type-safe**: Can validate headers vs data
5. **Future-ready**: Enables named column access

## Documentation

Will document in `docs/DIFFERENCES_FROM_PETL.md`:
- Header separation vs PETL's "header as first row"
- Rationale for the change
- Migration examples
- Benefits for production use
