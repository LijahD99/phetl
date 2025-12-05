<?php

/**
 * Example 03: Data Transformations
 *
 * This example demonstrates the full range of data transformation capabilities:
 * - Row filtering operations
 * - Column operations
 * - Value conversions
 * - String manipulations
 * - Conditional transformations
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Data Transformations ===\n\n";

// Sample dataset for demonstrations
$products = Table::fromArray([
    ['sku', 'name', 'category', 'price', 'stock', 'active', 'description'],
    ['SKU001', 'laptop pro', 'Electronics', 1299.99, 50, true, 'High-performance laptop'],
    ['SKU002', 'wireless mouse', 'Electronics', 29.99, 200, true, 'Ergonomic wireless mouse'],
    ['SKU003', 'desk lamp', 'Office', 49.99, 0, false, 'LED desk lamp'],
    ['SKU004', 'notebook set', 'Office', 12.99, 500, true, 'Set of 5 notebooks'],
    ['SKU005', 'usb hub', 'Electronics', 39.99, 75, true, 'USB 3.0 hub with 7 ports'],
    ['SKU006', 'monitor stand', 'Office', 89.99, 30, true, 'Adjustable monitor stand'],
    ['SKU007', 'keyboard', 'Electronics', 149.99, 0, false, 'Mechanical keyboard'],
    ['SKU008', 'paper clips', 'Office', 3.99, 1000, true, 'Box of 100 paper clips'],
]);

echo "Starting with " . $products->count() . " products\n\n";

// ============================================================================
// 1. Row Filtering Operations
// ============================================================================

echo "1. Row Filtering Operations\n";
echo str_repeat('-', 50) . "\n";

// Filter active products
$active = $products->whereTrue('active');
echo "Active products: " . $active->count() . "\n";

// Filter in-stock products
$inStock = $products->whereGreaterThan('stock', 0);
echo "In-stock products: " . $inStock->count() . "\n";

// Filter electronics
$electronics = $products->whereEquals('category', 'Electronics');
echo "Electronics: " . $electronics->count() . "\n";

// Filter products in price range
$midRange = $products
    ->whereGreaterThanOrEqual('price', 25)
    ->whereLessThanOrEqual('price', 100);
echo "Mid-range products (\$25-\$100): " . $midRange->count() . "\n";

// Multiple category filter
$selectedCategories = $products->whereIn('category', ['Electronics', 'Office']);
echo "Electronics or Office: " . $selectedCategories->count() . "\n";

// Custom filter with closure
$affordable = $products->filter(fn($row) => 
    $row['price'] < 50 && $row['stock'] > 100
);
echo "Affordable & well-stocked: " . $affordable->count() . "\n\n";

// ============================================================================
// 2. Column Operations
// ============================================================================

echo "2. Column Operations\n";
echo str_repeat('-', 50) . "\n";

// Select specific columns
$simplified = $products->selectColumns('sku', 'name', 'price');
echo "Simplified columns: " . implode(', ', $simplified->header()) . "\n";

// Remove columns
$noDescription = $products->removeColumns('description', 'active');
echo "After removing: " . implode(', ', $noDescription->header()) . "\n";

// Rename columns
$renamed = $products->renameColumns([
    'sku' => 'product_id',
    'name' => 'product_name',
    'stock' => 'quantity',
]);
echo "Renamed columns: " . implode(', ', $renamed->header()) . "\n";

// Add computed columns
$withMetrics = $products
    ->addColumn('inventory_value', fn($row) => $row['price'] * $row['stock'])
    ->addColumn('price_tier', fn($row) => match(true) {
        $row['price'] >= 100 => 'Premium',
        $row['price'] >= 25 => 'Standard',
        default => 'Budget',
    });

echo "Added columns: inventory_value, price_tier\n";

// Add row numbers
$numbered = $products->addRowNumbers('row_num');
echo "Added row_num column\n\n";

// ============================================================================
// 3. Value Conversions
// ============================================================================

echo "3. Value Conversions\n";
echo str_repeat('-', 50) . "\n";

// Convert single field
$converted = $products->convert('price', fn($val) => round($val));
echo "Rounded prices\n";

// Convert multiple fields
$multiConverted = $products->convertMultiple([
    'price' => fn($val) => '$' . number_format($val, 2),
    'stock' => fn($val) => $val . ' units',
]);
echo "Formatted price and stock\n";

// Replace specific values
$replaced = $products->replace('active', false, 'Discontinued');
echo "Replaced inactive status\n";

// Replace all occurrences of a value
$noZeros = $products->replaceAll(0, 'Out of Stock');
echo "Replaced all zeros\n\n";

// ============================================================================
// 4. String Transformations
// ============================================================================

echo "4. String Transformations\n";
echo str_repeat('-', 50) . "\n";

// Uppercase
$upperNames = $products->upper('name');
echo "Uppercase names: " . $upperNames->look(2)[1][1] . "\n";

// Lowercase
$lowerCat = $products->lower('category');
echo "Lowercase category: " . $lowerCat->look(2)[1][2] . "\n";

// Trim whitespace (if there was any)
$trimmed = $products->trim('name');
echo "Trimmed names\n";

// Concatenate fields
$withFullDesc = $products->concatFields(
    'full_description',
    ['sku', 'name'],
    ' - '
);
echo "Created full_description from sku + name\n";

// Extract patterns
$skuNumbers = $products->extractPattern(
    'sku',
    'sku_number',
    '/SKU(\d+)/'
);
echo "Extracted SKU numbers\n\n";

// ============================================================================
// 5. Conditional Transformations
// ============================================================================

echo "5. Conditional Transformations\n";
echo str_repeat('-', 50) . "\n";

// When condition
$withLabel = $products->when(
    'stock',
    fn($stock) => $stock > 0,
    'availability',
    'In Stock',
    'Out of Stock'
);
echo "Added availability based on stock\n";

// Coalesce (first non-null)
$withDefaults = Table::fromArray([
    ['name', 'nickname', 'handle'],
    ['Alice', 'Ali', null],
    ['Bob', null, 'bobby'],
    ['Carol', null, null],
])->coalesce('display_name', ['nickname', 'handle', 'name']);
echo "Coalesced display_name from nickname/handle/name\n";

// ifNull - replace nulls with default
$withDefault = Table::fromArray([
    ['name', 'email'],
    ['Alice', 'alice@example.com'],
    ['Bob', null],
])->ifNull('email', 'safe_email', 'no-email@placeholder.com');
echo "Replaced null emails with placeholder\n";

// nullIf - set to null if condition met
$nullified = $products->nullIf(
    'stock',
    'clean_stock',
    fn($val) => $val === 0
);
echo "Set zero stock to null\n";

// Case statement
$withPriority = $products->case(
    'stock',
    'reorder_priority',
    [
        [fn($s) => $s === 0, 'Critical'],
        [fn($s) => $s < 50, 'High'],
        [fn($s) => $s < 100, 'Medium'],
    ],
    'Low'
);
echo "Added reorder_priority based on stock levels\n\n";

// ============================================================================
// 6. Sorting Operations
// ============================================================================

echo "6. Sorting Operations\n";
echo str_repeat('-', 50) . "\n";

// Sort ascending
$byName = $products->sortBy('name');
echo "Sorted by name ascending\n";

// Sort descending
$byPriceDesc = $products->sortByDesc('price');
echo "Sorted by price descending\n";

// Sort by multiple fields
$byCategory = $products->sortBy('category', 'name');
echo "Sorted by category, then name\n";

// Custom sort
$customSort = $products->sort(fn($a, $b) => 
    strlen($a['name']) <=> strlen($b['name'])
);
echo "Sorted by name length\n\n";

// ============================================================================
// 7. Deduplication Operations
// ============================================================================

echo "7. Deduplication Operations\n";
echo str_repeat('-', 50) . "\n";

$withDupes = Table::fromArray([
    ['category', 'item'],
    ['Electronics', 'Phone'],
    ['Electronics', 'Laptop'],
    ['Office', 'Desk'],
    ['Electronics', 'Phone'],  // Duplicate
    ['Office', 'Chair'],
]);

// Get distinct rows
$unique = $withDupes->distinct();
echo "Distinct rows: " . $unique->count() . " (from " . $withDupes->count() . ")\n";

// Find duplicates
$dupes = $withDupes->duplicates();
echo "Duplicate rows: " . $dupes->count() . "\n";

// Check uniqueness
$isUnique = $withDupes->isUnique();
echo "Is unique: " . ($isUnique ? 'Yes' : 'No') . "\n";

// Count distinct values
$counts = $withDupes->countDistinct('category');
echo "Category counts:\n";
foreach ($counts->toArray() as $i => $row) {
    if ($i > 0) {
        echo "  - {$row[0]}: {$row[1]}\n";
    }
}

echo "\n✓ Data transformations example completed!\n";
