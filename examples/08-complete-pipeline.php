<?php

/**
 * Example 08: Complete ETL Pipeline
 *
 * This example demonstrates a realistic end-to-end ETL pipeline:
 * - Extract from multiple sources
 * - Clean and validate data
 * - Transform and enrich
 * - Join related data
 * - Aggregate for reporting
 * - Load to multiple destinations
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Complete ETL Pipeline ===\n";
echo "A realistic e-commerce data processing example\n\n";

$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// ============================================================================
// EXTRACT: Load data from multiple sources
// ============================================================================

echo "PHASE 1: EXTRACT\n";
echo str_repeat('=', 60) . "\n";

// Orders data (simulating database extract)
$orders = Table::fromArray([
    ['order_id', 'customer_id', 'order_date', 'status', 'shipping_method'],
    [1001, 'C001', '2024-01-15 10:30:00', 'completed', 'standard'],
    [1002, 'C002', '2024-01-15 14:22:00', 'completed', 'express'],
    [1003, 'C001', '2024-01-16 09:15:00', 'completed', 'standard'],
    [1004, 'C003', '2024-01-16 16:45:00', 'pending', 'standard'],
    [1005, 'C002', '2024-01-17 11:00:00', 'completed', 'express'],
    [1006, 'C004', '2024-01-17 13:30:00', 'cancelled', 'standard'],
    [1007, 'C005', '2024-01-18 08:00:00', 'completed', 'express'],
    [1008, 'C003', '2024-01-18 15:20:00', 'completed', 'standard'],
    [1009, 'C001', '2024-01-19 10:45:00', 'completed', 'standard'],
    [1010, 'C006', '2024-01-19 14:00:00', 'pending', 'express'],
]);

// Order items (line items)
$orderItems = Table::fromArray([
    ['order_id', 'product_id', 'quantity', 'unit_price'],
    [1001, 'P001', 2, 29.99],
    [1001, 'P002', 1, 49.99],
    [1002, 'P003', 3, 19.99],
    [1003, 'P001', 1, 29.99],
    [1003, 'P004', 2, 99.99],
    [1004, 'P002', 1, 49.99],
    [1005, 'P005', 1, 149.99],
    [1006, 'P001', 3, 29.99],
    [1007, 'P003', 2, 19.99],
    [1007, 'P004', 1, 99.99],
    [1008, 'P002', 2, 49.99],
    [1009, 'P005', 1, 149.99],
    [1009, 'P001', 1, 29.99],
    [1010, 'P003', 4, 19.99],
]);

// Customers (simulating CRM extract)
$customers = Table::fromArray([
    ['customer_id', 'name', 'email', 'city', 'country', 'member_since'],
    ['C001', 'Alice Johnson', 'alice@email.com', 'New York', 'USA', '2022-03-15'],
    ['C002', 'Bob Smith', 'bob@email.com', 'Los Angeles', 'USA', '2023-01-22'],
    ['C003', 'Carol White', 'carol@email.com', 'London', 'UK', '2022-08-10'],
    ['C004', 'David Brown', 'david@email.com', 'Toronto', 'Canada', '2023-06-01'],
    ['C005', 'Eve Davis', 'eve@email.com', 'Sydney', 'Australia', '2023-09-15'],
    ['C006', 'Frank Miller', 'frank@email.com', 'Berlin', 'Germany', '2024-01-05'],
]);

// Products (simulating inventory extract)
$products = Table::fromArray([
    ['product_id', 'name', 'category', 'cost', 'stock_quantity'],
    ['P001', 'Widget Pro', 'Electronics', 15.00, 500],
    ['P002', 'Gadget Plus', 'Electronics', 25.00, 300],
    ['P003', 'Accessory Kit', 'Accessories', 8.00, 1000],
    ['P004', 'Premium Bundle', 'Bundles', 50.00, 150],
    ['P005', 'Ultimate Package', 'Bundles', 75.00, 100],
]);

echo "Extracted data:\n";
echo "  - Orders: " . $orders->count() . " records\n";
echo "  - Order Items: " . $orderItems->count() . " records\n";
echo "  - Customers: " . $customers->count() . " records\n";
echo "  - Products: " . $products->count() . " records\n\n";

// ============================================================================
// TRANSFORM: Clean, validate, enrich, and join data
// ============================================================================

echo "PHASE 2: TRANSFORM\n";
echo str_repeat('=', 60) . "\n";

// Step 1: Filter completed orders only
echo "Step 1: Filter completed orders...\n";
$completedOrders = $orders->whereEquals('status', 'completed');
echo "  Completed orders: " . $completedOrders->count() . "\n";

// Step 2: Calculate line item totals
echo "Step 2: Calculate line item totals...\n";
$orderItemsEnriched = $orderItems
    ->addColumn('line_total', fn($row) => $row['quantity'] * $row['unit_price']);

// Step 3: Aggregate order items to order level
echo "Step 3: Aggregate to order totals...\n";
$orderTotals = $orderItemsEnriched->aggregate('order_id', [
    'item_count' => 'count',
    'total_quantity' => fn($rows) => array_sum(array_column($rows, 'quantity')),
    'order_total' => fn($rows) => array_sum(array_column($rows, 'line_total')),
]);

// Step 4: Join orders with totals
echo "Step 4: Join orders with totals...\n";
$ordersWithTotals = $completedOrders
    ->innerJoin($orderTotals, 'order_id')
    ->innerJoin($customers, 'customer_id')
    ->selectColumns(
        'order_id', 'customer_id', 'name', 'email', 'country',
        'order_date', 'shipping_method', 'item_count', 'total_quantity', 'order_total'
    );

echo "  Orders with totals: " . $ordersWithTotals->count() . " records\n";

// Step 5: Add date dimensions
echo "Step 5: Add date dimensions...\n";
$ordersEnriched = $ordersWithTotals
    ->addColumn('order_day', fn($row) => date('Y-m-d', strtotime($row['order_date'])))
    ->addColumn('order_weekday', fn($row) => date('l', strtotime($row['order_date'])))
    ->addColumn('order_hour', fn($row) => (int) date('H', strtotime($row['order_date'])));

// Step 6: Calculate customer tenure
echo "Step 6: Calculate customer metrics...\n";
$ordersEnriched = $ordersEnriched
    ->addColumn('order_size', fn($row) => match(true) {
        $row['order_total'] >= 200 => 'Large',
        $row['order_total'] >= 100 => 'Medium',
        default => 'Small',
    });

echo "\n";

// ============================================================================
// AGGREGATE: Create summary reports
// ============================================================================

echo "PHASE 3: AGGREGATE\n";
echo str_repeat('=', 60) . "\n";

// Report 1: Sales by Customer
echo "Creating Report 1: Customer Summary...\n";
$customerSummary = $ordersEnriched
    ->aggregate(['customer_id', 'name', 'email', 'country'], [
        'total_orders' => 'count',
        'total_spent' => fn($rows) => array_sum(array_column($rows, 'order_total')),
        'avg_order_value' => fn($rows) => 
            array_sum(array_column($rows, 'order_total')) / count($rows),
    ])
    ->sortByDesc('total_spent');

// Report 2: Sales by Country
echo "Creating Report 2: Country Summary...\n";
$countrySummary = $ordersEnriched
    ->aggregate('country', [
        'orders' => 'count',
        'revenue' => fn($rows) => array_sum(array_column($rows, 'order_total')),
        'unique_customers' => fn($rows) => 
            count(array_unique(array_column($rows, 'customer_id'))),
    ])
    ->sortByDesc('revenue');

// Report 3: Sales by Day
echo "Creating Report 3: Daily Summary...\n";
$dailySummary = $ordersEnriched
    ->aggregate('order_day', [
        'orders' => 'count',
        'revenue' => fn($rows) => array_sum(array_column($rows, 'order_total')),
        'items_sold' => fn($rows) => array_sum(array_column($rows, 'total_quantity')),
    ])
    ->sortBy('order_day');

// Report 4: Sales by Order Size
echo "Creating Report 4: Order Size Distribution...\n";
$sizeSummary = $ordersEnriched
    ->aggregate('order_size', [
        'count' => 'count',
        'total_revenue' => fn($rows) => array_sum(array_column($rows, 'order_total')),
        'avg_items' => fn($rows) => 
            array_sum(array_column($rows, 'total_quantity')) / count($rows),
    ]);

// Report 5: Product Performance
echo "Creating Report 5: Product Performance...\n";
$productPerformance = $orderItemsEnriched
    ->innerJoin($products, 'product_id')
    ->addColumn('profit', fn($row) => 
        ($row['unit_price'] - $row['cost']) * $row['quantity']
    )
    ->aggregate(['product_id', 'name', 'category'], [
        'units_sold' => fn($rows) => array_sum(array_column($rows, 'quantity')),
        'revenue' => fn($rows) => array_sum(array_column($rows, 'line_total')),
        'profit' => fn($rows) => array_sum(array_column($rows, 'profit')),
    ])
    ->sortByDesc('revenue');

echo "\n";

// ============================================================================
// LOAD: Export to multiple destinations
// ============================================================================

echo "PHASE 4: LOAD\n";
echo str_repeat('=', 60) . "\n";

// Load order details
$result1 = $ordersEnriched->toCsv($outputDir . '/order_details.csv');
echo "Order Details: " . $result1->rowCount() . " rows -> order_details.csv\n";

// Load customer summary
$result2 = $customerSummary->toCsv($outputDir . '/customer_summary.csv');
$customerSummary->toJson($outputDir . '/customer_summary.json', prettyPrint: true);
echo "Customer Summary: " . $result2->rowCount() . " rows -> CSV + JSON\n";

// Load country summary
$result3 = $countrySummary->toCsv($outputDir . '/country_summary.csv');
echo "Country Summary: " . $result3->rowCount() . " rows -> country_summary.csv\n";

// Load daily summary
$result4 = $dailySummary->toCsv($outputDir . '/daily_summary.csv');
echo "Daily Summary: " . $result4->rowCount() . " rows -> daily_summary.csv\n";

// Load product performance
$result5 = $productPerformance->toCsv($outputDir . '/product_performance.csv');
echo "Product Performance: " . $result5->rowCount() . " rows -> product_performance.csv\n";

echo "\n";

// ============================================================================
// SUMMARY: Display key metrics
// ============================================================================

echo "PIPELINE SUMMARY\n";
echo str_repeat('=', 60) . "\n";

// Calculate totals from the data
$orderData = $ordersEnriched->toArray();
array_shift($orderData); // Remove header

$totalRevenue = array_sum(array_column($orderData, 'order_total'));
$totalOrders = count($orderData);
$avgOrderValue = $totalRevenue / $totalOrders;
$uniqueCustomers = count(array_unique(array_column($orderData, 'customer_id')));

echo "Key Metrics:\n";
echo sprintf("  Total Revenue: \$%.2f\n", $totalRevenue);
echo sprintf("  Total Orders: %d\n", $totalOrders);
echo sprintf("  Average Order Value: \$%.2f\n", $avgOrderValue);
echo sprintf("  Unique Customers: %d\n", $uniqueCustomers);
echo sprintf("  Revenue per Customer: \$%.2f\n", $totalRevenue / $uniqueCustomers);
echo "\n";

echo "Top Customers by Revenue:\n";
foreach ($customerSummary->head(3)->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %d. %s - \$%.2f (%d orders)\n",
            $i, $row[1], $row[5], $row[4]);
    }
}
echo "\n";

echo "Top Products by Revenue:\n";
// Row format: product_id, name, category, units_sold, revenue, profit
foreach ($productPerformance->head(3)->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %d. %s - \$%.2f revenue, \$%.2f profit\n",
            $i, $row[1], $row[4], $row[5]);
    }
}
echo "\n";

echo "Files generated in: $outputDir\n";
echo "✓ Complete ETL pipeline finished!\n";
