<?php

declare(strict_types=1);

namespace Tests\Unit\Transform\Values;

use InvalidArgumentException;
use Phetl\Transform\Values\ConditionalTransformer;

describe('when()', function () {
    it('applies condition with then/else values', function () {
        $headers = ['status', 'value'];
        $data = [
            ['active', 10],
            ['inactive', 20],
            ['active', 30],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::when(
            $headers,
            $data,
            'status',
            fn ($val) => $val === 'active',
            'result',
            'yes',
            'no'
        );

        expect($resultHeaders)->toBe(['status', 'value', 'result']);
        expect($resultData[0])->toBe(['active', 10, 'yes']);
        expect($resultData[1])->toBe(['inactive', 20, 'no']);
        expect($resultData[2])->toBe(['active', 30, 'yes']);
    });

    it('supports callback for then value', function () {
        $headers = ['amount'];
        $data = [
            [100],
            [50],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::when(
            $headers,
            $data,
            'amount',
            fn ($val) => $val >= 100,
            'discount',
            fn ($row) => $row[0] * 0.1,
            0
        );

        expect($resultData[0][1])->toBe(10.0);
        expect($resultData[1][1])->toBe(0);
    });

    it('supports callback for else value', function () {
        $headers = ['amount'];
        $data = [
            [100],
            [50],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::when(
            $headers,
            $data,
            'amount',
            fn ($val) => $val >= 100,
            'fee',
            0,
            fn ($row) => $row[0] * 0.05
        );

        expect($resultData[0][1])->toBe(0);
        expect($resultData[1][1])->toBe(2.5);
    });

    it('handles null values in condition', function () {
        $headers = ['value'];
        $data = [
            [null],
            [5],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::when(
            $headers,
            $data,
            'value',
            fn ($val) => $val !== null,
            'result',
            'has value',
            'no value'
        );

        expect($resultData[0][1])->toBe('no value');
        expect($resultData[1][1])->toBe('has value');
    });

    it('throws exception for invalid field', function () {
        $headers = ['value'];
        $data = [
            [1],
        ];

        expect(fn () => ConditionalTransformer::when(
            $headers,
            $data,
            'missing',
            fn ($val) => true,
            'result',
            'yes',
            'no'
        ))->toThrow(InvalidArgumentException::class);
    });

    it('can update existing field', function () {
        $headers = ['status'];
        $data = [
            ['active'],
            ['inactive'],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::when(
            $headers,
            $data,
            'status',
            fn ($val) => $val === 'active',
            'status',
            1,
            0
        );

        expect($resultData[0][0])->toBe(1);
        expect($resultData[1][0])->toBe(0);
    });
});

describe('coalesce()', function () {
    it('returns first non-null value', function () {
        $headers = ['a', 'b', 'c'];
        $data = [
            [null, null, 'value'],
            [null, 'first', 'second'],
            ['primary', 'backup', null],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'b', 'c']);

        expect($resultData[0][3])->toBe('value');
        expect($resultData[1][3])->toBe('first');
        expect($resultData[2][3])->toBe('primary');
    });

    it('returns null when all fields are null', function () {
        $headers = ['a', 'b', 'c'];
        $data = [
            [null, null, null],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'b', 'c']);

        expect($resultData[0][3])->toBeNull();
    });

    it('treats empty string as non-null', function () {
        $headers = ['a', 'b', 'c'];
        $data = [
            [null, '', 'value'],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'b', 'c']);

        expect($resultData[0][3])->toBe('');
    });

    it('treats zero as non-null', function () {
        $headers = ['a', 'b', 'c'];
        $data = [
            [null, 0, 5],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'b', 'c']);

        expect($resultData[0][3])->toBe(0);
    });

    it('treats false as non-null', function () {
        $headers = ['a', 'b', 'c'];
        $data = [
            [null, false, true],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'b', 'c']);

        expect($resultData[0][3])->toBe(false);
    });

    it('throws exception for invalid field', function () {
        $headers = ['a'];
        $data = [
            [null],
        ];

        expect(fn () => ConditionalTransformer::coalesce($headers, $data, 'result', ['a', 'missing']))
            ->toThrow(InvalidArgumentException::class);
    });

    it('requires at least one field', function () {
        $headers = ['a'];
        $data = [
            [1],
        ];

        expect(fn () => ConditionalTransformer::coalesce($headers, $data, 'result', []))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('nullIf()', function () {
    it('returns null when condition is true', function () {
        $headers = ['value'];
        $data = [
            [0],
            [5],
            [0],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::nullIf(
            $headers,
            $data,
            'value',
            'result',
            fn ($val) => $val === 0
        );

        expect($resultData[0][1])->toBeNull();
        expect($resultData[1][1])->toBe(5);
        expect($resultData[2][1])->toBeNull();
    });

    it('preserves original value when condition is false', function () {
        $headers = ['status'];
        $data = [
            ['active'],
            ['inactive'],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::nullIf(
            $headers,
            $data,
            'status',
            'result',
            fn ($val) => $val === 'deleted'
        );

        expect($resultData[0][1])->toBe('active');
        expect($resultData[1][1])->toBe('inactive');
    });

    it('can update existing field', function () {
        $headers = ['value'];
        $data = [
            [-1],
            [5],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::nullIf(
            $headers,
            $data,
            'value',
            'value',
            fn ($val) => $val < 0
        );

        expect($resultData[0][0])->toBeNull();
        expect($resultData[1][0])->toBe(5);
    });

    it('handles null input values', function () {
        $headers = ['value'];
        $data = [
            [null],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::nullIf(
            $headers,
            $data,
            'value',
            'result',
            fn ($val) => $val === null
        );

        expect($resultData[0][1])->toBeNull();
    });
});

describe('ifNull()', function () {
    it('replaces null with default value', function () {
        $headers = ['value'];
        $data = [
            [null],
            [5],
            [null],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::ifNull($headers, $data, 'value', 'result', 0);

        expect($resultData[0][1])->toBe(0);
        expect($resultData[1][1])->toBe(5);
        expect($resultData[2][1])->toBe(0);
    });

    it('preserves non-null values', function () {
        $headers = ['name'];
        $data = [
            ['John'],
            [''],
            [null],
            [0],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::ifNull($headers, $data, 'name', 'result', 'Unknown');

        expect($resultData[0][1])->toBe('John');
        expect($resultData[1][1])->toBe('');
        expect($resultData[2][1])->toBe('Unknown');
        expect($resultData[3][1])->toBe(0);
    });

    it('supports callback for default value', function () {
        $headers = ['value', 'backup'];
        $data = [
            [null, 10],
            [5, 20],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::ifNull(
            $headers,
            $data,
            'value',
            'result',
            fn ($row) => $row[1]
        );

        expect($resultData[0][2])->toBe(10);
        expect($resultData[1][2])->toBe(5);
    });
});

describe('case()', function () {
    it('evaluates multiple conditions in order', function () {
        $headers = ['score'];
        $data = [
            [95],
            [85],
            [75],
            [65],
            [55],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::case(
            $headers,
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
        );

        expect($resultData[0][1])->toBe('A');
        expect($resultData[1][1])->toBe('B');
        expect($resultData[2][1])->toBe('C');
        expect($resultData[3][1])->toBe('D');
        expect($resultData[4][1])->toBe('F');
    });

    it('supports callbacks for result values', function () {
        $headers = ['amount'];
        $data = [
            [1000],
            [500],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::case(
            $headers,
            $data,
            'amount',
            'fee',
            [
                [fn ($val) => $val >= 1000, fn ($row) => $row[0] * 0.01],
                [fn ($val) => $val >= 500, fn ($row) => $row[0] * 0.02],
            ],
            fn ($row) => $row[0] * 0.03
        );

        expect($resultData[0][1])->toBe(10.0);
        expect($resultData[1][1])->toBe(10.0);
    });

    it('returns default when no conditions match', function () {
        $headers = ['value'];
        $data = [
            [10],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::case(
            $headers,
            $data,
            'value',
            'result',
            [
                [fn ($val) => $val > 100, 'high'],
                [fn ($val) => $val > 50, 'medium'],
            ],
            'low'
        );

        expect($resultData[0][1])->toBe('low');
    });

    it('stops at first matching condition', function () {
        $headers = ['value'];
        $data = [
            [100],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::case(
            $headers,
            $data,
            'value',
            'result',
            [
                [fn ($val) => $val >= 50, 'first'],
                [fn ($val) => $val >= 100, 'second'],
            ],
            'default'
        );

        expect($resultData[0][1])->toBe('first');
    });

    it('handles empty conditions with default', function () {
        $headers = ['value'];
        $data = [
            [5],
        ];

        [$resultHeaders, $resultData] = ConditionalTransformer::case(
            $headers,
            $data,
            'value',
            'result',
            [],
            'default'
        );

        expect($resultData[0][1])->toBe('default');
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
