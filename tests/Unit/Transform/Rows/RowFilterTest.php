<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use Phetl\Table;
use PHPUnit\Framework\TestCase;

class RowFilterTest extends TestCase
{
    public function test_it_filters_with_custom_predicate(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ];

        $table = Table::fromArray($data)->filter(fn($row) => $row['age'] > 28);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Charlie', 35], $result[2]);
    }

    public function test_select_is_alias_for_filter(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->select(fn($row) => $row['age'] >= 30);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Alice', 30], $result[1]);
    }

    public function test_it_filters_where_equals(): void
    {
        $data = [
            ['name', 'status'],
            ['Alice', 'active'],
            ['Bob', 'inactive'],
            ['Charlie', 'active'],
        ];

        $table = Table::fromArray($data)->whereEquals('status', 'active');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 'active'], $result[1]);
        $this->assertEquals(['Charlie', 'active'], $result[2]);
    }

    public function test_it_filters_where_not_equals(): void
    {
        $data = [
            ['name', 'status'],
            ['Alice', 'active'],
            ['Bob', 'inactive'],
        ];

        $table = Table::fromArray($data)->whereNotEquals('status', 'active');

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', 'inactive'], $result[1]);
    }

    public function test_it_filters_where_greater_than(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ];

        $table = Table::fromArray($data)->whereGreaterThan('age', 28);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Charlie', 35], $result[2]);
    }

    public function test_it_filters_where_less_than(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->whereLessThan('age', 28);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', 25], $result[1]);
    }

    public function test_it_filters_where_greater_than_or_equal(): void
    {
        $data = [
            ['name', 'score'],
            ['Alice', 90],
            ['Bob', 85],
            ['Charlie', 95],
        ];

        $table = Table::fromArray($data)->whereGreaterThanOrEqual('score', 90);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 90], $result[1]);
        $this->assertEquals(['Charlie', 95], $result[2]);
    }

    public function test_it_filters_where_less_than_or_equal(): void
    {
        $data = [
            ['name', 'score'],
            ['Alice', 90],
            ['Bob', 85],
        ];

        $table = Table::fromArray($data)->whereLessThanOrEqual('score', 85);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', 85], $result[1]);
    }

    public function test_it_filters_where_in(): void
    {
        $data = [
            ['name', 'city'],
            ['Alice', 'NYC'],
            ['Bob', 'LA'],
            ['Charlie', 'SF'],
        ];

        $table = Table::fromArray($data)->whereIn('city', ['NYC', 'SF']);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
        $this->assertEquals(['Charlie', 'SF'], $result[2]);
    }

    public function test_it_filters_where_not_in(): void
    {
        $data = [
            ['name', 'city'],
            ['Alice', 'NYC'],
            ['Bob', 'LA'],
        ];

        $table = Table::fromArray($data)->whereNotIn('city', ['NYC']);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', 'LA'], $result[1]);
    }

    public function test_it_filters_where_null(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', null],
            ['Charlie', 35],
        ];

        $table = Table::fromArray($data)->whereNull('age');

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', null], $result[1]);
    }

    public function test_it_filters_where_not_null(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', null],
        ];

        $table = Table::fromArray($data)->whereNotNull('age');

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Alice', 30], $result[1]);
    }

    public function test_it_filters_where_true(): void
    {
        $data = [
            ['name', 'active'],
            ['Alice', true],
            ['Bob', false],
            ['Charlie', true],
        ];

        $table = Table::fromArray($data)->whereTrue('active');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', true], $result[1]);
        $this->assertEquals(['Charlie', true], $result[2]);
    }

    public function test_it_filters_where_false(): void
    {
        $data = [
            ['name', 'active'],
            ['Alice', true],
            ['Bob', false],
        ];

        $table = Table::fromArray($data)->whereFalse('active');

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Bob', false], $result[1]);
    }

    public function test_it_filters_where_contains(): void
    {
        $data = [
            ['name', 'email'],
            ['Alice', 'alice@example.com'],
            ['Bob', 'bob@test.com'],
            ['Charlie', 'charlie@example.com'],
        ];

        $table = Table::fromArray($data)->whereContains('email', 'example');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 'alice@example.com'], $result[1]);
        $this->assertEquals(['Charlie', 'charlie@example.com'], $result[2]);
    }

    public function test_filters_can_be_chained(): void
    {
        $data = [
            ['name', 'age', 'status'],
            ['Alice', 30, 'active'],
            ['Bob', 25, 'inactive'],
            ['Charlie', 35, 'active'],
            ['David', 28, 'active'],
        ];

        $table = Table::fromArray($data)
            ->whereEquals('status', 'active')
            ->whereGreaterThan('age', 28);

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['Alice', 30, 'active'], $result[1]);
        $this->assertEquals(['Charlie', 35, 'active'], $result[2]);
    }

    public function test_filters_combine_with_other_transformations(): void
    {
        $data = [
            ['name', 'age', 'city', 'temp'],
            ['Alice', 30, 'NYC', 'x'],
            ['Bob', 25, 'LA', 'y'],
            ['Charlie', 35, 'SF', 'z'],
        ];

        $table = Table::fromArray($data)
            ->removeColumns('temp')
            ->whereGreaterThan('age', 26)
            ->selectColumns('name', 'city')
            ->head(1);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
    }

    public function test_it_returns_header_only_when_no_rows_match(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->whereGreaterThan('age', 100);

        $result = $table->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_handles_missing_fields_gracefully(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob'], // Missing age
        ];

        $table = Table::fromArray($data)->whereGreaterThan('age', 25);

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['Alice', 30], $result[1]);
    }
}
