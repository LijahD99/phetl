<?php

declare(strict_types=1);

namespace Tests\Unit\Transform\Values;

use InvalidArgumentException;
use Phetl\Transform\Values\ConditionalTransformer;

describe('when()', function () {
    it('applies condition with then/else values', function () {
        $data = [
            ['status', 'value'],
            ['active', 10],
            ['inactive', 20],
            ['active', 30],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'status',
                fn ($val) => $val === 'active',
                'result',
                'yes',
                'no'
            )
        );

        expect($result[0])->toBe(['status', 'value', 'result']);
        expect($result[1])->toBe(['active', 10, 'yes']);
        expect($result[2])->toBe(['inactive', 20, 'no']);
        expect($result[3])->toBe(['active', 30, 'yes']);
    });

    it('supports callback for then value', function () {
        $data = [
            ['amount'],
            [100],
            [50],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'amount',
                fn ($val) => $val >= 100,
                'discount',
                fn ($row) => $row[0] * 0.1,
                0
            )
        );

        expect($result[1][1])->toBe(10.0);
        expect($result[2][1])->toBe(0);
    });

    it('supports callback for else value', function () {
        $data = [
            ['amount'],
            [100],
            [50],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'amount',
                fn ($val) => $val >= 100,
                'fee',
                0,
                fn ($row) => $row[0] * 0.05
            )
        );

        expect($result[1][1])->toBe(0);
        expect($result[2][1])->toBe(2.5);
    });

    it('handles null values in condition', function () {
        $data = [
            ['value'],
            [null],
            [5],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'value',
                fn ($val) => $val !== null,
                'result',
                'has value',
                'no value'
            )
        );

        expect($result[1][1])->toBe('no value');
        expect($result[2][1])->toBe('has value');
    });

    it('throws exception for invalid field', function () {
        $data = [
            ['value'],
            [1],
        ];

        expect(fn () => iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'missing',
                fn ($val) => true,
                'result',
                'yes',
                'no'
            )
        ))->toThrow(InvalidArgumentException::class);
    });

    it('can update existing field', function () {
        $data = [
            ['status'],
            ['active'],
            ['inactive'],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::when(
                $data,
                'status',
                fn ($val) => $val === 'active',
                'status',
                1,
                0
            )
        );

        expect($result[1][0])->toBe(1);
        expect($result[2][0])->toBe(0);
    });
});

describe('coalesce()', function () {
    it('returns first non-null value', function () {
        $data = [
            ['a', 'b', 'c'],
            [null, null, 'value'],
            [null, 'first', 'second'],
            ['primary', 'backup', null],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'b', 'c'])
        );

        expect($result[1][3])->toBe('value');
        expect($result[2][3])->toBe('first');
        expect($result[3][3])->toBe('primary');
    });

    it('returns null when all fields are null', function () {
        $data = [
            ['a', 'b', 'c'],
            [null, null, null],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'b', 'c'])
        );

        expect($result[1][3])->toBeNull();
    });

    it('treats empty string as non-null', function () {
        $data = [
            ['a', 'b', 'c'],
            [null, '', 'value'],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'b', 'c'])
        );

        expect($result[1][3])->toBe('');
    });

    it('treats zero as non-null', function () {
        $data = [
            ['a', 'b', 'c'],
            [null, 0, 5],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'b', 'c'])
        );

        expect($result[1][3])->toBe(0);
    });

    it('treats false as non-null', function () {
        $data = [
            ['a', 'b', 'c'],
            [null, false, true],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'b', 'c'])
        );

        expect($result[1][3])->toBe(false);
    });

    it('throws exception for invalid field', function () {
        $data = [
            ['a'],
            [null],
        ];

        expect(fn () => iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', ['a', 'missing'])
        ))->toThrow(InvalidArgumentException::class);
    });

    it('requires at least one field', function () {
        $data = [
            ['a'],
            [1],
        ];

        expect(fn () => iterator_to_array(
            ConditionalTransformer::coalesce($data, 'result', [])
        ))->toThrow(InvalidArgumentException::class);
    });
});

describe('nullIf()', function () {
    it('returns null when condition is true', function () {
        $data = [
            ['value'],
            [0],
            [5],
            [0],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::nullIf(
                $data,
                'value',
                'result',
                fn ($val) => $val === 0
            )
        );

        expect($result[1][1])->toBeNull();
        expect($result[2][1])->toBe(5);
        expect($result[3][1])->toBeNull();
    });

    it('preserves original value when condition is false', function () {
        $data = [
            ['status'],
            ['active'],
            ['inactive'],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::nullIf(
                $data,
                'status',
                'result',
                fn ($val) => $val === 'deleted'
            )
        );

        expect($result[1][1])->toBe('active');
        expect($result[2][1])->toBe('inactive');
    });

    it('can update existing field', function () {
        $data = [
            ['value'],
            [-1],
            [5],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::nullIf(
                $data,
                'value',
                'value',
                fn ($val) => $val < 0
            )
        );

        expect($result[1][0])->toBeNull();
        expect($result[2][0])->toBe(5);
    });

    it('handles null input values', function () {
        $data = [
            ['value'],
            [null],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::nullIf(
                $data,
                'value',
                'result',
                fn ($val) => $val === null
            )
        );

        expect($result[1][1])->toBeNull();
    });
});

