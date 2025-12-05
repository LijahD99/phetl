# PHETL Roadmap

## Completed Phases

### Phase 5 ✅ - Window Functions & REST API
**Status**: ✅ Completed

### Phase 6 ✅ - Excel File Support
**Status**: ✅ Completed
- ExcelExtractor with PHPSpreadsheet 5.2.0
- ExcelLoader with sheet selection
- Table integration (fromExcel/toExcel)
- 35 tests, 80 assertions

---

## Current Phase

### Phase 7 🔄 - Performance Benchmarking Framework
**Priority**: High
**Status**: In Progress

Build comprehensive benchmarking infrastructure to establish performance baselines and identify optimization opportunities.

**Scope**:
- Benchmark harness for all major operations
- Memory profiling for large datasets (10K, 100K, 1M rows)
- Performance comparison across transformation types
- Automated benchmark reporting
- Document performance characteristics

**Deliverables**:
- `benchmarks/` directory with test suites
- Benchmark runner script
- Performance documentation
- Baseline metrics for future optimization

---

## Planned Phases

### Phase 8 - Parquet File Support
**Priority**: High
**Estimated Effort**: Medium

Add support for Apache Parquet columnar storage format for big data workflows.

**Why**:
- Modern columnar format essential for analytics
- Efficient compression and encoding
- Popular in Spark, pandas, dask ecosystems
- Differentiator (most PHP ETL libraries don't support it)

**Scope**:
- ParquetExtractor for reading .parquet files
- ParquetLoader for writing .parquet files
- Table integration (fromParquet/toParquet)
- ~40 tests expected
- Integration with Apache Arrow or similar

**Technical Approach**:
- Evaluate PHP Parquet libraries
- Consider FFI with Apache Arrow
- Fallback: exec parquet-tools CLI

---

### Phase 9 - REST API Enhancements
**Priority**: Medium
**Estimated Effort**: Small

Enhance existing REST API extractor with production-grade reliability features.

**Scope**:
- Rate limiting with configurable delays
- Retry logic with exponential backoff
- Request timeout configuration
- OAuth 2.0 token refresh support
- Custom HTTP headers support
- ~15-20 additional tests

**Why**: Makes REST API extractor production-ready for high-volume APIs

---

### Phase 10 - Cloud Storage Loaders
**Priority**: Medium
**Estimated Effort**: Large

Add support for cloud storage destinations for production deployments.

**Scope**:
- S3Loader for AWS (aws/aws-sdk-php)
- GCSLoader for Google Cloud (google/cloud-storage)
- AzureBlobLoader for Azure (microsoft/azure-storage-blob)
- Streaming uploads for large files
- ~30-40 tests (10-15 per loader)
- Configuration validation

**Why**: Enable cloud-native deployments and modern data pipelines

---

### Phase 11 - CLI Tool & Developer Experience
**Priority**: Low-Medium
**Estimated Effort**: Medium

Build command-line interface for improved developer experience.

**Scope**:
- Symfony Console application
- Commands: extract, transform, load, inspect, profile
- Interactive REPL mode
- Pipeline definition via YAML/JSON
- Progress indicators for long operations
- Colorized output

**Example Usage**:
```bash
phetl extract:csv data.csv | phetl transform:filter "age > 25" | phetl load:json output.json
phetl inspect data.csv --stats --preview=10
phetl repl  # Interactive mode
```

---

## RESTful API Extractor

**Priority**: High
**Status**: ✅ Completed (Phase 5)

### Implemented Features
- ✅ `Table::fromRestApi($url, $config)` factory method
- ✅ Authentication: Bearer tokens, API Key (header/query), Basic auth
- ✅ Pagination: offset/limit, cursor-based, page-based with max_pages
- ✅ Response mapping: Extract nested data with `data_path`, flatten with `fields` mapping
- ✅ Dot notation for nested JSON navigation (e.g., `'user.profile.email'`)
- ✅ Mock response system for reliable testing
- ✅ Config validation with helpful error messages
- ✅ 39 comprehensive tests (69 assertions)

### Usage Example
```php
Table::fromRestApi('https://api.example.com/users', [
    'auth' => [
        'type' => 'bearer',
        'token' => $token,
    ],
    'pagination' => [
        'type' => 'offset',  // or 'cursor', 'page'
        'page_size' => 50,
        'max_pages' => 10,
    ],
    'mapping' => [
        'data_path' => 'response.users',  // Extract nested array
        'fields' => [
            'id' => 'user_id',
            'name' => 'profile.full_name',
            'email' => 'contact.email',
        ],
    ],
]);
```

See `docs/rest-api-extractor-design.md` for complete documentation.

### Future Enhancements
- [ ] Rate limiting with configurable delays
- [ ] Retry logic with exponential backoff
- [ ] Request timeout configuration
- [ ] OAuth 2.0 token refresh
- [ ] Custom HTTP headers

### Related Features
- [ ] GraphQL extractor
- [ ] WebSocket streaming extractor
- [ ] Webhook receiver

---

### Future Enhancements (Post Phase 11)

#### Performance & Scalability
- [ ] Parallel processing for large datasets
- [ ] Async I/O operations with ReactPHP/Amp
- [ ] In-memory caching strategies
- [ ] Query optimization for complex pipelines
- [ ] Lazy evaluation improvements

#### Additional Extractors & Loaders
- [ ] XML file support (XmlExtractor/XmlLoader)
- [ ] Avro format support
- [ ] Google Sheets integration (via API)
- [ ] LDAP directory extraction
- [ ] ElasticSearch bulk loading
- [ ] Message queue publishing (RabbitMQ, Kafka)

#### Advanced Transformations
- [ ] Fuzzy matching for joins (Levenshtein distance)
- [ ] Machine learning feature engineering helpers
- [ ] Advanced date/time operations (business days, time zones)
- [ ] Geographic/spatial operations (distance, containment)
- [ ] Text analysis (tokenization, stemming, sentiment)

#### GraphQL & Real-time
- [ ] GraphQL extractor with query builder
- [ ] WebSocket streaming extractor
- [ ] Webhook receiver for event-driven ETL
- [ ] Server-Sent Events (SSE) support

#### Developer Experience
- [ ] Visual pipeline builder/debugger (web UI)
- [ ] Migration wizard from pandas/petl
- [ ] API reference generator (from docblocks)
- [ ] Video tutorials and screencasts
- [ ] Community cookbook of common patterns

---

**Note**: This document is for planning and discussion. Add comments and ideas as GitHub issues with the `enhancement` label.
