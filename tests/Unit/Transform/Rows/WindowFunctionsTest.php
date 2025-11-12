<?php

declare(strict_types=1);

use Phetl\Transform\Rows\WindowFunctions;

describe('WindowFunctions', function () {
    describe('lag()', function () {
        it('returns previous row value', function () {
            $data = [
                ['id', 'value'],
                [1, 'A'],
                [2, 'B'],
                [3, 'C'],
                [4, 'D'],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'prev_value'));

            expect($result)->toBe([
                ['id', 'value', 'prev_value'],
                [1, 'A', null],
                [2, 'B', 'A'],
                [3, 'C', 'B'],
                [4, 'D', 'C'],
            ]);
        });

        it('supports custom offset', function () {
            $data = [
                ['id', 'value'],
                [1, 10],
                [2, 20],
                [3, 30],
                [4, 40],
                [5, 50],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'lag2', 2));

            expect($result)->toBe([
                ['id', 'value', 'lag2'],
                [1, 10, null],
                [2, 20, null],
                [3, 30, 10],
                [4, 40, 20],
                [5, 50, 30],
            ]);
        });

        it('supports default value', function () {
            $data = [
                ['id', 'value'],
                [1, 'A'],
                [2, 'B'],
                [3, 'C'],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'prev_value', 1, 'N/A'));

            expect($result)->toBe([
                ['id', 'value', 'prev_value'],
                [1, 'A', 'N/A'],
                [2, 'B', 'A'],
                [3, 'C', 'B'],
            ]);
        });

        it('supports partition by field', function () {
            $data = [
                ['category', 'value'],
                ['A', 1],
                ['A', 2],
                ['B', 3],
                ['B', 4],
                ['A', 5],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'prev_value', 1, null, 'category'));

            expect($result)->toBe([
                ['category', 'value', 'prev_value'],
                ['A', 1, null],
                ['A', 2, 1],
                ['B', 3, null],
                ['B', 4, 3],
                ['A', 5, 2],
            ]);
        });

        it('throws exception for invalid field', function () {
            $data = [
                ['id', 'value'],
                [1, 'A'],
            ];

            expect(fn() => iterator_to_array(WindowFunctions::lag($data, 'invalid', 'result')))
                ->toThrow(InvalidArgumentException::class, "Field 'invalid' not found");
        });
    });

    describe('lead()', function () {
        it('returns next row value', function () {
            $data = [
                ['id', 'value'],
                [1, 'A'],
                [2, 'B'],
                [3, 'C'],
                [4, 'D'],
            ];

            $result = iterator_to_array(WindowFunctions::lead($data, 'value', 'next_value'));

            expect($result)->toBe([
                ['id', 'value', 'next_value'],
                [1, 'A', 'B'],
                [2, 'B', 'C'],
                [3, 'C', 'D'],
                [4, 'D', null],
            ]);
        });

        it('supports custom offset', function () {
            $data = [
                ['id', 'value'],
                [1, 10],
                [2, 20],
                [3, 30],
                [4, 40],
                [5, 50],
            ];

            $result = iterator_to_array(WindowFunctions::lead($data, 'value', 'lead2', 2));

            expect($result)->toBe([
                ['id', 'value', 'lead2'],
                [1, 10, 30],
                [2, 20, 40],
                [3, 30, 50],
                [4, 40, null],
                [5, 50, null],
            ]);
        });

        it('supports default value', function () {
            $data = [
                ['id', 'value'],
                [1, 'A'],
                [2, 'B'],
                [3, 'C'],
            ];

            $result = iterator_to_array(WindowFunctions::lead($data, 'value', 'next_value', 1, 'END'));

            expect($result)->toBe([
                ['id', 'value', 'next_value'],
                [1, 'A', 'B'],
                [2, 'B', 'C'],
                [3, 'C', 'END'],
            ]);
        });

        it('supports partition by field', function () {
            $data = [
                ['category', 'value'],
                ['A', 1],
                ['A', 2],
                ['B', 3],
                ['B', 4],
                ['A', 5],
            ];

            $result = iterator_to_array(WindowFunctions::lead($data, 'value', 'next_value', 1, null, 'category'));

            expect($result)->toBe([
                ['category', 'value', 'next_value'],
                ['A', 1, 2],
                ['A', 2, 5],
                ['B', 3, 4],
                ['B', 4, null],
                ['A', 5, null],
            ]);
        });
    });

    describe('rowNumber()', function () {
        it('assigns sequential row numbers', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 92],
            ];

            $result = iterator_to_array(WindowFunctions::rowNumber($data, 'row_num'));

            expect($result)->toBe([
                ['name', 'score', 'row_num'],
                ['Alice', 95, 1],
                ['Bob', 87, 2],
                ['Charlie', 92, 3],
            ]);
        });

        it('supports partition by field', function () {
            $data = [
                ['category', 'value'],
                ['A', 10],
                ['A', 20],
                ['B', 30],
                ['B', 40],
                ['A', 50],
            ];

            $result = iterator_to_array(WindowFunctions::rowNumber($data, 'row_num', 'category'));

            expect($result)->toBe([
                ['category', 'value', 'row_num'],
                ['A', 10, 1],
                ['A', 20, 2],
                ['B', 30, 1],
                ['B', 40, 2],
                ['A', 50, 3],
            ]);
        });

        it('supports order by field', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 92],
            ];

            $result = iterator_to_array(WindowFunctions::rowNumber($data, 'rank', null, 'score'));

            expect($result)->toBe([
                ['name', 'score', 'rank'],
                ['Bob', 87, 1],
                ['Charlie', 92, 2],
                ['Alice', 95, 3],
            ]);
        });

        it('supports partition and order by', function () {
            $data = [
                ['dept', 'name', 'salary'],
                ['Sales', 'Alice', 50000],
                ['Sales', 'Bob', 60000],
                ['IT', 'Charlie', 70000],
                ['IT', 'Dave', 65000],
                ['Sales', 'Eve', 55000],
            ];

            $result = iterator_to_array(WindowFunctions::rowNumber($data, 'rank', 'dept', 'salary'));

            expect($result)->toBe([
                ['dept', 'name', 'salary', 'rank'],
                ['Sales', 'Alice', 50000, 1],
                ['Sales', 'Eve', 55000, 2],
                ['Sales', 'Bob', 60000, 3],
                ['IT', 'Dave', 65000, 1],
                ['IT', 'Charlie', 70000, 2],
            ]);
        });
    });

    describe('rank()', function () {
        it('assigns rank with gaps for ties', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 95],
                ['Dave', 87],
                ['Eve', 92],
            ];

            $result = iterator_to_array(WindowFunctions::rank($data, 'score', 'rank'));

            expect($result)->toBe([
                ['name', 'score', 'rank'],
                ['Bob', 87, 1],
                ['Dave', 87, 1],
                ['Eve', 92, 3],
                ['Alice', 95, 4],
                ['Charlie', 95, 4],
            ]);
        });

        it('supports descending order', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 95],
            ];

            $result = iterator_to_array(WindowFunctions::rank($data, 'score', 'rank', null, true));

            expect($result)->toBe([
                ['name', 'score', 'rank'],
                ['Alice', 95, 1],
                ['Charlie', 95, 1],
                ['Bob', 87, 3],
            ]);
        });

        it('supports partition by field', function () {
            $data = [
                ['dept', 'name', 'score'],
                ['Sales', 'Alice', 95],
                ['Sales', 'Bob', 87],
                ['IT', 'Charlie', 95],
                ['IT', 'Dave', 95],
                ['Sales', 'Eve', 87],
            ];

            $result = iterator_to_array(WindowFunctions::rank($data, 'score', 'rank', 'dept'));

            expect($result)->toBe([
                ['dept', 'name', 'score', 'rank'],
                ['Sales', 'Bob', 87, 1],
                ['Sales', 'Eve', 87, 1],
                ['Sales', 'Alice', 95, 3],
                ['IT', 'Charlie', 95, 1],
                ['IT', 'Dave', 95, 1],
            ]);
        });
    });

    describe('denseRank()', function () {
        it('assigns rank without gaps', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 95],
                ['Dave', 87],
                ['Eve', 92],
            ];

            $result = iterator_to_array(WindowFunctions::denseRank($data, 'score', 'rank'));

            expect($result)->toBe([
                ['name', 'score', 'rank'],
                ['Bob', 87, 1],
                ['Dave', 87, 1],
                ['Eve', 92, 2],
                ['Alice', 95, 3],
                ['Charlie', 95, 3],
            ]);
        });

        it('supports descending order', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 92],
            ];

            $result = iterator_to_array(WindowFunctions::denseRank($data, 'score', 'rank', null, true));

            expect($result)->toBe([
                ['name', 'score', 'rank'],
                ['Alice', 95, 1],
                ['Charlie', 92, 2],
                ['Bob', 87, 3],
            ]);
        });
    });

    describe('percentRank()', function () {
        it('calculates percentage rank', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
                ['Bob', 87],
                ['Charlie', 92],
                ['Dave', 87],
            ];

            $result = iterator_to_array(WindowFunctions::percentRank($data, 'score', 'pct_rank'));

            expect($result[0])->toBe(['name', 'score', 'pct_rank']);
            expect($result[1][2])->toBe(0.0); // Bob - lowest
            expect($result[2][2])->toBe(0.0); // Dave - tied with Bob
            expect($result[3][2])->toBeGreaterThan(0.0);
            expect($result[4][2])->toBe(1.0); // Alice - highest
        });

        it('handles single row', function () {
            $data = [
                ['name', 'score'],
                ['Alice', 95],
            ];

            $result = iterator_to_array(WindowFunctions::percentRank($data, 'score', 'pct_rank'));

            expect($result)->toBe([
                ['name', 'score', 'pct_rank'],
                ['Alice', 95, 0.0],
            ]);
        });

        it('supports partition by field', function () {
            $data = [
                ['dept', 'score'],
                ['Sales', 95],
                ['Sales', 87],
                ['IT', 92],
                ['IT', 88],
                ['Sales', 90],
            ];

            $result = iterator_to_array(WindowFunctions::percentRank($data, 'score', 'pct_rank', 'dept'));

            // Sales partition: 87, 90, 95 -> 0.0, 0.5, 1.0
            // IT partition: 88, 92 -> 0.0, 1.0
            expect($result[0])->toBe(['dept', 'score', 'pct_rank']);
            // Check that partitions reset
            expect($result[1][2])->toBe(0.0); // Sales 87 - first in partition
            expect($result[4][2])->toBe(0.0); // IT 88 - first in partition
        });
    });

    describe('edge cases', function () {
        it('handles empty data gracefully', function () {
            $data = [
                ['id', 'value'],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'prev'));

            expect($result)->toBe([
                ['id', 'value', 'prev'],
            ]);
        });

        it('handles null values in window functions', function () {
            $data = [
                ['id', 'value'],
                [1, 10],
                [2, null],
                [3, 30],
            ];

            $result = iterator_to_array(WindowFunctions::lag($data, 'value', 'prev'));

            expect($result)->toBe([
                ['id', 'value', 'prev'],
                [1, 10, null],
                [2, null, 10],
                [3, 30, null],
            ]);
        });
    });
});
