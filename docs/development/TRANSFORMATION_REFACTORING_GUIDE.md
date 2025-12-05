# Transformation Class Refactoring Guide

## Overview

This guide documents the refactoring needed to update all 17 transformation classes to work with the new header-separated architecture.

## Change Pattern

### Old Pattern
```php
public static function transform(array $data, ...): iterable
{
    // Extract header from first row
    $headers = $data[0];

    // Process data rows (skip header)
    $result = [$headers]; // Re-add header
    foreach (array_slice($data, 1) as $row) {
        // Transform row
        $result[] = $transformedRow;
    }

    return $result;
}
```

### New Pattern
```php
public static function transform(array $headers, array $data, ...): array
{
    // Headers provided separately - no need to extract

    // Process data rows directly (no header in $data)
    $result = [];
    foreach ($data as $row) {
        // Transform row
        $result[] = $transformedRow;
    }

    return [$headers, $result]; // Return tuple
}
```

## Transformation Classes To Update

### 1. Row Transformations (5 files)
- [ ] `src/Transform/Rows/RowSelector.php` - head(), tail(), slice(), skip()
- [ ] `src/Transform/Rows/RowSorter.php` - sort()
- [ ] `src/Transform/Rows/RowFilter.php` - filter(), whereEquals(), whereIn(), etc.
- [ ] `src/Transform/Rows/Deduplicator.php` - distinct(), duplicates(), countDistinct()
- [ ] `src/Transform/Rows/WindowFunctions.php` - lag(), lead(), rank(), etc.

### 2. Column Transformations (3 files)
- [ ] `src/Transform/Columns/ColumnSelector.php` - select(), remove()
- [ ] `src/Transform/Columns/ColumnRenamer.php` - rename()
- [ ] `src/Transform/Columns/ColumnAdder.php` - add(), addRowNumbers()

### 3. Value Transformations (4 files)
- [ ] `src/Transform/Values/ValueConverter.php` - convert(), convertMultiple()
- [ ] `src/Transform/Values/ValueReplacer.php` - replace(), replaceMap(), replaceAll()
- [ ] `src/Transform/Values/StringTransformer.php` - upper(), lower(), trim(), concat(), extract()
- [ ] `src/Transform/Values/ConditionalTransformer.php` - when(), coalesce(), nullIf(), ifNull(), case()

### 4. Complex Transformations (3 files)
- [ ] `src/Transform/Joins/Join.php` - inner(), left(), right()
- [ ] `src/Transform/Aggregation/Aggregator.php` - aggregate(), count(), sum()
- [ ] `src/Transform/Reshaping/Reshaper.php` - unpivot(), pivot(), transpose()

### 5. Set Operations (1 file)
- [ ] `src/Transform/Set/SetOperation.php` - concat(), union(), merge()

### 6. Validation (1 file)
- [ ] `src/Transform/Validation/Validator.php` - required(), validate()

## Table.php Method Updates

After updating transformation classes, update ~40 methods in Table.php:

```php
// OLD
public function head(int $limit): self
{
    return new self(RowSelector::head($this->materializedData, $limit));
}

// NEW
public function head(int $limit): self
{
    [$headers, $data] = RowSelector::head($this->headers, $this->materializedData, $limit);
    return new self($headers, $data);
}
```

## Key Considerations

### Header Mutations
Some transformations modify headers (column operations):
```php
// Column select - reduces headers
public static function select(array $headers, array $data, array $columns): array
{
    // Filter headers
    $newHeaders = array_values(array_intersect($headers, $columns));

    // Filter data columns
    $newData = /* filter columns from data */;

    return [$newHeaders, $newData];
}
```

### Header Preservation
Most transformations preserve headers unchanged:
```php
// Row filter - headers unchanged
public static function filter(array $headers, array $data, callable $predicate): array
{
    $filtered = array_filter($data, $predicate);
    return [$headers, array_values($filtered)];
}
```

### Header Generation
Some transformations create new headers (aggregation, reshaping):
```php
// Aggregation - generates new headers
public static function aggregate(array $headers, array $data, ...): array
{
    $newHeaders = ['group_field', 'count', 'sum', ...];
    $aggregatedData = /* compute aggregations */;

    return [$newHeaders, $aggregatedData];
}
```

## Testing Strategy

1. **Update transformation class** - change signature and implementation
2. **Update Table.php method** - pass headers + data, destructure result
3. **Run unit tests** - fix failing tests for that transformation
4. **Run integration tests** - ensure pipelines work
5. **Repeat for next transformation**

## Progress Tracking

Use this checklist to track progress through all transformations:

- [x] ExtractorInterface - return [headers, data]
- [x] LoaderInterface - accept (headers, data)
- [x] All 6 Extractors updated
- [x] All 4 Loaders updated
- [ ] RowSelector (4 methods)
- [ ] RowSorter (1 method)
- [ ] RowFilter (15+ methods)
- [ ] Deduplicator (5 methods)
- [ ] WindowFunctions (5+ methods)
- [ ] ColumnSelector (2 methods)
- [ ] ColumnRenamer (1 method)
- [ ] ColumnAdder (2 methods)
- [ ] ValueConverter (2 methods)
- [ ] ValueReplacer (3 methods)
- [ ] StringTransformer (5 methods)
- [ ] ConditionalTransformer (5 methods)
- [ ] Join (3 methods)
- [ ] Aggregator (3 methods)
- [ ] Reshaper (3 methods)
- [ ] SetOperation (3 methods)
- [ ] Validator (2 methods)
- [ ] All ~40 Table.php transformation methods
- [ ] All ~30 test files

## Estimated Scope

- **17 transformation classes** × ~3-5 methods each = **~60 method signatures**
- **40 Table.php methods** to update
- **30 test files** to update
- **Total**: ~130 locations to modify

## Next Steps

1. Start with simplest transformations (RowSelector, RowFilter)
2. Move to column operations
3. Handle complex operations (Joins, Aggregation, Reshaping) last
4. Update tests incrementally as each transformation is completed
5. Run full test suite at the end to verify all pipelines work
