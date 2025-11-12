<?php

declare(strict_types=1);

namespace Phetl\Benchmarks\Suites;

use Phetl\Benchmarks\Benchmark;
use Phetl\Table;

/**
 * Benchmark aggregation operations
 */
class AggregationBenchmark extends Benchmark
{
    public function __construct(
        private int $rowCount,
        private int $groupCount = 10
    ) {
    }

    public function getName(): string
    {
        return "Aggregation - {$this->rowCount} rows, {$this->groupCount} groups";
    }

    protected function execute(): void
    {
        $data = $this->generateData();
        $table = Table::fromArray($data);

        $table->aggregate(['category'], [
            'total_sales' => fn($rows) => array_sum(array_column($rows, 2)),  // 'sales' is index 2
            'avg_sales' => fn($rows) => count($rows) > 0 ? array_sum(array_column($rows, 2)) / count($rows) : 0,
            'count' => fn($rows) => count($rows),
            'min_sales' => fn($rows) => count($rows) > 0 ? min(array_column($rows, 2)) : 0,
            'max_sales' => fn($rows) => count($rows) > 0 ? max(array_column($rows, 2)) : 0,
        ])->toArray();
    }

    private function generateData(): array
    {
        $categories = array_map(fn($i) => "Category $i", range(1, $this->groupCount));

        $data = [['id', 'category', 'sales', 'quantity']];

        for ($i = 1; $i <= $this->rowCount; $i++) {
            $data[] = [
                $i,
                $categories[array_rand($categories)],
                rand(100, 10000),
                rand(1, 100),
            ];
        }

        return $data;
    }
}
