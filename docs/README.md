# PHETL Documentation

Welcome to the PHETL documentation. This library provides powerful Extract, Transform, and Load (ETL) capabilities for PHP applications.

## User Documentation

- [Getting Started](getting-started.md) - Installation, quick start, and basic concepts
- [Migration from petl](DIFFERENCES_FROM_PETL.md) - For users familiar with Python's petl library

## Examples

The [examples/](../examples/) directory contains runnable code demonstrating:

| Example | Description |
|---------|-------------|
| [01-basic-etl.php](../examples/01-basic-etl.php) | Core ETL concepts: extract, transform, load |
| [02-csv-processing.php](../examples/02-csv-processing.php) | CSV file operations and transformations |
| [03-data-transformations.php](../examples/03-data-transformations.php) | Filtering, sorting, column operations |
| [04-aggregation-grouping.php](../examples/04-aggregation-grouping.php) | Grouping and aggregation functions |
| [05-join-operations.php](../examples/05-join-operations.php) | Inner, left, and right joins |
| [06-data-validation.php](../examples/06-data-validation.php) | Validating data quality |
| [07-reshaping-data.php](../examples/07-reshaping-data.php) | Pivot, unpivot, and transpose |
| [08-complete-pipeline.php](../examples/08-complete-pipeline.php) | End-to-end ETL workflow |
| [excel-operations.php](../examples/excel-operations.php) | Working with Excel files |
| [rest-api-extraction.php](../examples/rest-api-extraction.php) | Extracting data from REST APIs |

Run any example:
```bash
php examples/01-basic-etl.php
```

## API Reference

### Data Sources (Extractors)

| Method | Description |
|--------|-------------|
| `Table::fromArray($data)` | Load from PHP array |
| `Table::fromCsv($path)` | Load from CSV file |
| `Table::fromJson($path)` | Load from JSON file |
| `Table::fromExcel($path, $sheet)` | Load from Excel file |
| `Table::fromDatabase($pdo, $query)` | Load from database query |
| `Table::fromRestApi($url, $config)` | Load from REST API |

### Data Destinations (Loaders)

| Method | Returns |
|--------|---------|
| `->toArray()` | Array with header + data rows |
| `->toCsv($path)` | `LoadResult` |
| `->toJson($path)` | `LoadResult` |
| `->toExcel($path, $sheet)` | `LoadResult` |
| `->toDatabase($pdo, $table)` | `LoadResult` |

### Row Operations

| Method | Description |
|--------|-------------|
| `->head($n)` | First N rows |
| `->tail($n)` | Last N rows |
| `->skip($n)` | Skip first N rows |
| `->slice($start, $stop)` | Row range |
| `->filter($fn)` | Custom filter |
| `->whereEquals($field, $value)` | Equality filter |
| `->whereGreaterThan($field, $value)` | Comparison filter |
| `->whereLessThan($field, $value)` | Comparison filter |
| `->whereIn($field, $values)` | In-list filter |
| `->whereNull($field)` | Null check |
| `->whereNotNull($field)` | Not-null check |
| `->sort($key)` | Sort rows |
| `->sortBy(...$fields)` | Sort ascending |
| `->sortByDesc(...$fields)` | Sort descending |
| `->distinct($fields)` | Remove duplicates |
| `->duplicates($fields)` | Find duplicates |

### Column Operations

| Method | Description |
|--------|-------------|
| `->selectColumns(...$cols)` | Keep columns |
| `->removeColumns(...$cols)` | Drop columns |
| `->renameColumns($map)` | Rename columns |
| `->addColumn($name, $value)` | Add column |
| `->addRowNumbers($name)` | Add row numbers |

### Value Operations

| Method | Description |
|--------|-------------|
| `->convert($field, $fn)` | Transform values |
| `->replace($field, $old, $new)` | Replace value |
| `->replaceAll($old, $new)` | Replace everywhere |
| `->upper($field)` | Uppercase |
| `->lower($field)` | Lowercase |
| `->trim($field)` | Trim whitespace |

### Combining Tables

| Method | Description |
|--------|-------------|
| `->concat(...$tables)` | Stack vertically |
| `->union(...$tables)` | Stack + dedupe |
| `->merge(...$tables)` | Combine columns |
| `->innerJoin($table, $key)` | Inner join |
| `->leftJoin($table, $key)` | Left join |
| `->rightJoin($table, $key)` | Right join |

### Aggregation

| Method | Description |
|--------|-------------|
| `->aggregate($groupBy, $aggs)` | Group and aggregate |
| `->groupBy($field, $aggs)` | Alias for aggregate |
| `->countBy($field)` | Count by group |
| `->sumField($field, $groupBy)` | Sum by group |

### Reshaping

| Method | Description |
|--------|-------------|
| `->pivot($index, $column, $value)` | Long to wide |
| `->unpivot($id, $values)` | Wide to long |
| `->transpose()` | Swap rows/columns |

### Validation

| Method | Description |
|--------|-------------|
| `->validate($rules)` | Validate with rules |
| `->validateOrFail($rules)` | Validate or throw |
| `->filterValid($rules)` | Keep valid rows |
| `->filterInvalid($rules)` | Keep invalid rows |

## Development Documentation

For contributors and developers:

- [Development Guide](development/GETTING_STARTED_DEV.md) - Setting up development environment
- [Project Status](development/PROJECT_STATUS.md) - Current implementation status
- [Roadmap](development/ROADMAP.md) - Future plans and features

## Design Documents

Technical design documentation:

- [REST API Extractor Design](design/rest-api-extractor-design.md)
- [Header Separation Design](design/header-separation-design.md)
- [Testing & Logging Framework Design](design/testing-logging-design.md)
