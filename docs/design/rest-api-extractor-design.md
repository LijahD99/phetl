# RestApiExtractor Design Document

## Overview
Extract tabular data from RESTful API endpoints with support for authentication, pagination, rate limiting, and response mapping.

## Configuration Array Structure

```php
[
    // Authentication (optional)
    'auth' => [
        'type' => 'bearer',      // or 'api_key', 'basic', 'none'
        'token' => 'xxx',        // for bearer
        'key' => 'xxx',          // for api_key
        'location' => 'header',  // or 'query' (for api_key)
        'header_name' => 'X-API-Key', // custom header name
        'username' => 'xxx',     // for basic auth
        'password' => 'xxx',     // for basic auth
    ],
    
    // Pagination (optional)
    'pagination' => [
        'type' => 'offset',      // or 'cursor', 'page', 'none'
        'page_size' => 100,
        'max_pages' => null,     // null = all pages
        
        // For offset pagination
        'offset_param' => 'offset',
        'limit_param' => 'limit',
        
        // For cursor pagination
        'cursor_param' => 'cursor',
        'cursor_path' => 'meta.next_cursor', // JSON path to next cursor
        
        // For page pagination
        'page_param' => 'page',
        'per_page_param' => 'per_page',
    ],
    
    // Rate limiting (optional)
    'rate_limit' => [
        'requests_per_minute' => 60,
        'retry_on_limit' => true,
        'max_retries' => 3,
        'backoff_factor' => 2,   // exponential: 1s, 2s, 4s
    ],
    
    // Response mapping (optional)
    'mapping' => [
        'data_path' => 'data',   // JSON path to array of records
        'fields' => [
            'id' => 'user_id',
            'name' => 'profile.full_name',
            'email' => 'contact.email',
        ],
    ],
    
    // HTTP options
    'headers' => [
        'Accept' => 'application/json',
        'User-Agent' => 'PHETL/1.0',
    ],
    'timeout' => 30,
    'verify_ssl' => true,
]
```

## Implementation Phases

### Phase 1: Basic HTTP GET (Start here)
- Simple GET request
- JSON response parsing
- Convert to tabular format
- Use PHP's `file_get_contents()` with stream context

### Phase 2: Authentication
- Bearer token (most common)
- API key (header or query param)
- Basic auth

### Phase 3: Pagination
- Offset-based (limit/offset)
- Cursor-based (next_cursor)
- Page-based (page number)

### Phase 4: Advanced Features
- Rate limiting with sleep/backoff
- Retry logic with exponential backoff
- Respect Retry-After headers
- JSON path mapping for nested data

## Constructor Signature

```php
public function __construct(
    private readonly string $url,
    private readonly array $config = []
)
```

## Default Behavior

If no config provided:
- No authentication
- No pagination (single request)
- No rate limiting
- Response should be JSON array of objects
- Fields extracted from first object

## Error Handling

- Invalid URL → InvalidArgumentException
- HTTP error (4xx, 5xx) → RuntimeException with status code
- Invalid JSON → InvalidArgumentException
- Empty response → return empty iterator
- Network errors → RuntimeException

## Testing Strategy

1. **Basic tests** (using mock HTTP responses)
   - Simple GET with JSON array
   - Empty response
   - Invalid JSON
   - HTTP errors

2. **Auth tests**
   - Bearer token in headers
   - API key in header
   - API key in query params

3. **Pagination tests**
   - Offset pagination (2 pages)
   - Cursor pagination (following next_cursor)
   - Max pages limit

4. **Rate limiting tests** (time-based, may skip)
   - Respect rate limits
   - Exponential backoff

5. **Integration tests** (may use httpbin.org or mock server)
   - Real HTTP requests
   - End-to-end workflows

## Dependencies

**Option 1: Use PHP built-in functions** (chosen for v1)
- `file_get_contents()` with stream context
- No external dependencies
- Simpler but less features

**Option 2: Add Guzzle/HTTP client** (future enhancement)
- More robust
- Better error handling
- Middleware support
- Requires new dependency

**Decision: Start with Option 1** to avoid adding dependencies, can enhance later.
