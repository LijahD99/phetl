<?php

/**
 * Example 05: Join Operations
 *
 * This example demonstrates table joining capabilities:
 * - Inner joins
 * - Left joins
 * - Right joins
 * - Multi-key joins
 * - Common join patterns
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Join Operations ===\n\n";

// Create sample tables for joins
$orders = Table::fromArray([
    ['order_id', 'customer_id', 'product_id', 'quantity', 'order_date'],
    [1001, 1, 'P001', 2, '2024-01-15'],
    [1002, 2, 'P002', 1, '2024-01-16'],
    [1003, 1, 'P003', 3, '2024-01-16'],
    [1004, 3, 'P001', 1, '2024-01-17'],
    [1005, 4, 'P002', 2, '2024-01-17'],  // Customer 4 doesn't exist
    [1006, 2, 'P004', 1, '2024-01-18'],  // Product P004 doesn't exist
]);

$customers = Table::fromArray([
    ['id', 'name', 'email', 'city'],
    [1, 'Alice Johnson', 'alice@example.com', 'New York'],
    [2, 'Bob Smith', 'bob@example.com', 'Los Angeles'],
    [3, 'Carol Williams', 'carol@example.com', 'Chicago'],
    [5, 'Eve Davis', 'eve@example.com', 'Miami'],  // No orders
]);

$products = Table::fromArray([
    ['product_id', 'name', 'price', 'category'],
    ['P001', 'Widget A', 29.99, 'Electronics'],
    ['P002', 'Widget B', 49.99, 'Electronics'],
    ['P003', 'Gadget X', 19.99, 'Accessories'],
    ['P005', 'Tool Y', 79.99, 'Hardware'],  // No orders
]);

echo "Sample Data:\n";
echo "  Orders: " . $orders->count() . " records\n";
echo "  Customers: " . $customers->count() . " records\n";
echo "  Products: " . $products->count() . " records\n\n";

// Helper function to display table
function displayTable(Table $table, string $title = '', int $limit = 10): void
{
    if ($title) {
        echo "$title:\n";
    }
    
    $data = $table->look($limit);
    
    // Calculate column widths
    $widths = [];
    foreach ($data as $row) {
        foreach ($row as $i => $val) {
            $len = strlen((string) ($val ?? 'NULL'));
            $widths[$i] = max($widths[$i] ?? 0, $len);
        }
    }
    
    foreach ($data as $rowIndex => $row) {
        $formatted = [];
        foreach ($row as $i => $val) {
            $formatted[] = str_pad((string) ($val ?? 'NULL'), $widths[$i]);
        }
        echo "  " . implode(' | ', $formatted) . "\n";
        if ($rowIndex === 0) {
            echo "  " . str_repeat('-', array_sum($widths) + (count($widths) - 1) * 3) . "\n";
        }
    }
    echo "\n";
}

// ============================================================================
// 1. Inner Join
// ============================================================================

echo "1. Inner Join - Orders with Customers\n";
echo str_repeat('-', 50) . "\n";

$ordersWithCustomers = $orders->innerJoin(
    $customers,
    'customer_id',  // Left key
    'id'            // Right key
);

echo "Only orders where both order and customer exist:\n";
displayTable(
    $ordersWithCustomers->selectColumns('order_id', 'name', 'email', 'quantity'),
    ''
);

// ============================================================================
// 2. Left Join
// ============================================================================

echo "2. Left Join - All Orders with Customer Info\n";
echo str_repeat('-', 50) . "\n";

$allOrdersWithCustomers = $orders->leftJoin(
    $customers,
    'customer_id',
    'id'
);

echo "All orders (NULL for missing customers):\n";
displayTable(
    $allOrdersWithCustomers->selectColumns('order_id', 'customer_id', 'name', 'quantity'),
    ''
);

// ============================================================================
// 3. Right Join
// ============================================================================

echo "3. Right Join - All Customers with Their Orders\n";
echo str_repeat('-', 50) . "\n";

$allCustomersWithOrders = $orders->rightJoin(
    $customers,
    'customer_id',
    'id'
);

echo "All customers (NULL for those without orders):\n";
displayTable(
    $allCustomersWithOrders->selectColumns('order_id', 'name', 'email', 'quantity'),
    ''
);

// ============================================================================
// 4. Multiple Joins - Order Details
// ============================================================================

echo "4. Multiple Joins - Complete Order Details\n";
echo str_repeat('-', 50) . "\n";

$orderDetails = $orders
    ->leftJoin($customers, 'customer_id', 'id')
    ->leftJoin($products, 'product_id')
    ->addColumn('total', fn($row) => 
        isset($row['price']) ? $row['quantity'] * $row['price'] : null
    )
    ->selectColumns('order_id', 'name', 'product_id', 'quantity', 'price', 'total')
    ->renameColumns(['name' => 'customer_name']);

displayTable($orderDetails);

// ============================================================================
// 5. Same Key Name Join
// ============================================================================

echo "5. Same Key Name Join\n";
echo str_repeat('-', 50) . "\n";

// When join keys have the same name, you only need to specify once
$ordersWithProducts = $orders->innerJoin(
    $products,
    'product_id'  // Same name in both tables
);

echo "Orders joined with products on 'product_id':\n";
displayTable(
    $ordersWithProducts->selectColumns('order_id', 'name', 'price', 'quantity'),
    ''
);

// ============================================================================
// 6. Enriching Data with Lookups
// ============================================================================

echo "6. Enriching Data - Customer Order Summary\n";
echo str_repeat('-', 50) . "\n";

// First get order counts per customer
$orderCounts = $orders->countBy('customer_id');

// Join back to customers to enrich
$customerMetrics = $customers
    ->leftJoin($orderCounts, 'id', 'customer_id')
    ->renameColumns(['count' => 'order_count'])
    ->ifNull('order_count', 'order_count', 0)
    ->selectColumns('name', 'city', 'order_count')
    ->sortByDesc('order_count');

displayTable($customerMetrics, 'Customer Order Counts');

// ============================================================================
// 7. Complex Analysis with Joins
// ============================================================================

echo "7. Revenue Analysis by Customer and Category\n";
echo str_repeat('-', 50) . "\n";

// Join orders with products to get prices
$enrichedOrders = $orders
    ->innerJoin($products, 'product_id')
    ->innerJoin($customers, 'customer_id', 'id')
    ->addColumn('revenue', fn($row) => $row['quantity'] * $row['price']);

// Aggregate by customer
$customerRevenue = $enrichedOrders
    ->aggregate(['customer_id', 'name'], [
        'total_orders' => 'count',
        'total_revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
        'avg_order_value' => fn($rows) => 
            array_sum(array_column($rows, 'revenue')) / count($rows),
    ])
    ->removeColumns('customer_id')
    ->sortByDesc('total_revenue');

// Note: toArray() returns rows as indexed arrays matching column order
// For production code, consider using named keys or json_encode for output
foreach ($customerRevenue->toArray() as $i => $row) {
    if ($i === 0) {
        // Header row: name | total_orders | total_revenue | avg_order_value
        echo sprintf("  %-20s | %-6s | %-10s | %s\n", 
            $row[0], $row[1], $row[2], $row[3]);
        echo "  " . str_repeat('-', 55) . "\n";
    } else {
        echo sprintf("  %-20s | %-6d | \$%-9.2f | \$%.2f\n",
            $row[0], $row[1], $row[2], $row[3]);
    }
}
echo "\n";

// ============================================================================
// 8. Finding Unmatched Records
// ============================================================================

echo "8. Finding Unmatched Records\n";
echo str_repeat('-', 50) . "\n";

// Customers without orders
$customersWithoutOrders = $customers
    ->leftJoin($orders, 'id', 'customer_id')
    ->whereNull('order_id')
    ->selectColumns('id', 'name', 'email');

echo "Customers without orders:\n";
displayTable($customersWithoutOrders);

// Products never ordered
$productsNeverOrdered = $products
    ->leftJoin($orders, 'product_id')
    ->whereNull('order_id')
    ->selectColumns('product_id', 'name', 'category');

echo "Products never ordered:\n";
displayTable($productsNeverOrdered);

echo "✓ Join operations example completed!\n";
