# Future Enhancements & Discussion Topics

## RESTful API Extractor

**Priority**: High
**Status**: Planned

### Requirements
- Support for paginated API responses
- Authentication mechanisms (Bearer, OAuth, API Key)
- Rate limiting and retry logic
- Query parameter building
- Response transformation (JSON to tabular format)
- Error handling and logging

### Discussion Points
1. **Authentication Strategy**
   - How to handle different auth types?
   - Secure credential storage?
   - Token refresh mechanisms?

2. **Pagination Handling**
   - Support for common patterns (cursor, offset, page-based)
   - Auto-detection of pagination scheme?
   - Configurable vs. automatic?

3. **Response Mapping**
   - How to map nested JSON to flat tables?
   - Support for JSON path expressions?
   - Multiple tables from single endpoint?

4. **Configuration**
   ```php
   Table::fromRestApi('https://api.example.com/users', [
       'auth' => ['bearer' => $token],
       'pagination' => 'cursor', // or 'offset', 'page'
       'rate_limit' => 100, // requests per minute
       'mapping' => [
           'id' => 'data.user_id',
           'name' => 'data.full_name',
           'email' => 'data.contact.email',
       ],
   ]);
   ```

### Related Features
- GraphQL extractor
- WebSocket streaming extractor
- Webhook receiver

---

## Other Future Enhancements

### Performance
- [ ] Parallel processing for large datasets
- [ ] Async I/O operations
- [ ] In-memory caching strategies
- [ ] Query optimization for complex pipelines

### Extractors
- [ ] Parquet file support
- [ ] Avro format support
- [ ] Google Sheets integration
- [ ] LDAP directory extraction

### Loaders
- [ ] Streaming to cloud storage (S3, GCS, Azure)
- [ ] ElasticSearch bulk loading
- [ ] Message queue publishing (RabbitMQ, Kafka)

### Transformations
- [ ] Window functions (lead, lag, rank)
- [ ] Fuzzy matching for joins
- [ ] Machine learning feature engineering helpers
- [ ] Advanced date/time operations

### Developer Experience
- [ ] Interactive REPL for quick testing
- [ ] Visual pipeline builder/debugger
- [ ] Performance profiling tools
- [ ] Migration wizard from pandas/petl

---

**Note**: This document is for planning and discussion. Add comments and ideas as GitHub issues with the `enhancement` label.
