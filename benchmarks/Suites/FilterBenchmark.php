<?php

declare(strict_types=1);

namespace Phetl\Benchmarks\Suites;

use Phetl\Benchmarks\Benchmark;
use Phetl\Table;

/**
 * Benchmark filtering operations on datasets of varying sizes
 */
class FilterBenchmark extends Benchmark
{
    public function __construct(
        private int $rowCount,
        private string $filterType = 'simple'
    ) {
    }

    public function getName(): string
    {
        return "Filter ({$this->filterType}) - {$this->rowCount} rows";
    }

    protected function execute(): void
    {
        $data = $this->generateData();
        $table = Table::fromArray($data);

        switch ($this->filterType) {
            case 'simple':
                $table->whereEquals('status', 'active')->toArray();
                break;
            case 'complex':
                $table
                    ->whereGreaterThan('age', 25)
                    ->whereLessThan('age', 65)
                    ->whereIn('department', ['Engineering', 'Sales'])
                    ->toArray();
                break;
            case 'closure':
                $table->filter(fn($row) =>
                    $row['age'] > 25 &&
                    in_array($row['department'], ['Engineering', 'Sales'])
                )->toArray();
                break;
        }
    }

    private function generateData(): array
    {
        $statuses = ['active', 'inactive', 'pending'];
        $departments = ['Engineering', 'Sales', 'Marketing', 'HR'];

        $data = [['id', 'name', 'age', 'status', 'department']];

        for ($i = 1; $i <= $this->rowCount; $i++) {
            $data[] = [
                $i,
                "User $i",
                rand(20, 70),
                $statuses[array_rand($statuses)],
                $departments[array_rand($departments)],
            ];
        }

        return $data;
    }
}