describe('ifNull()', function () {
    it('replaces null with default value', function () {
        $data = [
            ['value'],
            [null],
            [5],
            [null],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::ifNull($data, 'value', 'result', 0)
        );

        expect($result[1][1])->toBe(0);
        expect($result[2][1])->toBe(5);
        expect($result[3][1])->toBe(0);
    });

    it('preserves non-null values', function () {
        $data = [
            ['name'],
            ['John'],
            [''],
            [null],
            [0],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::ifNull($data, 'name', 'result', 'Unknown')
        );

        expect($result[1][1])->toBe('John');
        expect($result[2][1])->toBe('');
        expect($result[3][1])->toBe('Unknown');
        expect($result[4][1])->toBe(0);
    });

    it('supports callback for default value', function () {
        $data = [
            ['value', 'backup'],
            [null, 10],
            [5, 20],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::ifNull(
                $data,
                'value',
                'result',
                fn ($row) => $row[1]
            )
        );

        expect($result[1][2])->toBe(10);
        expect($result[2][2])->toBe(5);
    });
});

describe('case()', function () {
    it('evaluates multiple conditions in order', function () {
        $data = [
            ['score'],
            [95],
            [85],
            [75],
            [65],
            [55],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::case(
                $data,
                'score',
                'grade',
                [
                    [fn ($val) => $val >= 90, 'A'],
                    [fn ($val) => $val >= 80, 'B'],
                    [fn ($val) => $val >= 70, 'C'],
                    [fn ($val) => $val >= 60, 'D'],
                ],
                'F'
            )
        );

        expect($result[1][1])->toBe('A');
        expect($result[2][1])->toBe('B');
        expect($result[3][1])->toBe('C');
        expect($result[4][1])->toBe('D');
        expect($result[5][1])->toBe('F');
    });

    it('supports callbacks for result values', function () {
        $data = [
            ['amount'],
            [1000],
            [500],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::case(
                $data,
                'amount',
                'fee',
                [
                    [fn ($val) => $val >= 1000, fn ($row) => $row[0] * 0.01],
                    [fn ($val) => $val >= 500, fn ($row) => $row[0] * 0.02],
                ],
                fn ($row) => $row[0] * 0.03
            )
        );

        expect($result[1][1])->toBe(10.0);
        expect($result[2][1])->toBe(10.0);
    });

    it('returns default when no conditions match', function () {
        $data = [
            ['value'],
            [10],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::case(
                $data,
                'value',
                'result',
                [
                    [fn ($val) => $val > 100, 'high'],
                    [fn ($val) => $val > 50, 'medium'],
                ],
                'low'
            )
        );

        expect($result[1][1])->toBe('low');
    });

    it('stops at first matching condition', function () {
        $data = [
            ['value'],
            [100],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::case(
                $data,
                'value',
                'result',
                [
                    [fn ($val) => $val >= 50, 'first'],
                    [fn ($val) => $val >= 100, 'second'],
                ],
                'default'
            )
        );

        expect($result[1][1])->toBe('first');
    });

    it('handles empty conditions with default', function () {
        $data = [
            ['value'],
            [5],
        ];

        $result = iterator_to_array(
            ConditionalTransformer::case(
                $data,
                'value',
                'result',
                [],
                'default'
            )
        );

        expect($result[1][1])->toBe('default');
    });
});

describe('Table conditional methods', function () {
    it('when() adds conditional field', function () {
        $table = \Phetl\Table::fromArray([
            ['status'],
            ['active'],
            ['inactive'],
        ]);

        $result = $table
            ->when('status', fn ($val) => $val === 'active', 'enabled', true, false)
            ->toArray();

        expect($result)->toBe([
            ['status', 'enabled'],
            ['active', true],
            ['inactive', false],
        ]);
    });

    it('coalesce() combines null fields', function () {
        $table = \Phetl\Table::fromArray([
            ['primary', 'secondary'],
            [null, 'backup'],
            ['main', null],
        ]);

        $result = $table
            ->coalesce('value', ['primary', 'secondary'])
            ->toArray();

        expect($result)->toBe([
            ['primary', 'secondary', 'value'],
            [null, 'backup', 'backup'],
            ['main', null, 'main'],
        ]);
    });

    it('nullIf() conditionally nullifies values', function () {
        $table = \Phetl\Table::fromArray([
            ['value'],
            [-999],
            [5],
        ]);

        $result = $table
            ->nullIf('value', 'clean', fn ($val) => $val === -999)
            ->toArray();

        expect($result)->toBe([
            ['value', 'clean'],
            [-999, null],
            [5, 5],
        ]);
    });

    it('ifNull() replaces null values', function () {
        $table = \Phetl\Table::fromArray([
            ['name'],
            [null],
            ['John'],
        ]);

        $result = $table
            ->ifNull('name', 'display', 'Unknown')
            ->toArray();

        expect($result)->toBe([
            ['name', 'display'],
            [null, 'Unknown'],
            ['John', 'John'],
        ]);
    });

    it('case() handles multiple conditions', function () {
        $table = \Phetl\Table::fromArray([
            ['score'],
            [95],
            [75],
            [55],
        ]);

        $result = $table
            ->case('score', 'grade', [
                [fn ($val) => $val >= 90, 'A'],
                [fn ($val) => $val >= 70, 'C'],
            ], 'F')
            ->toArray();

        expect($result)->toBe([
            ['score', 'grade'],
            [95, 'A'],
            [75, 'C'],
            [55, 'F'],
        ]);
    });

    it('chains conditional operations', function () {
        $table = \Phetl\Table::fromArray([
            ['value', 'backup'],
            [null, 10],
            [5, 20],
        ]);

        $result = $table
            ->coalesce('combined', ['value', 'backup'])
            ->when('combined', fn ($val) => $val >= 10, 'category', 'high', 'low')
            ->toArray();

        expect($result)->toBe([
            ['value', 'backup', 'combined', 'category'],
            [null, 10, 10, 'high'],
            [5, 20, 5, 'low'],
        ]);
    });
});
