# Header Separation Refactoring - Current Status

## Completed Work ✅

### 1. Core Architecture (100% Complete)
- ✅ **Table.php Core Methods**
  - Constructor now accepts `(array $headers, iterable $data)`
  - `header()` returns `$this->headers` directly
  - `count()` counts data rows only (excludes header)
  - `getIterator()` yields data rows only
  - `toArray()` returns header + data for backward compatibility
  - `look()` displays header + N data rows

### 2. Extractors (100% Complete - 6 files)
- ✅ **ExtractorInterface** - Returns `array{0: array<string>, 1: iterable}`
- ✅ **ArrayExtractor** - Supports explicit headers or first-row-as-header
- ✅ **CsvExtractor** - Added `hasHeaders` parameter, generates col_N if needed
- ✅ **JsonExtractor** - Extracts headers from JSON object keys
- ✅ **DatabaseExtractor** - Gets headers from result set metadata
- ✅ **ExcelExtractor** - Added `hasHeaders` parameter
- ✅ **RestApiExtractor** - Complex refactoring for pagination + field mapping

### 3. Loaders (100% Complete - 4 files)
- ✅ **LoaderInterface** - Accepts `(array $headers, iterable $data)`
- ✅ **CsvLoader** - Writes header row separately
- ✅ **JsonLoader** - Converts headers + rows to JSON objects
- ✅ **DatabaseLoader** - Uses headers for column mapping
- ✅ **ExcelLoader** - Writes headers as first row

### 4. Factory Methods (100% Complete)
All `Table::from*()` methods updated to destructure `[$headers, $data]` from extractors

### 5. Load Methods (100% Complete)
All `Table::to*()` methods updated to pass `($this->headers, $this->materializedData)`

## Remaining Work 🚧

### Transformation Classes (0% Complete - 17 files, ~60 methods)

#### Priority 1: Simple Row Operations
- [ ] `RowSelector.php` (4 methods) - head(), tail(), slice(), skip()
- [ ] `RowSorter.php` (1 method) - sort()
- [ ] `RowFilter.php` (15 methods) - filter(), whereEquals(), whereIn(), etc.

#### Priority 2: Deduplication & Validation
- [ ] `Deduplicator.php` (5 methods) - distinct(), duplicates(), countDistinct(), isUnique()
- [ ] `Validator.php` (2 methods) - required(), validate()

#### Priority 3: Column Operations
- [ ] `ColumnSelector.php` (2 methods) - select(), remove()
- [ ] `ColumnRenamer.php` (1 method) - rename()
- [ ] `ColumnAdder.php` (2 methods) - add(), addRowNumbers()

#### Priority 4: Value Transformations
- [ ] `ValueConverter.php` (2 methods) - convert(), convertMultiple()
- [ ] `ValueReplacer.php` (3 methods) - replace(), replaceMap(), replaceAll()
- [ ] `StringTransformer.php` (5 methods) - upper(), lower(), trim(), concat(), extract()
- [ ] `ConditionalTransformer.php` (5 methods) - when(), coalesce(), nullIf(), ifNull(), case()
- [ ] `WindowFunctions.php` (5 methods) - lag(), lead(), rank(), etc.

#### Priority 5: Complex Operations (Last)
- [ ] `Join.php` (3 methods) - inner(), left(), right()
- [ ] `Aggregator.php` (3 methods) - aggregate(), count(), sum()
- [ ] `Reshaper.php` (3 methods) - unpivot(), pivot(), transpose()
- [ ] `SetOperation.php` (3 methods) - concat(), union(), merge()

### Table.php Transformation Methods (5% Complete - ~40 methods)
- ✅ head(), tail(), slice(), skip() - Updated signatures
- ✅ sort(), sortBy(), sortByDesc() - Updated signatures
- [ ] ~33 remaining methods need updates

### Tests (0% Complete - ~30 files)
- Integration tests failing with TypeError (transformations return wrong format)
- Unit tests need updating for new signatures
- ~589 tests total, currently 74 failing

## Test Failure Summary

### Current Errors (74 failures)

1. **TypeError: Generator given instead of array** (50+ failures)
   - Transformations still returning `Generator` instead of `[headers, data]`
   - Affects: Joins, Reshaping, String ops, Deduplication, Conditional ops

