<?php

declare(strict_types=1);

namespace Tests\Integration\Pipelines;

use Phetl\Table;

describe('Conditional Transformation Pipeline Integration', function () {
    it('categorizes customers by purchase amount', function () {
        $table = Table::fromArray([
            ['name', 'amount'],
            ['Alice', 1500],
            ['Bob', 800],
            ['Charlie', 400],
            ['David', 100],
        ]);

        $result = $table
            ->case('amount', 'tier', [
                [fn ($val) => $val >= 1000, 'Premium'],
                [fn ($val) => $val >= 500, 'Gold'],
                [fn ($val) => $val >= 200, 'Silver'],
            ], 'Bronze')
            ->when('amount', fn ($val) => $val >= 1000, 'discount_pct', 0.15, 0.10)
            ->toArray();

        expect($result)->toBe([
            ['name', 'amount', 'tier', 'discount_pct'],
            ['Alice', 1500, 'Premium', 0.15],
            ['Bob', 800, 'Gold', 0.10],
            ['Charlie', 400, 'Silver', 0.10],
            ['David', 100, 'Bronze', 0.10],
        ]);
    });

    it('handles missing contact information gracefully', function () {
        $table = Table::fromArray([
            ['name', 'email', 'phone', 'address'],
            ['Alice', null, '555-1234', null],
            ['Bob', 'bob@example.com', null, null],
            ['Charlie', null, null, '123 Main St'],
            ['David', null, null, null],
        ]);

        $result = $table
            ->coalesce('contact', ['email', 'phone', 'address'])
            ->ifNull('contact', 'contact_status', 'No Contact Info')
            ->toArray();

        expect($result)->toBe([
            ['name', 'email', 'phone', 'address', 'contact', 'contact_status'],
            ['Alice', null, '555-1234', null, '555-1234', '555-1234'],
            ['Bob', 'bob@example.com', null, null, 'bob@example.com', 'bob@example.com'],
            ['Charlie', null, null, '123 Main St', '123 Main St', '123 Main St'],
            ['David', null, null, null, null, 'No Contact Info'],
        ]);
    });

    it('cleanses data by replacing sentinel values', function () {
        $table = Table::fromArray([
            ['product', 'price', 'quantity'],
            ['Widget', -999, 10],
            ['Gadget', 29.99, -1],
            ['Doodad', 15.50, 5],
        ]);

        $result = $table
            ->nullIf('price', 'clean_price', fn ($val) => $val < 0)
            ->nullIf('quantity', 'clean_qty', fn ($val) => $val < 0)
            ->ifNull('clean_price', 'final_price', 0.00)
            ->ifNull('clean_qty', 'final_qty', 0)
            ->toArray();

        expect($result)->toBe([
            ['product', 'price', 'quantity', 'clean_price', 'clean_qty', 'final_price', 'final_qty'],
            ['Widget', -999, 10, null, 10, 0.00, 10],
            ['Gadget', 29.99, -1, 29.99, null, 29.99, 0],
            ['Doodad', 15.50, 5, 15.50, 5, 15.50, 5],
        ]);
    });

    it('combines conditional logic with filtering', function () {
        $table = Table::fromArray([
            ['name', 'score', 'attendance'],
            ['Alice', 95, 100],
            ['Bob', 75, 80],
            ['Charlie', 85, 95],
            ['David', 65, 70],
        ]);

        $result = $table
            ->case('score', 'letter_grade', [
                [fn ($val) => $val >= 90, 'A'],
                [fn ($val) => $val >= 80, 'B'],
                [fn ($val) => $val >= 70, 'C'],
            ], 'F')
            ->when('attendance', fn ($val) => $val >= 90, 'status', 'Excellent', 'Needs Improvement')
            ->whereIn('letter_grade', ['A', 'B'])
            ->toArray();

        expect($result)->toBe([
            ['name', 'score', 'attendance', 'letter_grade', 'status'],
            ['Alice', 95, 100, 'A', 'Excellent'],
            ['Charlie', 85, 95, 'B', 'Excellent'],
        ]);
    });

    it('handles complex ETL pipeline with multiple conditionals', function () {
        $table = Table::fromArray([
            ['customer', 'region', 'revenue', 'cost'],
            ['ACME Corp', null, 50000, 30000],
            ['TechStart', 'West', null, 15000],
            ['GlobalCo', 'East', 100000, null],
            ['LocalShop', null, null, 5000],
        ]);

        $result = $table
            ->ifNull('region', 'region_clean', 'Unknown')
            ->nullIf('revenue', 'revenue_clean', fn ($val) => $val === null)
            ->nullIf('cost', 'cost_clean', fn ($val) => $val === null)
            ->ifNull('revenue_clean', 'revenue_final', 0)
            ->ifNull('cost_clean', 'cost_final', 0)
            ->case('revenue_final', 'size', [
                [fn ($val) => $val >= 100000, 'Enterprise'],
                [fn ($val) => $val >= 50000, 'Mid-Market'],
                [fn ($val) => $val > 0, 'SMB'],
            ], 'No Revenue')
            ->toArray();

        expect(count($result))->toBe(5); // header + 4 rows
        expect($result[1][9])->toBe('Mid-Market'); // ACME Corp size
        expect($result[2][9])->toBe('No Revenue'); // TechStart size (null revenue)
        expect($result[3][9])->toBe('Enterprise'); // GlobalCo size
        expect($result[4][9])->toBe('No Revenue'); // LocalShop size
    });

    it('applies conditional logic to calculate derived fields', function () {
        $table = Table::fromArray([
            ['employee', 'sales', 'years'],
            ['Alice', 150000, 5],
            ['Bob', 80000, 2],
            ['Charlie', 120000, 8],
        ]);

        $result = $table
            ->when(
                'sales',
                fn ($val) => $val >= 100000,
                'bonus',
                fn ($row) => $row[1] * 0.10, // sales * 10%
                fn ($row) => $row[1] * 0.05  // sales * 5%
            )
            ->when(
                'years',
                fn ($val) => $val >= 5,
                'seniority_bonus',
                5000,
                0
            )
            ->toArray();

        expect($result[1])->toBe(['Alice', 150000, 5, 15000.0, 5000]);
        expect($result[2])->toBe(['Bob', 80000, 2, 4000.0, 0]);
        expect($result[3])->toBe(['Charlie', 120000, 8, 12000.0, 5000]);
    });
});
