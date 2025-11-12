<?php

declare(strict_types=1);

use Phetl\Table;
use Phetl\Transform\Validation\Validator;

describe('Validator', function () {
    describe('required()', function () {
        it('validates required fields are present', function () {
            $data = [
                ['name', 'email', 'age'],
                ['Alice', 'alice@example.com', 30],
                ['Bob', 'bob@example.com', 25],
                ['Charlie', 'charlie@example.com', 35],
            ];

            $result = Validator::required($data, ['name', 'email']);

            expect($result['valid'])->toBeTrue();
            expect($result['errors'])->toBeEmpty();
        });

        it('detects missing required values', function () {
            $data = [
                ['name', 'email', 'age'],
                ['Alice', 'alice@example.com', 30],
                ['Bob', null, 25],  // Missing email
                ['Charlie', 'charlie@example.com', 35],
            ];

            $result = Validator::required($data, ['name', 'email']);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0])->toMatchArray([
                'row' => 2,
                'field' => 'email',
                'rule' => 'required',
                'message' => 'Field "email" is required',
            ]);
        });

        it('detects empty string values', function () {
            $data = [
                ['name', 'email'],
                ['Alice', 'alice@example.com'],
                ['Bob', ''],  // Empty string
            ];

            $result = Validator::required($data, ['email']);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });

        it('throws exception for invalid field', function () {
            $data = [
                ['name', 'email'],
                ['Alice', 'alice@example.com'],
            ];

            expect(fn() => Validator::required($data, ['invalid']))
                ->toThrow(InvalidArgumentException::class, "Field 'invalid' not found");
        });
    });

    describe('type()', function () {
        it('validates string types', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ];

            $result = Validator::type($data, 'name', 'string');

            expect($result['valid'])->toBeTrue();
            expect($result['errors'])->toBeEmpty();
        });

        it('validates integer types', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', 25],
            ];

            $result = Validator::type($data, 'age', 'int');

            expect($result['valid'])->toBeTrue();
        });

        it('detects type mismatches', function () {
            $data = [
                ['name', 'age'],
                ['Alice', 30],
                ['Bob', '25'],  // String instead of int
            ];

            $result = Validator::type($data, 'age', 'int');

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0]['row'])->toBe(2);
            expect($result['errors'][0]['field'])->toBe('age');
        });

        it('supports multiple type names', function () {
            $data = [
                ['score', 'price'],
                [95, 19.99],
                [87, 24.99],
            ];

            expect(Validator::type($data, 'score', 'integer')['valid'])->toBeTrue();
            expect(Validator::type($data, 'price', 'float')['valid'])->toBeTrue();
            expect(Validator::type($data, 'price', 'double')['valid'])->toBeTrue();
        });
    });

    describe('range()', function () {
        it('validates numeric ranges', function () {
            $data = [
                ['age', 'score'],
                [25, 85],
                [30, 92],
                [35, 78],
            ];

            $result = Validator::range($data, 'age', 18, 65);

            expect($result['valid'])->toBeTrue();
        });

        it('detects values below minimum', function () {
            $data = [
                ['age'],
                [25],
                [17],  // Below minimum
                [30],
            ];

            $result = Validator::range($data, 'age', 18, 65);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0]['row'])->toBe(2);
        });

        it('detects values above maximum', function () {
            $data = [
                ['age'],
                [25],
                [30],
                [70],  // Above maximum
            ];

            $result = Validator::range($data, 'age', 18, 65);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0]['row'])->toBe(3);
        });

        it('allows null minimum', function () {
            $data = [
                ['score'],
                [50],
                [100],
                [150],  // Above max
            ];

            $result = Validator::range($data, 'score', null, 100);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });

        it('allows null maximum', function () {
            $data = [
                ['score'],
                [5],  // Below min
                [50],
                [100],
            ];

            $result = Validator::range($data, 'score', 10, null);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });
    });

    describe('pattern()', function () {
        it('validates regex patterns', function () {
            $data = [
                ['email'],
                ['alice@example.com'],
                ['bob@test.org'],
                ['charlie@domain.net'],
            ];

            $result = Validator::pattern($data, 'email', '/^[a-z]+@[a-z]+\.[a-z]+$/');

            expect($result['valid'])->toBeTrue();
        });

        it('detects pattern mismatches', function () {
            $data = [
                ['email'],
                ['alice@example.com'],
                ['invalid-email'],  // Doesn't match pattern
                ['bob@test.org'],
            ];

            $result = Validator::pattern($data, 'email', '/^[a-z]+@[a-z]+\.[a-z]+$/');

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0]['row'])->toBe(2);
        });

        it('handles phone number validation', function () {
            $data = [
                ['phone'],
                ['555-1234'],
                ['555-5678'],
                ['1234567'],  // Invalid format
            ];

            $result = Validator::pattern($data, 'phone', '/^\d{3}-\d{4}$/');

            expect($result['valid'])->toBeFalse();
        });
    });

    describe('in()', function () {
        it('validates values are in allowed list', function () {
            $data = [
                ['status'],
                ['active'],
                ['pending'],
                ['active'],
            ];

            $result = Validator::in($data, 'status', ['active', 'pending', 'inactive']);

            expect($result['valid'])->toBeTrue();
        });

        it('detects values not in allowed list', function () {
            $data = [
                ['status'],
                ['active'],
                ['invalid'],  // Not in allowed list
                ['pending'],
            ];

            $result = Validator::in($data, 'status', ['active', 'pending', 'inactive']);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });
    });

    describe('custom()', function () {
        it('validates with custom function', function () {
            $data = [
                ['age'],
                [25],
                [30],
                [35],
            ];

            $isAdult = fn($value) => $value >= 18;
            $result = Validator::custom($data, 'age', $isAdult, 'Must be adult');

            expect($result['valid'])->toBeTrue();
        });

        it('detects custom validation failures', function () {
            $data = [
                ['age'],
                [25],
                [15],  // Not adult
                [30],
            ];

            $isAdult = fn($value) => $value >= 18;
            $result = Validator::custom($data, 'age', $isAdult, 'Must be adult');

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0]['message'])->toBe('Must be adult');
        });
    });

    describe('validate()', function () {
        it('validates multiple rules at once', function () {
            $data = [
                ['name', 'email', 'age'],
                ['Alice', 'alice@example.com', 30],
                ['Bob', 'bob@test.org', 25],
            ];

            $rules = [
                'name' => ['required'],
                'email' => ['required', ['pattern', '/^[a-z]+@[a-z]+\.[a-z]+$/']],
                'age' => ['required', ['type', 'int'], ['range', 18, 65]],
            ];

            $result = Validator::validate($data, $rules);

            expect($result['valid'])->toBeTrue();
            expect($result['errors'])->toBeEmpty();
        });

        it('collects all validation errors', function () {
            $data = [
                ['name', 'email', 'age'],
                ['Alice', 'alice@example.com', 30],
                ['', 'invalid-email', 17],  // Multiple errors
                ['Charlie', 'charlie@test.org', 70],  // Age too high
            ];

            $rules = [
                'name' => ['required'],
                'email' => ['required', ['pattern', '/^[a-z]+@[a-z]+\.[a-z]+$/']],
                'age' => ['required', ['range', 18, 65]],
            ];

            $result = Validator::validate($data, $rules);

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(4); // name required, email pattern, age range * 2
        });
    });

    describe('unique()', function () {
        it('validates field values are unique', function () {
            $data = [
                ['id', 'email'],
                [1, 'alice@example.com'],
                [2, 'bob@example.com'],
                [3, 'charlie@example.com'],
            ];

            $result = Validator::unique($data, 'email');

            expect($result['valid'])->toBeTrue();
        });

        it('detects duplicate values', function () {
            $data = [
                ['id', 'email'],
                [1, 'alice@example.com'],
                [2, 'bob@example.com'],
                [3, 'alice@example.com'],  // Duplicate
            ];

            $result = Validator::unique($data, 'email');

            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });
    });
});

