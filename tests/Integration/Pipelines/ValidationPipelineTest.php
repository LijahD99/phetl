<?php

declare(strict_types=1);

use Phetl\Table;

describe('Validation Pipeline Integration', function () {
    it('validates user registration data', function () {
        $data = [
            ['username', 'email', 'age', 'status'],
            ['alice', 'alice@example.com', 25, 'active'],
            ['bob', 'bob@example.com', 30, 'active'],
            ['charlie', 'invalid-email', 17, 'pending'],  // Invalid email and age
        ];

        $table = Table::fromArray($data);

        $result = $table->validate([
            'username' => ['required'],
            'email' => ['required', ['pattern', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/']],
            'age' => ['required', ['type', 'int'], ['range', 18, null]],
            'status' => ['required', ['in', ['active', 'pending', 'inactive']]],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(2); // Invalid email and age
    });

    it('filters out invalid rows in data cleaning pipeline', function () {
        $data = [
            ['product', 'price', 'quantity'],
            ['Laptop', 999.99, 10],
            ['Mouse', -5.00, 50],    // Invalid price
            ['Keyboard', 75.50, 20],
            ['Monitor', 299.99, -5],  // Invalid quantity
        ];

        $valid = Table::fromArray($data)
            ->filterValid([
                'price' => [['range', 0, null]],
                'quantity' => [['range', 0, null]],
            ])
            ->toArray();

        expect($valid)->toBe([
            ['product', 'price', 'quantity'],
            ['Laptop', 999.99, 10],
            ['Keyboard', 75.50, 20],
        ]);
    });

    it('generates error report for data quality check', function () {
        $data = [
            ['id', 'email', 'phone'],
            [1, 'alice@example.com', '555-1234'],
            [2, 'invalid', '555-5678'],           // Invalid email
            [3, 'bob@test.org', '12345'],         // Invalid phone
            [4, 'alice@example.com', '555-9012'], // Duplicate email
        ];

        $table = Table::fromArray($data);

        $result = $table->validate([
            'email' => [['pattern', '/^[a-z]+@[a-z]+\.[a-z]+$/'], ['unique']],
            'phone' => [['pattern', '/^\d{3}-\d{4}$/']],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(3); // 2 pattern errors + 1 duplicate
    });

    it('validates and stops processing on error', function () {
        $data = [
            ['age', 'score'],
            [25, 85],
            [17, 92],  // Age too low
        ];

        $table = Table::fromArray($data);

        expect(fn() => $table->validateOrFail([
            'age' => [['range', 18, 65]],
        ]))->toThrow(RuntimeException::class, 'Validation failed');
    });

    it('validates required fields before processing', function () {
        $data = [
            ['name', 'email', 'amount'],
            ['Alice', 'alice@example.com', 100],
            ['Bob', null, 50],  // Missing email
            ['Charlie', 'charlie@example.com', null],  // Missing amount
        ];

        $invalid = Table::fromArray($data)
            ->filterInvalid([
                'name' => ['required'],
                'email' => ['required'],
                'amount' => ['required'],
            ])
            ->toArray();

        expect($invalid)->toBe([
            ['name', 'email', 'amount'],
            ['Bob', null, 50],
            ['Charlie', 'charlie@example.com', null],
        ]);
    });

    it('validates data types before aggregation', function () {
        $data = [
            ['category', 'amount'],
            ['A', 100],
            ['B', '50'],  // String instead of number
            ['A', 200],
        ];

        $table = Table::fromArray($data);

        $result = $table->validate([
            'amount' => [['type', 'int']],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'][0]['row'])->toBe(2);
        expect($result['errors'][0]['field'])->toBe('amount');
    });

    it('validates unique constraints', function () {
        $data = [
            ['id', 'username', 'email'],
            [1, 'alice', 'alice@example.com'],
            [2, 'bob', 'bob@example.com'],
            [3, 'charlie', 'alice@example.com'],  // Duplicate email
        ];

        $result = Table::fromArray($data)->validate([
            'id' => [['unique']],
            'email' => [['unique']],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1); // Only email duplicate
        expect($result['errors'][0]['field'])->toBe('email');
    });

    it('validates with custom business rules', function () {
        $data = [
            ['product', 'price', 'discount'],
            ['Laptop', 1000, 100],
            ['Mouse', 50, 60],  // Discount > price
            ['Keyboard', 75, 10],
        ];

        $table = Table::fromArray($data);

        // Custom validation: discount must be less than price
        $result = $table->validate([
            'discount' => [['custom', fn($v) => $v < 100, 'Discount too high']],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1);
        expect($result['errors'][0]['message'])->toBe('Discount too high');
    });

    it('chains validation with other transformations', function () {
        $data = [
            ['name', 'age', 'score'],
            ['Alice', 30, 85],
            ['Bob', 17, 92],    // Too young
            ['Charlie', 25, 78],
            ['David', 22, 65],
        ];

        $result = Table::fromArray($data)
            ->filterValid([
                'age' => [['range', 18, 65]],
            ])
            ->filter(fn($row) => $row['score'] >= 75)
            ->selectColumns('name', 'score')
            ->toArray();

        expect($result)->toBe([
            ['name', 'score'],
            ['Alice', 85],
            ['Charlie', 78],
        ]);
    });

    it('validates email format in contact list', function () {
        $contacts = [
            ['name', 'email'],
            ['Alice', 'alice@company.com'],
            ['Bob', 'bob@invalid'],  // Invalid email
            ['Charlie', 'charlie.smith@example.org'],
            ['David', 'not-an-email'],  // Invalid email
        ];

        $invalidContacts = Table::fromArray($contacts)
            ->filterInvalid([
                'email' => [['pattern', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/']],
            ])
            ->toArray();

        expect($invalidContacts)->toBe([
            ['name', 'email'],
            ['Bob', 'bob@invalid'],
            ['David', 'not-an-email'],
        ]);
    });

    it('validates product inventory data', function () {
        $inventory = [
            ['sku', 'name', 'price', 'quantity', 'status'],
            ['SKU001', 'Laptop', 999.99, 10, 'active'],
            ['SKU002', '', 50.00, 5, 'active'],           // Missing name
            ['SKU003', 'Keyboard', -10.00, 20, 'active'], // Negative price
            ['SKU004', 'Monitor', 299.99, 0, 'pending'],
            ['SKU005', 'Mouse', 25.00, 100, 'invalid'],   // Invalid status
        ];

        $result = Table::fromArray($inventory)->validate([
            'sku' => ['required'],
            'name' => ['required'],
            'price' => [['range', 0, null]],
            'quantity' => [['type', 'int'], ['range', 0, null]],
            'status' => [['in', ['active', 'inactive', 'pending']]],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(3); // Missing name, negative price, invalid status
    });

    it('validates phone numbers with multiple formats', function () {
        $data = [
            ['name', 'phone'],
            ['Alice', '555-1234'],
            ['Bob', '555-5678'],
            ['Charlie', '1234567'],  // Invalid format
            ['David', '555-9012'],
        ];

        $result = Table::fromArray($data)->validate([
            'phone' => [['pattern', '/^\d{3}-\d{4}$/']],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1);
        expect($result['errors'][0]['row'])->toBe(3);
    });

    it('validates age ranges for different categories', function () {
        $data = [
            ['name', 'age', 'category'],
            ['Alice', 25, 'adult'],
            ['Bob', 15, 'teen'],
            ['Charlie', 5, 'child'],
            ['David', 70, 'senior'],
            ['Eve', 10, 'adult'],  // Too young for adult
        ];

        $result = Table::fromArray($data)
            ->filter(fn($row) => $row['category'] === 'adult')
            ->validateRequired(['name', 'age']);

        expect($result['valid'])->toBeTrue();
    });

    it('generates comprehensive validation report', function () {
        $users = [
            ['id', 'username', 'email', 'age', 'role'],
            [1, 'alice', 'alice@example.com', 30, 'admin'],
            [2, '', 'bob@test.org', 25, 'user'],         // Missing username
            [3, 'charlie', 'invalid-email', 17, 'user'], // Invalid email, age too low
            [4, 'david', 'david@company.com', 45, 'superadmin'], // Invalid role
        ];

        $result = Table::fromArray($users)->validate([
            'username' => ['required', ['pattern', '/^[a-z]{3,20}$/']],
            'email' => ['required', ['pattern', '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/']],
            'age' => ['required', ['type', 'int'], ['range', 18, 65]],
            'role' => ['required', ['in', ['admin', 'user', 'moderator']]],
        ]);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(5); // username, email, age, role errors

        // Check specific errors
        $errorFields = array_column($result['errors'], 'field');
        expect($errorFields)->toContain('username');
        expect($errorFields)->toContain('email');
        expect($errorFields)->toContain('age');
        expect($errorFields)->toContain('role');
    });
});
