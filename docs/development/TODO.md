# TODO

## Next Features (Phase 5)

### High Priority
- [ ] RESTful API Extractor
  - HTTP client with authentication support
  - JSON response parsing
  - Pagination handling
  - Rate limiting support

### Medium Priority
- [ ] Additional I/O Formats
  - Excel reader/writer (PhpSpreadsheet integration)
  - Parquet support for big data workflows

### Performance Optimizations
- [ ] Benchmark framework for transformation operations
- [ ] Memory profiling for large datasets
- [ ] Parallel processing for independent operations

### Window Functions Refinement
- [ ] Fix partition row-order preservation (3 pending tests)
  - Currently groups by partition, need to maintain original row sequence
  - Requires stateful iteration tracking

## Development Tools
- [ ] CLI tool for common ETL operations
- [ ] REPL for interactive data exploration
- [ ] Visual query builder (web-based)

## Documentation
- [ ] API reference generator
- [ ] More real-world examples
- [ ] Performance tuning guide
- [ ] Migration guide from petl (Python)

---

**All core placeholder files have been implemented.**
**All Phase 1-4 features completed with comprehensive tests.**
**508 tests, 1,014 assertions, all passing (3 pending refinements).**

