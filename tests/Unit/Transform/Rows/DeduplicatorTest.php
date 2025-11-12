<?php

declare(strict_types=1);

use Phetl\Table;
use Phetl\Transform\Rows\Deduplicator;

describe('Deduplicator', function () {
    describe('distinct()', function () {
        it('removes duplicate rows', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'NYC'],  // Duplicate
                ['Charlie', 35, 'SF'],
                ['Bob', 25, 'LA'],     // Duplicate
            ];

            $result = iterator_to_array(Deduplicator::distinct($data));

            expect($result)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('removes duplicates based on specific field', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 35, 'SF'],    // Same name, different age/city
                ['Charlie', 35, 'SF'],
            ];

            $result = iterator_to_array(Deduplicator::distinct($data, 'name'));

            expect($result)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('removes duplicates based on multiple fields', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'SF'],    // Same name/age, different city
                ['Alice', 30, 'NYC'],   // Exact duplicate
                ['Charlie', 35, 'SF'],
            ];

            $result = iterator_to_array(Deduplicator::distinct($data, ['name', 'age']));

            expect($result)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice/30
                ['Bob', 25, 'LA'],
                ['Charlie', 35, 'SF'],
            ]);
        });

        it('handles empty data', function () {
            $data = [
                ['name', 'age'],
            ];

            $result = iterator_to_array(Deduplicator::distinct($data));

            expect($result)->toBe([
                ['name', 'age'],
            ]);
        });

        it('handles all unique rows', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            $result = iterator_to_array(Deduplicator::distinct($data));

            expect($result)->toBe($data);
        });

        it('handles null values', function () {
            $data = [
                ['name', 'age'],
                ['Alice', null],
                ['Bob', 25],
                ['Alice', null],  // Duplicate with null
            ];

            $result = iterator_to_array(Deduplicator::distinct($data));

            expect($result)->toBe([
                ['name', 'age'],
                ['Alice', null],
                ['Bob', 25],
            ]);
        });

        it('throws exception for invalid field', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
            ];

            expect(fn() => iterator_to_array(Deduplicator::distinct($data, 'invalid')))
                ->toThrow(InvalidArgumentException::class, "Field 'invalid' not found in header");
        });
    });

    describe('unique()', function () {
        it('is an alias for distinct()', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
            ];

            $result = iterator_to_array(Deduplicator::unique($data));

            expect($result)->toBe([
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ]);
        });

        it('works with field parameter', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Alice', 35],
            ];

            $result = iterator_to_array(Deduplicator::unique($data, 'name'));

            expect($result)->toBe([
                ['name', 'age'],
                ['Alice', 30],
            ]);
        });
    });

    describe('duplicates()', function () {
        it('returns only duplicate rows', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],  // Duplicate
                ['Charlie', 35],
                ['Bob', 25],    // Duplicate
            ];

            $result = iterator_to_array(Deduplicator::duplicates($data));

            expect($result)->toBe([
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ]);
        });

        it('returns duplicates based on specific field', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 35, 'SF'],    // Duplicate by name
                ['Charlie', 35, 'SF'],  // Unique
            ];

            $result = iterator_to_array(Deduplicator::duplicates($data, 'name'));

            expect($result)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice (duplicate)
            ]);
        });

        it('returns duplicates based on multiple fields', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'LA'],
                ['Alice', 30, 'SF'],    // Duplicate by name/age
                ['Charlie', 35, 'SF'],  // Unique
            ];

            $result = iterator_to_array(Deduplicator::duplicates($data, ['name', 'age']));

            expect($result)->toBe([
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],   // First Alice/30 (duplicate)
            ]);
        });

        it('returns empty when no duplicates', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            $result = iterator_to_array(Deduplicator::duplicates($data));

            expect($result)->toBe([
                ['name', 'age'],
            ]);
        });
    });

    describe('countDistinct()', function () {
        it('counts occurrences of each unique row', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
                ['Alice', 30],
                ['Bob', 25],
            ];

            $result = iterator_to_array(Deduplicator::countDistinct($data));

            expect($result)->toBe([
                ['name', 'age', 'count'],
                ['Alice', 30, 3],
                ['Bob', 25, 2],
            ]);
        });

        it('counts based on specific field', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Alice', 35],
                ['Bob', 25],
                ['Alice', 40],
            ];

            $result = iterator_to_array(Deduplicator::countDistinct($data, 'name'));

            expect($result)->toBe([
                ['name', 'age', 'count'],
                ['Alice', 30, 3],  // First Alice, count = 3
                ['Bob', 25, 1],
            ]);
        });

        it('supports custom count field name', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Alice', 30],
            ];

            $result = iterator_to_array(Deduplicator::countDistinct($data, null, 'frequency'));

            expect($result)->toBe([
                ['name', 'age', 'frequency'],
                ['Alice', 30, 2],
            ]);
        });

        it('counts based on multiple fields', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Alice', 30, 'LA'],
                ['Alice', 30, 'NYC'],
                ['Bob', 25, 'SF'],
            ];

            $result = iterator_to_array(Deduplicator::countDistinct($data, ['name', 'age']));

            expect($result)->toBe([
                ['name', 'age', 'city', 'count'],
                ['Alice', 30, 'NYC', 3],  // First Alice/30, count = 3
                ['Bob', 25, 'SF', 1],
            ]);
        });
    });

    describe('isUnique()', function () {
        it('returns true when all rows are unique', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Charlie', 35],
            ];

            $result = Deduplicator::isUnique($data);

            expect($result)->toBeTrue();
        });

        it('returns false when duplicates exist', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
                ['Alice', 30],
            ];

            $result = Deduplicator::isUnique($data);

            expect($result)->toBeFalse();
        });

        it('checks uniqueness based on specific field', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Alice', 35],  // Same name, different age
                ['Bob', 25],
            ];

            expect(Deduplicator::isUnique($data))->toBeTrue();
            expect(Deduplicator::isUnique($data, 'name'))->toBeFalse();
        });

        it('checks uniqueness based on multiple fields', function () {
            $data = [
                ['name', 'age', 'city'],
                ['Alice', 30, 'NYC'],
                ['Alice', 30, 'LA'],  // Same name/age, different city
                ['Bob', 25, 'SF'],
            ];

            expect(Deduplicator::isUnique($data))->toBeTrue();
            expect(Deduplicator::isUnique($data, ['name', 'age']))->toBeFalse();
        });

        it('returns true for empty data', function () {
            $data = [
                ['name', 'age'],
            ];

            $result = Deduplicator::isUnique($data);

            expect($result)->toBeTrue();
        });
    });
});

