<?php

declare(strict_types=1);

use Phetl\Table;

describe('Deduplication Pipeline Integration', function () {
    it('removes duplicates from CSV data', function () {
        $data = [
            ['product', 'category', 'price'],
            ['Laptop', 'Electronics', 999],
            ['Mouse', 'Electronics', 25],
            ['Laptop', 'Electronics', 999],  // Duplicate
            ['Keyboard', 'Electronics', 75],
            ['Mouse', 'Electronics', 25],    // Duplicate
        ];

        $result = Table::fromArray($data)
            ->distinct()
            ->toArray();

        expect($result)->toBe([
            ['product', 'category', 'price'],
            ['Laptop', 'Electronics', 999],
            ['Mouse', 'Electronics', 25],
            ['Keyboard', 'Electronics', 75],
        ]);
    });

    it('finds duplicates for data validation', function () {
        $data = [
            ['id', 'email', 'name'],
            [1, 'alice@example.com', 'Alice'],
            [2, 'bob@example.com', 'Bob'],
            [3, 'alice@example.com', 'Alice Smith'],  // Duplicate email
            [4, 'charlie@example.com', 'Charlie'],
        ];

        $result = Table::fromArray($data)
            ->duplicates('email')
            ->toArray();

        expect($result)->toBe([
            ['id', 'email', 'name'],
            [1, 'alice@example.com', 'Alice'],
        ]);
    });

    it('counts frequency of values', function () {
        $data = [
            ['browser', 'country', 'visits'],
            ['Chrome', 'US', 100],
            ['Firefox', 'UK', 50],
            ['Chrome', 'US', 200],   // Same browser/country
            ['Safari', 'US', 75],
            ['Chrome', 'CA', 150],   // Same browser, different country
        ];

        $result = Table::fromArray($data)
            ->countDistinct(['browser', 'country'])
            ->toArray();

        expect($result)->toBe([
            ['browser', 'country', 'visits', 'count'],
            ['Chrome', 'US', 100, 2],
            ['Firefox', 'UK', 50, 1],
            ['Safari', 'US', 75, 1],
            ['Chrome', 'CA', 150, 1],
        ]);
    });

    it('chains deduplication with aggregation', function () {
        $data = [
            ['product', 'category', 'sales'],
            ['Laptop', 'Electronics', 10],
            ['Mouse', 'Electronics', 50],
            ['Laptop', 'Electronics', 10],  // Duplicate
            ['Desk', 'Furniture', 5],
            ['Mouse', 'Electronics', 50],   // Duplicate
        ];

        $result = Table::fromArray($data)
            ->distinct()
            ->aggregate(['category'], ['sales' => 'sum'])
            ->toArray();

        expect($result)->toBe([
            ['category', 'sales'],
            ['Electronics', 60],
            ['Furniture', 5],
        ]);
    });

    it('chains deduplication with joins', function () {
        $sales = [
            ['product_id', 'quantity'],
            [1, 10],
            [2, 20],
            [1, 10],  // Duplicate
            [3, 15],
        ];

        $products = [
            ['id', 'name'],
            [1, 'Laptop'],
            [2, 'Mouse'],
            [3, 'Keyboard'],
        ];

        $result = Table::fromArray($sales)
            ->distinct()
            ->innerJoin(
                Table::fromArray($products),
                'product_id',
                'id'
            )
            ->toArray();

        expect($result)->toBe([
            ['product_id', 'quantity', 'name'],
            [1, 10, 'Laptop'],
            [2, 20, 'Mouse'],
            [3, 15, 'Keyboard'],
        ]);
    });

    it('validates data quality with isUnique()', function () {
        $validData = [
            ['id', 'email'],
            [1, 'alice@example.com'],
            [2, 'bob@example.com'],
            [3, 'charlie@example.com'],
        ];

        $invalidData = [
            ['id', 'email'],
            [1, 'alice@example.com'],
            [2, 'bob@example.com'],
            [3, 'alice@example.com'],  // Duplicate email
        ];

        expect(Table::fromArray($validData)->isUnique('email'))->toBeTrue();
        expect(Table::fromArray($invalidData)->isUnique('email'))->toBeFalse();
    });

    it('processes user activity logs', function () {
        $logs = [
            ['user_id', 'action', 'timestamp'],
            [1, 'login', '2024-01-01 10:00'],
            [2, 'view', '2024-01-01 10:05'],
            [1, 'login', '2024-01-01 10:00'],  // Duplicate
            [3, 'login', '2024-01-01 10:10'],
            [2, 'view', '2024-01-01 10:05'],   // Duplicate
            [1, 'logout', '2024-01-01 11:00'],
        ];

        $uniqueLogs = Table::fromArray($logs)
            ->distinct()
            ->toArray();

        expect($uniqueLogs)->toBe([
            ['user_id', 'action', 'timestamp'],
            [1, 'login', '2024-01-01 10:00'],
            [2, 'view', '2024-01-01 10:05'],
            [3, 'login', '2024-01-01 10:10'],
            [1, 'logout', '2024-01-01 11:00'],
        ]);
    });

    it('finds duplicate orders for investigation', function () {
        $orders = [
            ['order_id', 'customer', 'amount'],
            [1001, 'Alice', 99.99],
            [1002, 'Bob', 49.99],
            [1003, 'Alice', 99.99],   // Different order, same customer/amount
            [1004, 'Charlie', 149.99],
            [1005, 'Bob', 49.99],     // Different order, same customer/amount
        ];

        $duplicates = Table::fromArray($orders)
            ->duplicates(['customer', 'amount'])
            ->toArray();

        expect($duplicates)->toBe([
            ['order_id', 'customer', 'amount'],
            [1001, 'Alice', 99.99],
            [1002, 'Bob', 49.99],
        ]);
    });

    it('generates frequency report', function () {
        $events = [
            ['event', 'severity'],
            ['error', 'high'],
            ['warning', 'medium'],
            ['error', 'high'],
            ['info', 'low'],
            ['error', 'high'],
            ['warning', 'medium'],
        ];

        $report = Table::fromArray($events)
            ->countDistinct(['event', 'severity'], 'occurrences')
            ->toArray();

        expect($report)->toBe([
            ['event', 'severity', 'occurrences'],
            ['error', 'high', 3],
            ['warning', 'medium', 2],
            ['info', 'low', 1],
        ]);
    });

    it('cleans and aggregates sensor data', function () {
        $readings = [
            ['sensor_id', 'temperature', 'humidity'],
            ['S1', 22.5, 45],
            ['S2', 23.0, 50],
            ['S1', 22.5, 45],  // Duplicate reading
            ['S3', 21.0, 55],
            ['S2', 23.0, 50],  // Duplicate reading
        ];

        $result = Table::fromArray($readings)
            ->distinct()
            ->toArray();

        expect($result)->toBe([
            ['sensor_id', 'temperature', 'humidity'],
            ['S1', 22.5, 45],
            ['S2', 23.0, 50],
            ['S3', 21.0, 55],
        ]);
    });

    it('handles complex deduplication with filtering', function () {
        $transactions = [
            ['id', 'user', 'amount', 'status'],
            [1, 'Alice', 100, 'completed'],
            [2, 'Bob', 50, 'pending'],
            [3, 'Alice', 100, 'completed'],  // Duplicate
            [4, 'Charlie', 200, 'completed'],
            [5, 'Bob', 50, 'failed'],        // Same user/amount, different status
            [6, 'Alice', 100, 'completed'],  // Another duplicate
        ];

        $result = Table::fromArray($transactions)
            ->filter(fn($row) => $row['status'] === 'completed')
            ->distinct(['user', 'amount'])
            ->toArray();

        expect($result)->toBe([
            ['id', 'user', 'amount', 'status'],
            [1, 'Alice', 100, 'completed'],
            [4, 'Charlie', 200, 'completed'],
        ]);
    });

    it('combines multiple deduplication techniques', function () {
        $data = [
            ['category', 'product', 'price'],
            ['Electronics', 'Laptop', 999],
            ['Electronics', 'Mouse', 25],
            ['Electronics', 'Laptop', 999],  // Duplicate
            ['Furniture', 'Desk', 299],
            ['Electronics', 'Keyboard', 75],
            ['Furniture', 'Desk', 299],      // Duplicate
        ];

        // First get unique products
        $unique = Table::fromArray($data)->distinct();

        // Then count by category
        $categoryCount = $unique->countBy('category');

        // And check uniqueness
        $isProductUnique = $unique->isUnique('product');

        expect($categoryCount->toArray())->toBe([
            ['category', 'count'],
            ['Electronics', 3],
            ['Furniture', 1],
        ]);

        expect($isProductUnique)->toBeTrue(); // After distinct, all products are unique
    });
});
