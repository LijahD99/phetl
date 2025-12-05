<?php

/**
 * Example 02: CSV Processing
 *
 * This example demonstrates comprehensive CSV file operations:
 * - Reading CSV files with various options
 * - Handling different delimiters and formats
 * - Transforming CSV data
 * - Writing CSV output
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Phetl\Table;

echo "=== PHETL CSV Processing ===\n\n";

// Create sample data for demonstrations
$sampleData = [
    ['id', 'name', 'email', 'department', 'salary', 'hire_date'],
    ['1', 'Alice Johnson', 'alice@company.com', 'Engineering', '95000', '2020-03-15'],
    ['2', 'Bob Smith', 'bob@company.com', 'Sales', '75000', '2019-07-22'],
    ['3', 'Carol Williams', 'carol@company.com', 'Engineering', '105000', '2018-01-10'],
    ['4', 'David Brown', 'david@company.com', 'Marketing', '68000', '2021-09-01'],
    ['5', 'Eve Davis', 'eve@company.com', 'Engineering', '88000', '2022-02-14'],
];

$outputDir = __DIR__ . '/output';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// ============================================================================
// 1. Basic CSV Reading and Writing
// ============================================================================

echo "1. Basic CSV Operations\n";
echo str_repeat('-', 50) . "\n";

// First create a sample CSV file
$table = Table::fromArray($sampleData);
$table->toCsv($outputDir . '/employees.csv');
echo "Created sample CSV file\n";

// Read the CSV file back
$employees = Table::fromCsv($outputDir . '/employees.csv');
echo "Read " . $employees->count() . " employees from CSV\n";
echo "Columns: " . implode(', ', $employees->header()) . "\n\n";

// ============================================================================
// 2. CSV with Custom Delimiters
// ============================================================================

echo "2. Custom Delimiters\n";
echo str_repeat('-', 50) . "\n";

// Write with semicolon delimiter (common in European files)
$table->toCsv($outputDir . '/employees_semicolon.csv', delimiter: ';');
echo "Wrote CSV with semicolon delimiter\n";

// Write with tab delimiter (TSV)
$table->toCsv($outputDir . '/employees.tsv', delimiter: "\t");
echo "Wrote TSV (tab-separated) file\n\n";

// ============================================================================
// 3. Filtering and Transforming CSV Data
// ============================================================================

echo "3. Filtering and Transforming\n";
echo str_repeat('-', 50) . "\n";

// Filter engineering employees earning above 90k
$highEarners = $employees
    ->whereEquals('department', 'Engineering')
    ->filter(fn($row) => (int) $row['salary'] > 90000)
    ->selectColumns('name', 'email', 'salary');

echo "High-earning engineers:\n";
foreach ($highEarners->toArray() as $index => $row) {
    if ($index === 0) {
        continue; // Skip header for display
    }
    echo "  - {$row[0]}: \${$row[2]}\n";
}
echo "\n";

// ============================================================================
// 4. Data Cleaning in CSV
// ============================================================================

echo "4. Data Cleaning\n";
echo str_repeat('-', 50) . "\n";

// Create data with issues
$dirtyData = Table::fromArray([
    ['name', 'email', 'status'],
    ['  Alice  ', 'ALICE@EXAMPLE.COM', 'active'],
    ['Bob', 'bob@example.com', 'ACTIVE'],
    ['  Carol', 'carol@example.com', 'Active'],
]);

// Clean the data
$cleanData = $dirtyData
    ->trim('name')                           // Remove whitespace
    ->lower('email')                         // Lowercase emails
    ->convert('status', 'strtolower');       // Normalize status

echo "Cleaned data:\n";
foreach ($cleanData->toArray() as $row) {
    echo "  " . implode(' | ', $row) . "\n";
}
echo "\n";

// ============================================================================
// 5. Adding Computed Columns
// ============================================================================

echo "5. Adding Computed Columns\n";
echo str_repeat('-', 50) . "\n";

$enriched = $employees
    // Convert salary to integer for calculations
    ->convert('salary', 'intval')
    
    // Add annual bonus (10% of salary)
    ->addColumn('bonus', fn($row) => $row['salary'] * 0.10)
    
    // Add total compensation
    ->addColumn('total_comp', fn($row) => $row['salary'] + $row['bonus'])
    
    // Add tenure in years
    ->addColumn('tenure_years', function ($row) {
        $hireDate = new DateTime($row['hire_date']);
        $now = new DateTime();
        return $hireDate->diff($now)->y;
    });

echo "Enriched employee data:\n";
$preview = $enriched->selectColumns('name', 'salary', 'bonus', 'tenure_years')->look(3);
foreach ($preview as $row) {
    echo "  " . implode(' | ', array_map('strval', $row)) . "\n";
}

$enriched->toCsv($outputDir . '/employees_enriched.csv');
echo "\nSaved enriched data to employees_enriched.csv\n\n";

// ============================================================================
// 6. Aggregating CSV Data
// ============================================================================

echo "6. Aggregating Data\n";
echo str_repeat('-', 50) . "\n";

$summary = $employees
    ->convert('salary', 'intval')
    ->aggregate('department', [
        'employee_count' => 'count',
        'avg_salary' => fn($rows) => array_sum(array_column($rows, 'salary')) / count($rows),
        'total_salary' => fn($rows) => array_sum(array_column($rows, 'salary')),
    ])
    ->sortByDesc('avg_salary');

echo "Department Summary:\n";
foreach ($summary->toArray() as $index => $row) {
    if ($index === 0) {
        echo "  " . implode(' | ', $row) . "\n";
        echo "  " . str_repeat('-', 50) . "\n";
    } else {
        $formatted = sprintf(
            "  %-15s | %d employees | \$%s avg | \$%s total",
            $row[0],
            $row[1],
            number_format($row[2], 0),
            number_format($row[3], 0)
        );
        echo $formatted . "\n";
    }
}

$summary->toCsv($outputDir . '/department_summary.csv');
echo "\nSaved summary to department_summary.csv\n\n";

// ============================================================================
// 7. Splitting Data into Multiple Files
// ============================================================================

echo "7. Splitting Data\n";
echo str_repeat('-', 50) . "\n";

// Get unique departments
$departments = ['Engineering', 'Sales', 'Marketing'];

foreach ($departments as $dept) {
    $deptData = $employees->whereEquals('department', $dept);
    $filename = strtolower($dept) . '_employees.csv';
    $result = $deptData->toCsv($outputDir . '/' . $filename);
    echo "  $dept: {$result->rowCount()} employees -> $filename\n";
}

echo "\n✓ CSV processing example completed!\n";
echo "Output files saved to: $outputDir\n";
