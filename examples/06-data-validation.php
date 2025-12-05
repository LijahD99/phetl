<?php

/**
 * Example 06: Data Validation
 *
 * This example demonstrates data validation capabilities:
 * - Required field validation
 * - Type validation
 * - Range validation
 * - Pattern matching
 * - Custom validation rules
 * - Filtering valid/invalid rows
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL Data Validation ===\n\n";

// Sample data with various issues
$userData = Table::fromArray([
    ['id', 'name', 'email', 'age', 'status', 'phone'],
    [1, 'Alice Johnson', 'alice@example.com', 28, 'active', '555-1234'],
    [2, '', 'bob@example.com', 35, 'active', '555-5678'],           // Empty name
    [3, 'Carol Williams', 'invalid-email', 22, 'active', '555-9012'], // Invalid email
    [4, 'David Brown', 'david@example.com', 17, 'active', '555-3456'], // Under 18
    [5, 'Eve Davis', null, 45, 'active', '555-7890'],                 // Null email
    [6, 'Frank Miller', 'frank@example.com', 30, 'invalid', '555-1111'], // Bad status
    [7, 'Grace Lee', 'grace@example.com', 150, 'active', '555-2222'],    // Invalid age
    [8, 'Henry Wilson', 'henry@example.com', 25, 'active', 'bad-phone'], // Invalid phone
]);

echo "Sample data: " . $userData->count() . " records\n\n";

// ============================================================================
// 1. Required Field Validation
// ============================================================================

echo "1. Required Field Validation\n";
echo str_repeat('-', 50) . "\n";

$requiredResult = $userData->validateRequired(['name', 'email']);

echo "Checking required fields (name, email):\n";
echo "  Valid: " . ($requiredResult['valid'] ? 'Yes' : 'No') . "\n";
echo "  Errors: " . count($requiredResult['errors']) . "\n";

foreach ($requiredResult['errors'] as $error) {
    echo sprintf("    Row %d: %s - %s\n", 
        $error['row'], $error['field'], $error['message']);
}
echo "\n";

// ============================================================================
// 2. Comprehensive Validation Rules
// ============================================================================

echo "2. Comprehensive Validation\n";
echo str_repeat('-', 50) . "\n";

$validationResult = $userData->validate([
    'name' => ['required'],
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', ['range', 18, 120]],
    'status' => ['required', ['in', ['active', 'inactive', 'pending']]],
    'phone' => [['pattern', '/^\d{3}-\d{4}$/']],
]);

echo "Validation Summary:\n";
echo "  Overall Valid: " . ($validationResult['valid'] ? 'Yes' : 'No') . "\n";
echo "  Total Errors: " . count($validationResult['errors']) . "\n\n";

// Group errors by row
$errorsByRow = [];
foreach ($validationResult['errors'] as $error) {
    $errorsByRow[$error['row']][] = $error;
}

echo "Errors by Row:\n";
foreach ($errorsByRow as $rowNum => $errors) {
    echo "  Row $rowNum:\n";
    foreach ($errors as $error) {
        echo sprintf("    - %s: %s\n", $error['field'], $error['message']);
    }
}
echo "\n";

// ============================================================================
// 3. Filtering Valid Rows
// ============================================================================

echo "3. Filtering Valid Rows\n";
echo str_repeat('-', 50) . "\n";

$validRows = $userData->filterValid([
    'name' => ['required'],
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', ['range', 18, 120]],
    'status' => [['in', ['active', 'inactive', 'pending']]],
]);

echo "Valid records: " . $validRows->count() . " of " . $userData->count() . "\n";
echo "Valid rows:\n";
foreach ($validRows->selectColumns('id', 'name', 'email')->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %d. %s <%s>\n", $row[0], $row[1], $row[2]);
    }
}
echo "\n";

// ============================================================================
// 4. Finding Invalid Rows
// ============================================================================

echo "4. Finding Invalid Rows\n";
echo str_repeat('-', 50) . "\n";

$invalidRows = $userData->filterInvalid([
    'name' => ['required'],
    'email' => ['required', 'email'],
    'age' => [['range', 18, 120]],
]);

echo "Invalid records: " . $invalidRows->count() . "\n";
echo "Invalid rows:\n";
foreach ($invalidRows->selectColumns('id', 'name', 'email', 'age')->toArray() as $i => $row) {
    if ($i > 0) {
        $name = $row[1] ?: '(empty)';
        $email = $row[2] ?? '(null)';
        echo sprintf("  ID %d: %s, %s, age %s\n", $row[0], $name, $email, $row[3]);
    }
}
echo "\n";

// ============================================================================
// 5. Validate or Fail
// ============================================================================

echo "5. Validate or Fail (Exception Handling)\n";
echo str_repeat('-', 50) . "\n";

// Create a valid dataset
$validData = Table::fromArray([
    ['name', 'email', 'age'],
    ['Alice', 'alice@example.com', 25],
    ['Bob', 'bob@example.com', 30],
]);

try {
    $validData->validateOrFail([
        'name' => ['required'],
        'email' => ['required', 'email'],
        'age' => ['integer', ['range', 18, 100]],
    ]);
    echo "  ✓ Valid data passed validation\n";
} catch (RuntimeException $e) {
    echo "  ✗ Validation failed: " . $e->getMessage() . "\n";
}

// Create an invalid dataset
$invalidData = Table::fromArray([
    ['name', 'email', 'age'],
    ['Alice', 'not-an-email', 25],
]);

try {
    $invalidData->validateOrFail([
        'email' => ['email'],
    ]);
    echo "  ✓ Data passed validation\n";
} catch (RuntimeException $e) {
    echo "  ✗ Validation failed: " . $e->getMessage() . "\n";
}
echo "\n";

// ============================================================================
// 6. Data Quality Report
// ============================================================================

echo "6. Data Quality Report\n";
echo str_repeat('-', 50) . "\n";

$rules = [
    'name' => ['required'],
    'email' => ['required', 'email'],
    'age' => ['required', 'integer', ['range', 18, 120]],
    'status' => [['in', ['active', 'inactive', 'pending']]],
    'phone' => [['pattern', '/^\d{3}-\d{4}$/']],
];

$result = $userData->validate($rules);

// Count errors by field
$errorsByField = [];
foreach ($result['errors'] as $error) {
    $field = $error['field'];
    $errorsByField[$field] = ($errorsByField[$field] ?? 0) + 1;
}

// Count errors by rule type
$errorsByRule = [];
foreach ($result['errors'] as $error) {
    $rule = $error['rule'];
    $errorsByRule[$rule] = ($errorsByRule[$rule] ?? 0) + 1;
}

$totalRows = $userData->count();
$invalidRowCount = count(array_unique(array_column($result['errors'], 'row')));
$validRowCount = $totalRows - $invalidRowCount;
$validPercent = round(($validRowCount / $totalRows) * 100, 1);

echo "Data Quality Summary:\n";
echo "  Total Records: $totalRows\n";
echo "  Valid Records: $validRowCount ($validPercent%)\n";
echo "  Invalid Records: $invalidRowCount\n";
echo "\n";

echo "Errors by Field:\n";
arsort($errorsByField);
foreach ($errorsByField as $field => $count) {
    echo sprintf("  %-12s: %d errors\n", $field, $count);
}
echo "\n";

echo "Errors by Rule:\n";
arsort($errorsByRule);
foreach ($errorsByRule as $rule => $count) {
    echo sprintf("  %-12s: %d failures\n", $rule, $count);
}
echo "\n";

// ============================================================================
// 7. Cleaning Data Based on Validation
// ============================================================================

echo "7. Cleaning Data Pipeline\n";
echo str_repeat('-', 50) . "\n";

// Start with messy data
$messyData = Table::fromArray([
    ['name', 'email', 'age', 'department'],
    ['  Alice  ', 'ALICE@EXAMPLE.COM', '25', 'Engineering'],
    ['Bob', 'bob@example.com', '30', '  Sales  '],
    ['', 'carol@example.com', 'invalid', 'Marketing'],
    ['David', '', '35', 'Engineering'],
]);

echo "Starting with " . $messyData->count() . " records\n";

// Clean and validate
$cleanData = $messyData
    // Trim whitespace
    ->trim('name')
    ->trim('department')
    
    // Normalize email to lowercase
    ->lower('email')
    
    // Convert age to integer (will be 0 for invalid)
    ->convert('age', fn($val) => is_numeric($val) ? (int) $val : 0)
    
    // Filter to valid records
    ->filter(fn($row) => !empty($row['name']))  // Has name
    ->filter(fn($row) => !empty($row['email'])) // Has email
    ->filter(fn($row) => $row['age'] >= 18);    // Valid age

echo "After cleaning: " . $cleanData->count() . " valid records\n";
echo "\nCleaned data:\n";
foreach ($cleanData->toArray() as $i => $row) {
    if ($i > 0) {
        echo sprintf("  %s | %s | %d | %s\n", 
            $row[0], $row[1], $row[2], $row[3]);
    }
}

echo "\n✓ Data validation example completed!\n";
