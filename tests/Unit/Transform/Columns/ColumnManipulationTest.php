<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Columns;

use Phetl\Table;
use PHPUnit\Framework\TestCase;

class ColumnManipulationTest extends TestCase
{
    public function test_it_renames_single_column(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->renameColumns(['name' => 'full_name']);

        $result = $table->toArray();
        $this->assertEquals(['full_name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
    }

    public function test_it_renames_multiple_columns(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $table = Table::fromArray($data)->renameColumns([
            'name' => 'full_name',
            'city' => 'location',
        ]);

        $result = $table->toArray();
        $this->assertEquals(['full_name', 'age', 'location'], $result[0]);
        $this->assertEquals(['Alice', 30, 'NYC'], $result[1]);
    }

    public function test_it_ignores_nonexistent_columns_in_rename(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->renameColumns([
            'nonexistent' => 'new_name',
            'age' => 'years',
        ]);

        $result = $table->toArray();
        $this->assertEquals(['name', 'years'], $result[0]);
    }

    public function test_rename_is_alias_for_rename_columns(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->rename(['name' => 'full_name']);

        $result = $table->toArray();
        $this->assertEquals(['full_name', 'age'], $result[0]);
    }

    public function test_it_adds_column_with_constant_value(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->addColumn('country', 'USA');

        $result = $table->toArray();
        $this->assertEquals(['name', 'age', 'country'], $result[0]);
        $this->assertEquals(['Alice', 30, 'USA'], $result[1]);
        $this->assertEquals(['Bob', 25, 'USA'], $result[2]);
    }

    public function test_it_adds_column_with_computed_value(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->addColumn(
            'age_in_months',
            fn (array $row) => $row['age'] * 12
        );

        $result = $table->toArray();
        $this->assertEquals(['name', 'age', 'age_in_months'], $result[0]);
        $this->assertEquals(['Alice', 30, 360], $result[1]);
        $this->assertEquals(['Bob', 25, 300], $result[2]);
    }

    public function test_it_adds_column_with_multiple_field_computation(): void
    {
        $data = [
            ['first', 'last'],
            ['Alice', 'Smith'],
            ['Bob', 'Jones'],
        ];

        $table = Table::fromArray($data)->addColumn(
            'full_name',
            fn (array $row) => $row['first'] . ' ' . $row['last']
        );

        $result = $table->toArray();
        $this->assertEquals(['first', 'last', 'full_name'], $result[0]);
        $this->assertEquals(['Alice', 'Smith', 'Alice Smith'], $result[1]);
        $this->assertEquals(['Bob', 'Jones', 'Bob Jones'], $result[2]);
    }

    public function test_add_field_is_alias_for_add_column(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->addField('status', 'active');

        $result = $table->toArray();
        $this->assertEquals(['name', 'age', 'status'], $result[0]);
        $this->assertEquals(['Alice', 30, 'active'], $result[1]);
    }

    public function test_it_adds_row_numbers(): void
    {
        $data = [
            ['name', 'city'],
            ['Alice', 'NYC'],
            ['Bob', 'LA'],
            ['Charlie', 'SF'],
        ];

        $table = Table::fromArray($data)->addRowNumbers();

        $result = $table->toArray();
        $this->assertEquals(['name', 'city', 'row_number'], $result[0]);
        $this->assertEquals(['Alice', 'NYC', 1], $result[1]);
        $this->assertEquals(['Bob', 'LA', 2], $result[2]);
        $this->assertEquals(['Charlie', 'SF', 3], $result[3]);
    }

    public function test_it_adds_row_numbers_with_custom_name(): void
    {
        $data = [
            ['name'],
            ['Alice'],
            ['Bob'],
        ];

        $table = Table::fromArray($data)->addRowNumbers('id');

        $result = $table->toArray();
        $this->assertEquals(['name', 'id'], $result[0]);
        $this->assertEquals(['Alice', 1], $result[1]);
        $this->assertEquals(['Bob', 2], $result[2]);
    }

    public function test_column_transformations_can_be_chained(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)
            ->renameColumns(['name' => 'full_name'])
            ->addColumn('country', 'USA')
            ->addRowNumbers('id');

        $result = $table->toArray();
        $this->assertEquals(['full_name', 'age', 'country', 'id'], $result[0]);
        $this->assertEquals(['Alice', 30, 'USA', 1], $result[1]);
        $this->assertEquals(['Bob', 25, 'USA', 2], $result[2]);
    }

    public function test_column_operations_combine_with_row_operations(): void
    {
        $data = [
            ['name', 'age', 'temp'],
            ['Alice', 30, 'x'],
            ['Bob', 25, 'y'],
            ['Charlie', 35, 'z'],
        ];

        $table = Table::fromArray($data)
            ->removeColumns('temp')
            ->addColumn('doubled_age', fn ($row) => $row['age'] * 2)
            ->head(1);

        $result = $table->toArray();
        $this->assertCount(2, $result); // header + 1 row
        $this->assertEquals(['name', 'age', 'doubled_age'], $result[0]);
        $this->assertEquals(['Alice', 30, 60], $result[1]);
    }

    public function test_it_handles_null_values_in_computations(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', null],
        ];

        $table = Table::fromArray($data)->addColumn(
            'age_doubled',
            fn (array $row) => $row['age'] !== null ? $row['age'] * 2 : null
        );

        $result = $table->toArray();
        $this->assertEquals(60, $result[1][2]);
        $this->assertNull($result[2][2]);
    }

    public function test_it_preserves_types_in_added_columns(): void
    {
        $data = [
            ['name', 'score'],
            ['Alice', 95.5],
        ];

        $table = Table::fromArray($data)
            ->addColumn('passing', fn ($row) => $row['score'] >= 60)
            ->addColumn('grade', fn ($row) => $row['score'] >= 90 ? 'A' : 'B');

        $result = $table->toArray();
        $this->assertIsBool($result[1][2]);
        $this->assertTrue($result[1][2]);
        $this->assertIsString($result[1][3]);
        $this->assertEquals('A', $result[1][3]);
    }
}