describe('Table deduplication methods', function () {
    it('distinct() removes duplicate rows', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Alice', 30],
        ]);

        $result = $table->distinct()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ]);
    });

    it('distinct() works with field parameter', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 35],
        ]);

        $result = $table->distinct('name')->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('unique() is an alias', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
        ]);

        $result = $table->unique()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('duplicates() returns only duplicates', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Alice', 30],
        ]);

        $result = $table->duplicates()->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
        ]);
    });

    it('countDistinct() counts unique rows', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
            ['Bob', 25],
        ]);

        $result = $table->countDistinct()->toArray();

        expect($result)->toBe([
            ['name', 'age', 'count'],
            ['Alice', 30, 2],
            ['Bob', 25, 1],
        ]);
    });

    it('isUnique() checks uniqueness', function () {
        $unique = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ]);

        $duplicates = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Alice', 30],
        ]);

        expect($unique->isUnique())->toBeTrue();
        expect($duplicates->isUnique())->toBeFalse();
    });

    it('chains with other operations', function () {
        $table = Table::fromArray([
            ['name', 'age', 'score'],
            ['Alice', 30, 85],
            ['Bob', 25, 90],
            ['Alice', 30, 85],  // Duplicate
            ['Charlie', 35, 78],
            ['Bob', 25, 90],    // Duplicate
        ]);

        $result = $table
            ->distinct()
            ->filter(fn($row) => $row['score'] >= 80)
            ->selectColumns('name', 'score')
            ->toArray();

        expect($result)->toBe([
            ['name', 'score'],
            ['Alice', 85],
            ['Bob', 90],
        ]);
    });
});
