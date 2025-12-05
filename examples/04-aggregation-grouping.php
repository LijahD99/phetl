<?php

/**
 * Example 04: Aggregation and Grouping
 *
 * This example demonstrates aggregation capabilities:
 * - Grouping data by one or more fields
 * - Built-in aggregation functions
 * - Custom aggregation logic
 * - Statistical calculations
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Aggregation and Grouping ===\n\n";

// Sample sales data
$sales = Table::fromArray([
    ['date', 'region', 'product', 'category', 'quantity', 'unit_price', 'salesperson'],
    ['2024-01-15', 'North', 'Widget A', 'Electronics', 10, 29.99, 'Alice'],
    ['2024-01-15', 'South', 'Widget B', 'Electronics', 5, 49.99, 'Bob'],
    ['2024-01-16', 'North', 'Gadget X', 'Accessories', 20, 9.99, 'Alice'],
    ['2024-01-16', 'East', 'Widget A', 'Electronics', 8, 29.99, 'Carol'],
    ['2024-01-17', 'North', 'Widget A', 'Electronics', 15, 29.99, 'Alice'],
    ['2024-01-17', 'South', 'Gadget Y', 'Accessories', 25, 14.99, 'Bob'],
    ['2024-01-17', 'West', 'Widget B', 'Electronics', 12, 49.99, 'David'],
    ['2024-01-18', 'East', 'Gadget X', 'Accessories', 30, 9.99, 'Carol'],
    ['2024-01-18', 'North', 'Widget B', 'Electronics', 7, 49.99, 'Alice'],
    ['2024-01-18', 'South', 'Widget A', 'Electronics', 18, 29.99, 'Bob'],
]);

// Add revenue column for aggregations
$sales = $sales->addColumn('revenue', fn($row) => $row['quantity'] * $row['unit_price']);

echo "Sales data: " . $sales->count() . " transactions\n\n";

// ============================================================================
// 1. Simple Grouping with Count
// ============================================================================

echo "1. Simple Grouping - Count by Region\n";
echo str_repeat('-', 50) . "\n";

$byRegion = $sales->countBy('region');

foreach ($byRegion->toArray() as $i => $row) {
    if ($i === 0) {
        echo sprintf("  %-10s | %s\n", $row[0], $row[1]);
        echo "  " . str_repeat('-', 20) . "\n";
    } else {
        echo sprintf("  %-10s | %d\n", $row[0], $row[1]);
    }
}
echo "\n";

// ============================================================================
// 2. Grouping with Multiple Aggregations
// ============================================================================

echo "2. Multiple Aggregations by Region\n";
echo str_repeat('-', 50) . "\n";

$regionSummary = $sales->aggregate('region', [
    'transactions' => 'count',
    'total_quantity' => fn($rows) => array_sum(array_column($rows, 'quantity')),
    'total_revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
    'avg_order_value' => fn($rows) => 
        array_sum(array_column($rows, 'revenue')) / count($rows),
]);

foreach ($regionSummary->sortByDesc('total_revenue')->toArray() as $i => $row) {
    if ($i === 0) {
        echo sprintf("  %-8s | %-5s | %-8s | %-12s | %s\n", 
            $row[0], $row[1], $row[2], $row[3], $row[4]);
        echo "  " . str_repeat('-', 60) . "\n";
    } else {
        echo sprintf("  %-8s | %-5d | %-8d | \$%-11s | \$%.2f\n",
            $row[0], $row[1], $row[2], number_format($row[3], 2), $row[4]);
    }
}
echo "\n";

// ============================================================================
// 3. Grouping by Multiple Fields
// ============================================================================

echo "3. Grouping by Region and Category\n";
echo str_repeat('-', 50) . "\n";

$regionCategory = $sales->aggregate(['region', 'category'], [
    'count' => 'count',
    'revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
]);

foreach ($regionCategory->sortBy('region', 'category')->toArray() as $i => $row) {
    if ($i === 0) {
        echo sprintf("  %-8s | %-12s | %-5s | %s\n", 
            $row[0], $row[1], $row[2], $row[3]);
        echo "  " . str_repeat('-', 45) . "\n";
    } else {
        echo sprintf("  %-8s | %-12s | %-5d | \$%.2f\n",
            $row[0], $row[1], $row[2], $row[3]);
    }
}
echo "\n";

// ============================================================================
// 4. Statistical Aggregations
// ============================================================================

echo "4. Statistical Aggregations\n";
echo str_repeat('-', 50) . "\n";

$productStats = $sales->aggregate('product', [
    'count' => 'count',
    'total_qty' => fn($rows) => array_sum(array_column($rows, 'quantity')),
    'min_qty' => fn($rows) => min(array_column($rows, 'quantity')),
    'max_qty' => fn($rows) => max(array_column($rows, 'quantity')),
    'avg_qty' => fn($rows) => array_sum(array_column($rows, 'quantity')) / count($rows),
    'total_revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
]);

echo "Product Statistics:\n";
foreach ($productStats->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %s:\n", $row[0]);
        echo sprintf("    Sales: %d | Total Qty: %d | Min: %d | Max: %d | Avg: %.1f\n",
            $row[1], $row[2], $row[3], $row[4], $row[5]);
        echo sprintf("    Total Revenue: \$%.2f\n", $row[6]);
    }
}
echo "\n";

// ============================================================================
// 5. Salesperson Performance
// ============================================================================

echo "5. Salesperson Performance\n";
echo str_repeat('-', 50) . "\n";

$performance = $sales->aggregate('salesperson', [
    'sales_count' => 'count',
    'total_revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
    'avg_sale' => fn($rows) => 
        array_sum(array_column($rows, 'revenue')) / count($rows),
    'products_sold' => fn($rows) => 
        count(array_unique(array_column($rows, 'product'))),
])->sortByDesc('total_revenue');

echo "Performance Ranking:\n";
$rank = 1;
foreach ($performance->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  #%d %s - \$%.2f revenue (%d sales, avg \$%.2f)\n",
            $rank++, $row[0], $row[1], $row[2], $row[3]);
    }
}
echo "\n";

// ============================================================================
// 6. Time-based Aggregation
// ============================================================================

echo "6. Daily Sales Summary\n";
echo str_repeat('-', 50) . "\n";

$dailySales = $sales->aggregate('date', [
    'transactions' => 'count',
    'total_revenue' => fn($rows) => array_sum(array_column($rows, 'revenue')),
    'unique_customers' => fn($rows) => 
        count(array_unique(array_column($rows, 'salesperson'))),
])->sortBy('date');

foreach ($dailySales->toArray() as $i => $row) {
    if ($i === 0) {
        echo sprintf("  %-12s | %-12s | %-12s | %s\n", 
            $row[0], $row[1], $row[2], $row[3]);
        echo "  " . str_repeat('-', 55) . "\n";
    } else {
        echo sprintf("  %-12s | %-12d | \$%-11.2f | %d\n",
            $row[0], $row[1], $row[2], $row[3]);
    }
}
echo "\n";

// ============================================================================
// 7. Sum Field Shortcut
// ============================================================================

echo "7. Sum Field Shortcut\n";
echo str_repeat('-', 50) . "\n";

// Sum revenue by category
$categoryRevenue = $sales->sumField('revenue', 'category');
echo "Revenue by Category:\n";
foreach ($categoryRevenue->sortByDesc('sum')->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %-15s: \$%.2f\n", $row[0], $row[1]);
    }
}
echo "\n";

// ============================================================================
// 8. Grand Totals
// ============================================================================

echo "8. Grand Totals\n";
echo str_repeat('-', 50) . "\n";

// Calculate overall statistics
$data = $sales->toArray();
array_shift($data); // Remove header

$totalRevenue = array_sum(array_column($data, 'revenue'));
$totalQuantity = array_sum(array_column($data, 'quantity'));
$avgOrderValue = $totalRevenue / count($data);
$uniqueProducts = count(array_unique(array_column($data, 'product')));

echo "Summary Statistics:\n";
echo sprintf("  Total Transactions: %d\n", count($data));
echo sprintf("  Total Revenue: \$%.2f\n", $totalRevenue);
echo sprintf("  Total Units Sold: %d\n", $totalQuantity);
echo sprintf("  Average Order Value: \$%.2f\n", $avgOrderValue);
echo sprintf("  Unique Products: %d\n", $uniqueProducts);

echo "\n✓ Aggregation example completed!\n";