describe('Table validation methods', function () {
    it('validates required fields', function () {
        $table = Table::fromArray([
            ['name', 'email'],
            ['Alice', 'alice@example.com'],
            ['Bob', null],  // Missing email
        ]);

        $result = $table->validateRequired(['name', 'email']);

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toHaveCount(1);
    });

    it('validates with multiple rules', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ]);

        $rules = [
            'name' => ['required'],
            'age' => ['required', ['type', 'int'], ['range', 18, 65]],
        ];

        $result = $table->validate($rules);

        expect($result['valid'])->toBeTrue();
    });

    it('throws exception when validation fails', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 17],  // Too young
        ]);

        expect(fn() => $table->validateOrFail([
            'age' => [['range', 18, 65]],
        ]))->toThrow(RuntimeException::class, 'Validation failed');
    });

    it('filters to valid rows only', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 17],  // Invalid
            ['Charlie', 25],
        ]);

        $result = $table->filterValid([
            'age' => [['range', 18, 65]],
        ])->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Alice', 30],
            ['Charlie', 25],
        ]);
    });

    it('filters to invalid rows only', function () {
        $table = Table::fromArray([
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 17],  // Invalid
            ['Charlie', 25],
        ]);

        $result = $table->filterInvalid([
            'age' => [['range', 18, 65]],
        ])->toArray();

        expect($result)->toBe([
            ['name', 'age'],
            ['Bob', 17],
        ]);
    });
});
