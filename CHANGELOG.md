# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Complete ETL library with fluent API
- **Extractors**: Array, CSV, JSON, Excel, Database, REST API
- **Loaders**: CSV, JSON, Excel, Database with LoadResult
- **Row Operations**: filter, sort, head, tail, slice, distinct
- **Column Operations**: select, remove, rename, add
- **Value Operations**: convert, replace, string transforms
- **Joins**: inner, left, right joins with multi-key support
- **Aggregation**: group by, count, sum, custom aggregations
- **Reshaping**: pivot, unpivot/melt, transpose
- **Validation**: required, type, range, pattern, custom rules
- **Set Operations**: concat, union, merge
- Comprehensive test suite (589 tests, 1,145 assertions)
- PSR-12 compliant code style
- PHPStan max level static analysis
- Comprehensive documentation and examples