2. **InvalidArgumentException: Field not found** (20+ failures)
   - Validator and Deduplicator still treating first row as header
   - Need to update these to accept headers parameter

## Implementation Strategy

### Step-by-Step Plan

1. **Start with RowSelector** (simplest)
   - Update all 4 methods
   - Update corresponding Table.php methods
   - Run tests - should fix ~10 failures

2. **RowSorter** (single method)
   - Quick win
   - Should fix sorting-related failures

3. **RowFilter** (15 methods but similar pattern)
   - Batch update all where* methods
   - Should fix filtering pipelines

4. **Deduplicator + Validator** (critical for tests)
   - These are causing "field not found" errors
   - Update to accept headers parameter
   - Should fix ~20 test failures

5. **Column operations** (moderate complexity)
   - ColumnSelector, ColumnRenamer, ColumnAdder
   - These modify headers - important pattern

6. **Value transformations** (tedious but straightforward)
   - ValueConverter, ValueReplacer, StringTransformer, ConditionalTransformer
   - All preserve headers, modify data

7. **Complex operations** (save for last)
   - Joins - complex header merging
   - Aggregator - generates new headers
   - Reshaper - transposes headers/data
   - SetOperation - merges multiple tables

### Refactoring Pattern

```php
// OLD
public static function transform(array $data, ...): iterable
{
    $header = $data[0];
    $rows = array_slice($data, 1);

    // transform logic

    yield $header;
    foreach ($transformed as $row) {
        yield $row;
    }
}

// NEW
public static function transform(array $headers, array $data, ...): array
{
    // transform logic (no header extraction needed)

    return [$headers, $transformed];
}
```

## Estimated Effort

- **17 transformation classes** × 30 min avg = **8.5 hours**
- **40 Table.php methods** × 2 min avg = **1.3 hours**
- **30 test files** × 15 min avg = **7.5 hours**
- **Testing & debugging** = **3 hours**
- **Total: ~20 hours** of focused work

## Quick Wins

Start here for fastest progress:

1. RowSelector (4 methods) - 30 min → fixes 5+ tests
2. RowSorter (1 method) - 10 min → fixes 3+ tests
3. Deduplicator (5 methods) - 45 min → fixes 15+ tests
4. Validator (2 methods) - 30 min → fixes 10+ tests

**2 hours of work → ~33 test failures fixed!**

## Documentation Updates Needed

After implementation complete:

1. **docs/DIFFERENCES_FROM_PETL.md**
   - Add "Header Separation" section
   - Explain design rationale
   - Show migration examples

2. **CHANGELOG.md**
   - Document breaking change
   - Migration guide for users

3. **README.md**
   - Update examples if needed
   - Mention header clarity benefit

## Benefits of This Refactoring

1. **Semantic Clarity** - Headers are metadata, not data
2. **count() Intuitive** - Returns number of data rows
3. **Better API** - Can swap headers easily
4. **Type Safety** - Can validate headers separately
5. **Future Features** - Enables named column access: `$table['column_name']`

## Files Modified So Far

### Core (2 files)
- `src/Contracts/ExtractorInterface.php`
- `src/Contracts/LoaderInterface.php`

### Extractors (6 files)
- `src/Extract/Extractors/ArrayExtractor.php`
- `src/Extract/Extractors/CsvExtractor.php`
- `src/Extract/Extractors/JsonExtractor.php`
- `src/Extract/Extractors/DatabaseExtractor.php`
- `src/Extract/Extractors/ExcelExtractor.php`
- `src/Extract/Extractors/RestApiExtractor.php`

### Loaders (4 files)
- `src/Load/Loaders/CsvLoader.php`
- `src/Load/Loaders/JsonLoader.php`
- `src/Load/Loaders/DatabaseLoader.php`
- `src/Load/Loaders/ExcelLoader.php`

### Table (1 file)
- `src/Table.php` (partially complete)

### Documentation (3 files)
- `docs/header-separation-design.md` (new)
- `docs/TRANSFORMATION_REFACTORING_GUIDE.md` (new)
- `docs/HEADER_SEPARATION_STATUS.md` (this file)

**Total: 16 files fully updated, 1 partially updated, 17 transformations remaining**
