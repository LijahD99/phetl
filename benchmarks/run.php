<?php

/**
 * PHETL Performance Benchmark Suite
 *
 * Run comprehensive benchmarks on all major operations
 * to establish performance baselines and identify optimization opportunities.
 */

require __DIR__ . '/../vendor/autoload.php';

use Phetl\Benchmarks\BenchmarkRunner;
use Phetl\Benchmarks\Suites\FilterBenchmark;
use Phetl\Benchmarks\Suites\AggregationBenchmark;
use Phetl\Benchmarks\Suites\JoinBenchmark;
use Phetl\Benchmarks\Suites\SortBenchmark;

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║         PHETL Performance Benchmark Suite               ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "\n";

$runner = new BenchmarkRunner();

// ============================================================================
// Filter Benchmarks
// ============================================================================

echo "Adding Filter benchmarks...\n";

// Small dataset (1K rows)
$runner->add((new FilterBenchmark(1000, 'simple'))->setIterations(20));
$runner->add((new FilterBenchmark(1000, 'complex'))->setIterations(20));
$runner->add((new FilterBenchmark(1000, 'closure'))->setIterations(20));

// Medium dataset (10K rows)
$runner->add((new FilterBenchmark(10000, 'simple'))->setIterations(10));
$runner->add((new FilterBenchmark(10000, 'complex'))->setIterations(10));
$runner->add((new FilterBenchmark(10000, 'closure'))->setIterations(10));

// Large dataset (100K rows)
$runner->add((new FilterBenchmark(100000, 'simple'))->setIterations(5));
$runner->add((new FilterBenchmark(100000, 'complex'))->setIterations(5));

// ============================================================================
// Aggregation Benchmarks
// ============================================================================

echo "Adding Aggregation benchmarks...\n";

// Small dataset (1K rows, 10 groups)
$runner->add((new AggregationBenchmark(1000, 10))->setIterations(20));

// Medium dataset (10K rows, 50 groups)
$runner->add((new AggregationBenchmark(10000, 50))->setIterations(10));

// Large dataset (100K rows, 100 groups)
$runner->add((new AggregationBenchmark(100000, 100))->setIterations(5));

// Many groups (10K rows, 1000 groups)
$runner->add((new AggregationBenchmark(10000, 1000))->setIterations(5));

// ============================================================================
// Join Benchmarks
// ============================================================================

echo "Adding Join benchmarks...\n";

// Small joins (1K x 1K)
$runner->add((new JoinBenchmark(1000, 1000, 'inner'))->setIterations(15));
$runner->add((new JoinBenchmark(1000, 1000, 'left'))->setIterations(15));

// Medium joins (10K x 1K)
$runner->add((new JoinBenchmark(10000, 1000, 'inner'))->setIterations(10));
$runner->add((new JoinBenchmark(10000, 1000, 'left'))->setIterations(10));

// Large joins (10K x 10K)
$runner->add((new JoinBenchmark(10000, 10000, 'inner'))->setIterations(5));

// ============================================================================
// Sort Benchmarks
// ============================================================================

echo "Adding Sort benchmarks...\n";

// Small dataset (1K rows)
$runner->add((new SortBenchmark(1000, 'single'))->setIterations(20));
$runner->add((new SortBenchmark(1000, 'multiple'))->setIterations(20));
$runner->add((new SortBenchmark(1000, 'descending'))->setIterations(20));

// Medium dataset (10K rows)
$runner->add((new SortBenchmark(10000, 'single'))->setIterations(10));
$runner->add((new SortBenchmark(10000, 'multiple'))->setIterations(10));

// Large dataset (100K rows)
$runner->add((new SortBenchmark(100000, 'single'))->setIterations(5));

echo "\n";

// ============================================================================
// Run Benchmarks
// ============================================================================

$runner->run();

// ============================================================================
// Display Results
// ============================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                  Detailed Results                        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";

$runner->printResults();

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║               Performance Comparison                     ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";

$runner->printComparison();

// ============================================================================
// Export Results
// ============================================================================

$timestamp = date('Y-m-d_H-i-s');
$jsonFile = __DIR__ . "/results/benchmark_{$timestamp}.json";
$csvFile = __DIR__ . "/results/benchmark_{$timestamp}.csv";

@mkdir(__DIR__ . '/results', 0755, true);

$runner->exportJson($jsonFile);
$runner->exportCsv($csvFile);

echo "\n";
echo "Results exported to:\n";
echo "  - JSON: $jsonFile\n";
echo "  - CSV:  $csvFile\n";
echo "\n";

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║              Benchmark Complete! ✓                       ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
