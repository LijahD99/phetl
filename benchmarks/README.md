# PHETL Performance Benchmarks

Comprehensive benchmark suite for measuring performance characteristics of PHETL operations.

## Quick Start

Run all benchmarks:

```bash
php benchmarks/run.php
```

## What Gets Benchmarked

### Filter Operations
- Simple filters (whereEquals)
- Complex filters (multiple conditions)
- Closure-based filters
- Dataset sizes: 1K, 10K, 100K rows

### Aggregation Operations
- Multiple aggregation functions (sum, avg, count, min, max)
- Various group counts (10, 50, 100, 1000 groups)
- Dataset sizes: 1K, 10K, 100K rows

### Join Operations
- Inner joins
- Left joins
- Right joins
- Various table size combinations (1K×1K, 10K×1K, 10K×10K)

### Sort Operations
- Single field sorting
- Multiple field sorting
- Ascending and descending
- Dataset sizes: 1K, 10K, 100K rows

## Metrics Collected

For each benchmark, the following metrics are measured across multiple iterations:

### Time Metrics
- Average execution time (milliseconds)
- Median execution time
- Minimum execution time
- Maximum execution time
- Standard deviation

### Memory Metrics
- Average memory usage (bytes)
- Peak memory usage
- Formatted human-readable sizes

## Output

### Console Output
- Real-time progress during benchmark execution
- Detailed results for each benchmark
- Performance comparison (sorted by speed and memory)

### JSON Export
Results are exported to `benchmarks/results/benchmark_YYYY-MM-DD_HH-MM-SS.json`:

```json
{
  "timestamp": "2025-11-12T10:30:00+00:00",
  "php_version": "8.1.0",
  "benchmarks": [
    {
      "name": "Filter (simple) - 1000 rows",
      "iterations": 20,
      "time": {
        "avg_ms": 1.234,
        "median_ms": 1.200,
        "min_ms": 1.100,
        "max_ms": 1.500,
        "stddev_ms": 0.123
      },
      "memory": {
        "avg_bytes": 524288,
        "avg_formatted": "512.00 KB",
        "peak_bytes": 1048576,
        "peak_formatted": "1.00 MB"
      }
    }
  ]
}
```

### CSV Export
Results are also exported to `benchmarks/results/benchmark_YYYY-MM-DD_HH-MM-SS.csv` for analysis in spreadsheets or BI tools.

## Creating Custom Benchmarks

Extend the `Benchmark` base class:

```php
<?php

use Phetl\Benchmarks\Benchmark;
use Phetl\Table;

class MyCustomBenchmark extends Benchmark
{
    public function __construct(private int $rowCount)
    {
    }

    public function getName(): string
    {
        return "My Custom Benchmark - {$this->rowCount} rows";
    }

    protected function execute(): void
    {
        // Your benchmark code here
        $data = $this->generateData();
        $table = Table::fromArray($data);

        // Perform operations...
        $result = $table
            ->whereEquals('status', 'active')
            ->sortBy('age')
            ->toArray();
    }

    private function generateData(): array
    {
        // Generate test data
        $data = [['id', 'name', 'age', 'status']];

        for ($i = 1; $i <= $this->rowCount; $i++) {
            $data[] = [$i, "User $i", rand(20, 70), 'active'];
        }

        return $data;
    }
}
```

Then add it to the runner:

```php
$runner->add((new MyCustomBenchmark(10000))->setIterations(10));
```

## Interpreting Results

### Time Performance
- **< 10ms**: Excellent for real-time operations
- **10-100ms**: Good for interactive applications
- **100-1000ms**: Acceptable for batch processing
- **> 1000ms**: Consider optimization or chunking

### Memory Usage
- **< 10MB**: Excellent efficiency
- **10-100MB**: Good for most datasets
- **100MB-1GB**: May need optimization for large datasets
- **> 1GB**: Consider streaming or chunking strategies

### Standard Deviation
- **Low stddev**: Consistent performance
- **High stddev**: Performance varies, investigate outliers

## Baseline Expectations

These are rough guidelines for PHP 8.1+ on modern hardware:

| Operation | 1K rows | 10K rows | 100K rows |
|-----------|---------|----------|-----------|
| Filter    | ~1-5ms  | ~10-50ms | ~100-500ms |
| Sort      | ~2-10ms | ~20-100ms | ~200ms-1s |
| Join      | ~5-20ms | ~50-200ms | ~500ms-2s |
| Aggregate | ~3-15ms | ~30-150ms | ~300ms-1.5s |

Actual performance depends on:
- PHP version and opcache settings
- Hardware (CPU, memory speed)
- Data characteristics (types, distribution)
- Operation complexity

## Continuous Benchmarking

Run benchmarks regularly to:
- Track performance over time
- Detect regressions in new code
- Validate optimization efforts
- Compare different approaches

Consider running benchmarks:
- Before/after major refactorings
- When adding new features
- On different PHP versions
- On different hardware configurations

## Tips for Accurate Benchmarking

1. **Close other applications** - Minimize background processes
2. **Disable opcache optimizations** during development (re-enable for production comparisons)
3. **Run multiple iterations** - The framework does this automatically
4. **Use representative data** - Benchmarks use realistic data distributions
5. **Warm up** - The framework runs a warmup iteration before measuring
6. **Check PHP configuration** - memory_limit, max_execution_time, etc.

## Troubleshooting

### Out of Memory
Increase PHP memory limit:
```bash
php -d memory_limit=2G benchmarks/run.php
```

### Slow Execution
Reduce dataset sizes or iterations in `run.php`:
```php
$runner->add((new FilterBenchmark(1000, 'simple'))->setIterations(5));
```

### Inconsistent Results
- Close background applications
- Run benchmarks multiple times
- Check for thermal throttling on laptops
- Use average/median instead of single runs
