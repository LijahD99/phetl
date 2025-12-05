<?php

/**
 * Example 07: Reshaping Data (Pivot and Unpivot)
 *
 * This example demonstrates data reshaping operations:
 * - Unpivot (melt) - wide to long format
 * - Pivot - long to wide format
 * - Transpose - swap rows and columns
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Data Reshaping ===\n\n";

// ============================================================================
// 1. Unpivot (Melt) - Wide to Long Format
// ============================================================================

echo "1. Unpivot (Melt) - Wide to Long Format\n";
echo str_repeat('-', 50) . "\n";

// Wide format: One row per entity, multiple value columns
$salesWide = Table::fromArray([
    ['product', 'jan_sales', 'feb_sales', 'mar_sales'],
    ['Widget A', 1000, 1200, 1500],
    ['Widget B', 800, 900, 1100],
    ['Gadget X', 500, 600, 750],
]);

echo "Wide format (before unpivot):\n";
foreach ($salesWide->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// Unpivot to long format
$salesLong = $salesWide->unpivot(
    'product',                                    // ID field(s) to keep
    ['jan_sales', 'feb_sales', 'mar_sales'],     // Value fields to unpivot
    'month',                                      // Name for variable column
    'sales'                                       // Name for value column
);

echo "Long format (after unpivot):\n";
foreach ($salesLong->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// 2. Unpivot with Multiple ID Columns
// ============================================================================

echo "2. Unpivot with Multiple ID Columns\n";
echo str_repeat('-', 50) . "\n";

$regionalSales = Table::fromArray([
    ['region', 'product', 'q1', 'q2', 'q3', 'q4'],
    ['North', 'Widgets', 100, 150, 120, 180],
    ['South', 'Widgets', 80, 90, 110, 130],
    ['North', 'Gadgets', 50, 60, 70, 80],
]);

$longFormat = $regionalSales->unpivot(
    ['region', 'product'],  // Multiple ID fields
    null,                   // null = all remaining fields
    'quarter',
    'amount'
);

echo "Regional sales in long format:\n";
foreach ($longFormat->head(6)->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "  ... (" . $longFormat->count() . " rows total)\n\n";

// ============================================================================
// 3. Pivot - Long to Wide Format
// ============================================================================

echo "3. Pivot - Long to Wide Format\n";
echo str_repeat('-', 50) . "\n";

// Long format data
$longData = Table::fromArray([
    ['product', 'metric', 'value'],
    ['Widget A', 'price', 29.99],
    ['Widget A', 'stock', 100],
    ['Widget A', 'rating', 4.5],
    ['Widget B', 'price', 49.99],
    ['Widget B', 'stock', 75],
    ['Widget B', 'rating', 4.2],
]);

echo "Long format (before pivot):\n";
foreach ($longData->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// Pivot to wide format
$wideData = $longData->pivot(
    'product',    // Index field(s) - becomes row identifier
    'metric',     // Column field - values become column names
    'value'       // Value field - values fill the cells
);

echo "Wide format (after pivot):\n";
foreach ($wideData->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// 4. Pivot with Aggregation
// ============================================================================

echo "4. Pivot with Aggregation\n";
echo str_repeat('-', 50) . "\n";

// Sales data with multiple entries per combination
$salesData = Table::fromArray([
    ['region', 'category', 'amount'],
    ['North', 'Electronics', 500],
    ['North', 'Electronics', 300],
    ['North', 'Clothing', 200],
    ['South', 'Electronics', 400],
    ['South', 'Clothing', 350],
    ['South', 'Clothing', 150],
]);

echo "Sales data (multiple entries per region/category):\n";
foreach ($salesData->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// Pivot with sum aggregation
$pivoted = $salesData->pivot(
    'region',
    'category',
    'amount',
    'sum'  // Aggregate duplicates by summing
);

echo "Pivoted with SUM aggregation:\n";
foreach ($pivoted->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// 5. Transpose - Swap Rows and Columns
// ============================================================================

echo "5. Transpose - Swap Rows and Columns\n";
echo str_repeat('-', 50) . "\n";

$original = Table::fromArray([
    ['metric', 'product_a', 'product_b', 'product_c'],
    ['price', 29.99, 49.99, 19.99],
    ['stock', 100, 75, 200],
    ['rating', 4.5, 4.2, 4.8],
]);

echo "Original:\n";
foreach ($original->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

$transposed = $original->transpose();

echo "Transposed (rows and columns swapped):\n";
foreach ($transposed->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// 6. Real-World Example: Survey Data
// ============================================================================

echo "6. Real-World Example: Survey Responses\n";
echo str_repeat('-', 50) . "\n";

// Survey data in wide format
$surveyWide = Table::fromArray([
    ['respondent_id', 'q1_satisfaction', 'q2_recommend', 'q3_ease_of_use'],
    ['R001', 4, 5, 4],
    ['R002', 3, 4, 5],
    ['R003', 5, 5, 4],
    ['R004', 2, 3, 3],
]);

echo "Survey data (wide format):\n";
foreach ($surveyWide->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// Unpivot for analysis
$surveyLong = $surveyWide->unpivot(
    'respondent_id',
    null,
    'question',
    'score'
);

// Calculate average by question
$avgByQuestion = $surveyLong->aggregate('question', [
    'avg_score' => fn($rows) => array_sum(array_column($rows, 'score')) / count($rows),
    'response_count' => 'count',
]);

echo "Average scores by question:\n";
foreach ($avgByQuestion->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %-20s: %.2f (n=%d)\n", $row[0], $row[1], $row[2]);
    }
}
echo "\n";

// ============================================================================
// 7. Time Series Reshaping
// ============================================================================

echo "7. Time Series Reshaping\n";
echo str_repeat('-', 50) . "\n";

// Metrics over time in wide format
$timeSeriesWide = Table::fromArray([
    ['date', 'visitors', 'page_views', 'conversions'],
    ['2024-01-01', 1000, 5000, 50],
    ['2024-01-02', 1200, 6200, 62],
    ['2024-01-03', 950, 4800, 48],
]);

echo "Time series (wide):\n";
foreach ($timeSeriesWide->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// Unpivot for visualization-friendly format
$timeSeriesLong = $timeSeriesWide
    ->unpivot('date', null, 'metric', 'value')
    ->sortBy('date', 'metric');

echo "Time series (long - for charting):\n";
foreach ($timeSeriesLong->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}

echo "\n✓ Data reshaping example completed!\n";
