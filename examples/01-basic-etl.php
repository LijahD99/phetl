<?php

/**
 * Example 01: Basic ETL Operations
 *
 * This example demonstrates the fundamental Extract, Transform, Load pattern
 * using PHETL. You'll learn how to:
 * - Extract data from various sources
 * - Apply transformations
 * - Load data to destinations
 * - Check operation results
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Basic ETL Operations ===\n\n";

// ============================================================================
// EXTRACT: Getting Data Into PHETL
// ============================================================================

echo "1. EXTRACT - Loading Data\n";
echo str_repeat('-', 50) . "\n";

// Extract from a PHP array (most common for examples)
$salesData = Table::fromArray([
    ['product', 'category', 'price', 'quantity', 'date'],
    ['Widget A', 'Electronics', 29.99, 100, '2024-01-15'],
    ['Widget B', 'Electronics', 49.99, 75, '2024-01-16'],
    ['Gadget X', 'Accessories', 9.99, 200, '2024-01-15'],
    ['Gadget Y', 'Accessories', 14.99, 150, '2024-01-17'],
    ['Tool Z', 'Hardware', 39.99, 50, '2024-01-16'],
]);

echo "Extracted " . $salesData->count() . " rows from array\n";
echo "Columns: " . implode(', ', $salesData->header()) . "\n\n";

// Preview the data with look()
echo "Preview (first 3 rows):\n";
$preview = $salesData->look(3);
foreach ($preview as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// TRANSFORM: Manipulating Data
// ============================================================================

echo "2. TRANSFORM - Manipulating Data\n";
echo str_repeat('-', 50) . "\n";

// Apply multiple transformations in a pipeline
$transformed = $salesData
    // Add a computed column for total revenue
    ->addColumn('revenue', fn($row) => $row['price'] * $row['quantity'])
    
    // Filter to high-value products (revenue > 1000)
    ->filter(fn($row) => $row['revenue'] > 1000)
    
    // Sort by revenue descending
    ->sortByDesc('revenue')
    
    // Select only the columns we need
    ->selectColumns('product', 'category', 'revenue');

echo "Transformed data (" . $transformed->count() . " rows after filtering):\n";
foreach ($transformed->toArray() as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}
echo "\n";

// ============================================================================
// LOAD: Saving Data
// ============================================================================

echo "3. LOAD - Saving Data\n";
echo str_repeat('-', 50) . "\n";

// Ensure output directory exists
$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Load to CSV
$csvResult = $transformed->toCsv($outputDir . '/sales_summary.csv');
echo "CSV Export: " . ($csvResult->success() ? 'Success' : 'Failed') . "\n";
echo "  Rows written: " . $csvResult->rowCount() . "\n";

// Load to JSON
$jsonResult = $transformed->toJson($outputDir . '/sales_summary.json', prettyPrint: true);
echo "JSON Export: " . ($jsonResult->success() ? 'Success' : 'Failed') . "\n";
echo "  Rows written: " . $jsonResult->rowCount() . "\n";

// ============================================================================
// COMPLETE PIPELINE: End-to-End Example
// ============================================================================

echo "\n4. COMPLETE PIPELINE - End-to-End\n";
echo str_repeat('-', 50) . "\n";

// A complete ETL pipeline in one fluent chain
$result = Table::fromArray([
    ['name', 'email', 'age', 'department'],
    ['Alice Smith', 'alice@example.com', 28, 'Engineering'],
    ['Bob Jones', 'bob@example.com', 35, 'Sales'],
    ['Carol White', 'carol@example.com', 42, 'Engineering'],
    ['David Brown', null, 31, 'Marketing'],
    ['Eve Davis', 'eve@example.com', 26, 'Sales'],
])
    // Remove rows with null email
    ->whereNotNull('email')
    
    // Filter to age 30+
    ->whereGreaterThanOrEqual('age', 30)
    
    // Add a seniority column
    ->addColumn('seniority', fn($row) => $row['age'] >= 40 ? 'Senior' : 'Mid-level')
    
    // Rename columns for output
    ->renameColumns(['name' => 'full_name'])
    
    // Remove the age column
    ->removeColumns('age')
    
    // Sort alphabetically
    ->sortBy('full_name')
    
    // Export to CSV
    ->toCsv($outputDir . '/employees_filtered.csv');

echo "Pipeline complete!\n";
echo "  Success: " . ($result->success() ? 'Yes' : 'No') . "\n";
echo "  Rows exported: " . $result->rowCount() . "\n";

if ($result->hasWarnings()) {
    echo "  Warnings:\n";
    foreach ($result->warnings() as $warning) {
        echo "    - $warning\n";
    }
}

echo "\n✓ Basic ETL example completed!\n";
echo "Output files saved to: $outputDir\n";
