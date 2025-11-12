<?php

declare(strict_types=1);

namespace Phetl\Benchmarks\Suites;

use Phetl\Benchmarks\Benchmark;
use Phetl\Table;

/**
 * Benchmark sorting operations
 */
class SortBenchmark extends Benchmark
{
    public function __construct(
        private int $rowCount,
        private string $sortType = 'single'
    ) {
    }

    public function getName(): string
    {
        return "Sort ({$this->sortType}) - {$this->rowCount} rows";
    }

    protected function execute(): void
    {
        $data = $this->generateData();
        $table = Table::fromArray($data);

        switch ($this->sortType) {
            case 'single':
                $table->sortBy('age')->toArray();
                break;
            case 'multiple':
                $table->sort(['department', 'age'])->toArray();
                break;
            case 'descending':
                $table->sortByDesc('salary')->toArray();
                break;
        }
    }

    private function generateData(): array
    {
        $departments = ['Engineering', 'Sales', 'Marketing', 'HR', 'Finance'];

        $data = [['id', 'name', 'age', 'salary', 'department']];

        for ($i = 1; $i <= $this->rowCount; $i++) {
            $data[] = [
                $i,
                "Employee $i",
                rand(22, 65),
                rand(40000, 150000),
                $departments[array_rand($departments)],
            ];
        }

        return $data;
    }
}
