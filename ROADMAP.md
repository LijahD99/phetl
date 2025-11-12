# Future Enhancements & Discussion Topics

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

## Other Future Enhancements

### Performance
- [ ] Parallel processing for large datasets
- [ ] Async I/O operations
- [ ] In-memory caching strategies
- [ ] Query optimization for complex pipelines

### Extractors
- [ ] Excel file support (.xlsx, .xls)
- [ ] Parquet file support
- [ ] Avro format support
- [ ] Google Sheets integration
- [ ] LDAP directory extraction

### Loaders
- [ ] Streaming to cloud storage (S3, GCS, Azure)
- [ ] ElasticSearch bulk loading
- [ ] Message queue publishing (RabbitMQ, Kafka)

### Transformations
- [x] Window functions (lead, lag, rank, denseRank, rowNumber, percentRank) - ✅ Completed
- [ ] Fuzzy matching for joins
- [ ] Machine learning feature engineering helpers
- [ ] Advanced date/time operations
- [ ] Geographic/spatial operations

### Developer Experience
- [ ] Interactive REPL for quick testing
- [ ] Visual pipeline builder/debugger
- [ ] Performance profiling tools
- [ ] Migration wizard from pandas/petl

---

**Note**: This document is for planning and discussion. Add comments and ideas as GitHub issues with the `enhancement` label.
