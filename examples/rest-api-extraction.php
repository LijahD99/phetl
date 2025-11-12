<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

/**
 * REST API Extraction Examples
 *
 * This file demonstrates various ways to extract data from RESTful APIs
 * using the RestApiExtractor. All examples use mock responses for demonstration.
 */

echo "=== REST API Extraction Examples ===\n\n";

// Example 1: Basic API extraction
echo "1. Basic API Extraction\n";
echo str_repeat('-', 50) . "\n";

$users = Table::fromRestApi('https://api.example.com/users', [
    '_mock_response' => json_encode([
        ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
        ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
    ]),
]);

echo "Users:\n";
foreach ($users->head(4) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 2: API with Bearer Token Authentication
echo "2. Bearer Token Authentication\n";
echo str_repeat('-', 50) . "\n";

$secureData = Table::fromRestApi('https://api.example.com/secure/data', [
    '_mock_response' => json_encode([
        ['id' => 1, 'value' => 100],
        ['id' => 2, 'value' => 200],
    ]),
    'auth' => [
        'type' => 'bearer',
        'token' => 'your-secret-token-here',
    ],
]);

echo "Authenticated data:\n";
foreach ($secureData->head(3) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 3: API Key Authentication (Header)
echo "3. API Key Authentication (Header)\n";
echo str_repeat('-', 50) . "\n";

$apiData = Table::fromRestApi('https://api.example.com/data', [
    '_mock_response' => json_encode([
        ['product' => 'Widget', 'price' => 9.99],
        ['product' => 'Gadget', 'price' => 14.99],
    ]),
    'auth' => [
        'type' => 'api_key',
        'key' => 'your-api-key',
        'location' => 'header',
        'header_name' => 'X-API-Key',
    ],
]);

echo "Product data:\n";
foreach ($apiData->head(3) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 4: Basic Authentication
echo "4. Basic Authentication\n";
echo str_repeat('-', 50) . "\n";

$basicAuth = Table::fromRestApi('https://api.example.com/protected', [
    '_mock_response' => json_encode([
        ['resource' => 'Resource A', 'status' => 'active'],
        ['resource' => 'Resource B', 'status' => 'inactive'],
    ]),
    'auth' => [
        'type' => 'basic',
        'username' => 'admin',
        'password' => 'secret',
    ],
]);

echo "Protected resources:\n";
foreach ($basicAuth->head(3) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 5: Paginated API (Offset-based)
echo "5. Offset-based Pagination\n";
echo str_repeat('-', 50) . "\n";

$paginatedOffset = Table::fromRestApi('https://api.example.com/products', [
    '_mock_responses' => [
        json_encode([
            ['id' => 1, 'name' => 'Product 1'],
            ['id' => 2, 'name' => 'Product 2'],
        ]),
        json_encode([
            ['id' => 3, 'name' => 'Product 3'],
            ['id' => 4, 'name' => 'Product 4'],
        ]),
        json_encode([]), // Empty response signals end
    ],
    'pagination' => [
        'type' => 'offset',
        'page_size' => 2,
    ],
]);

echo "All products (paginated):\n";
foreach ($paginatedOffset->head(5) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 6: Cursor-based Pagination
echo "6. Cursor-based Pagination\n";
echo str_repeat('-', 50) . "\n";

$paginatedCursor = Table::fromRestApi('https://api.example.com/posts', [
    '_mock_responses' => [
        json_encode([
            ['id' => 1, 'title' => 'First Post'],
            ['id' => 2, 'title' => 'Second Post'],
        ]),
        json_encode([
            ['id' => 3, 'title' => 'Third Post'],
        ]),
        json_encode([]), // Empty signals end
    ],
    'pagination' => [
        'type' => 'cursor',
        'page_size' => 2,
    ],
]);

echo "All posts (cursor pagination):\n";
foreach ($paginatedCursor->head(4) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 7: Page-based Pagination with Limit
echo "7. Page-based Pagination (max 2 pages)\n";
echo str_repeat('-', 50) . "\n";

$paginatedPage = Table::fromRestApi('https://api.example.com/items', [
    '_mock_responses' => [
        json_encode([['id' => 1], ['id' => 2]]),
        json_encode([['id' => 3], ['id' => 4]]),
        json_encode([['id' => 5], ['id' => 6]]), // Won't be fetched due to max_pages
    ],
    'pagination' => [
        'type' => 'page',
        'page_size' => 2,
        'max_pages' => 2, // Stop after 2 pages
    ],
]);

echo "Items (max 2 pages):\n";
foreach ($paginatedPage->head(5) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 8: Nested JSON Response (data_path extraction)
echo "8. Extracting Nested Data\n";
echo str_repeat('-', 50) . "\n";

$nested = Table::fromRestApi('https://api.example.com/response', [
    '_mock_response' => json_encode([
        'status' => 'success',
        'response' => [
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ],
        ],
    ]),
    'mapping' => [
        'data_path' => 'response.users',
    ],
]);

echo "Nested users:\n";
foreach ($nested->head(3) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 9: Field Mapping (Flattening Nested Fields)
echo "9. Field Mapping (Flattening)\n";
echo str_repeat('-', 50) . "\n";

$mapped = Table::fromRestApi('https://api.example.com/employees', [
    '_mock_response' => json_encode([
        [
            'employee_id' => 101,
            'personal' => [
                'first_name' => 'Alice',
                'last_name' => 'Smith',
            ],
            'contact' => [
                'email' => 'alice@company.com',
            ],
        ],
        [
            'employee_id' => 102,
            'personal' => [
                'first_name' => 'Bob',
                'last_name' => 'Jones',
            ],
            'contact' => [
                'email' => 'bob@company.com',
            ],
        ],
    ]),
    'mapping' => [
        'fields' => [
            'id' => 'employee_id',
            'first_name' => 'personal.first_name',
            'last_name' => 'personal.last_name',
            'email' => 'contact.email',
        ],
    ],
]);

echo "Flattened employee data:\n";
foreach ($mapped->head(3) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 10: Combined - Pagination + Mapping + Authentication
echo "10. Complete Example (Pagination + Mapping + Auth)\n";
echo str_repeat('-', 50) . "\n";

$complete = Table::fromRestApi('https://api.example.com/customers', [
    '_mock_responses' => [
        json_encode([
            [
                'customer_id' => 1,
                'profile' => ['name' => 'Alice', 'tier' => 'gold'],
                'stats' => ['orders' => 25, 'total_spent' => 1250.00],
            ],
            [
                'customer_id' => 2,
                'profile' => ['name' => 'Bob', 'tier' => 'silver'],
                'stats' => ['orders' => 10, 'total_spent' => 450.00],
            ],
        ]),
        json_encode([
            [
                'customer_id' => 3,
                'profile' => ['name' => 'Charlie', 'tier' => 'bronze'],
                'stats' => ['orders' => 5, 'total_spent' => 150.00],
            ],
        ]),
        json_encode([]),
    ],
    'auth' => [
        'type' => 'bearer',
        'token' => 'production-api-token',
    ],
    'pagination' => [
        'type' => 'offset',
        'page_size' => 2,
    ],
    'mapping' => [
        'fields' => [
            'id' => 'customer_id',
            'name' => 'profile.name',
            'tier' => 'profile.tier',
            'orders' => 'stats.orders',
            'revenue' => 'stats.total_spent',
        ],
    ],
]);

echo "Customer analytics:\n";
foreach ($complete->head(4) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

// Example 11: Chaining transformations after API extraction
echo "11. Transformations After API Extraction\n";
echo str_repeat('-', 50) . "\n";

$transformed = Table::fromRestApi('https://api.example.com/sales', [
    '_mock_response' => json_encode([
        ['product' => 'Widget', 'quantity' => 5, 'price' => 10.00],
        ['product' => 'Gadget', 'quantity' => 3, 'price' => 15.00],
        ['product' => 'Widget', 'quantity' => 2, 'price' => 10.00],
        ['product' => 'Doohickey', 'quantity' => 1, 'price' => 25.00],
    ]),
])
    ->addColumn('total', fn($row) => $row['quantity'] * $row['price'])
    ->whereGreaterThan('total', 20)
    ->sortByDesc('total');

echo "Sales over $20 (sorted):\n";
foreach ($transformed->head(4) as $row) {
    echo implode(', ', $row) . "\n";
}
echo "\n";

echo "=== Examples Complete ===\n";

/**
 * Notes:
 *
 * 1. The '_mock_response' and '_mock_responses' keys are used for testing
 *    and demonstration. In production, the RestApiExtractor will make actual
 *    HTTP requests using PHP's file_get_contents().
 *
 * 2. For real API calls, remove the mock keys and ensure your environment
 *    has internet access and the API endpoints are reachable.
 *
 * 3. Authentication tokens, API keys, and passwords should be stored securely
 *    (e.g., environment variables) and never committed to version control.
 *
 * 4. See docs/rest-api-extractor-design.md for complete configuration options
 *    and detailed documentation.
 */
