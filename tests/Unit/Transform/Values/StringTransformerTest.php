<?php

declare(strict_types=1);

use Phetl\Table;
use Phetl\Transform\Values\StringTransformer;

describe('StringTransformer', function () {
    describe('upper()', function () {
        it('converts field to uppercase', function () {
            $data = [
                ['name', 'city'],
                ['alice', 'new york'],
                ['bob', 'los angeles'],
            ];

            $result = iterator_to_array(StringTransformer::upper($data, 'name'));

            expect($result)->toBe([
                ['name', 'city'],
                ['ALICE', 'new york'],
                ['BOB', 'los angeles'],
            ]);
        });

        it('handles null values', function () {
            $data = [
                ['name'],
                ['alice'],
                [null],
                ['bob'],
            ];

            $result = iterator_to_array(StringTransformer::upper($data, 'name'));

            expect($result)->toBe([
                ['name'],
                ['ALICE'],
                [null],
                ['BOB'],
            ]);
        });
    });

    describe('lower()', function () {
        it('converts field to lowercase', function () {
            $data = [
                ['name', 'city'],
                ['ALICE', 'NEW YORK'],
                ['BOB', 'LOS ANGELES'],
            ];

            $result = iterator_to_array(StringTransformer::lower($data, 'name'));

            expect($result)->toBe([
                ['name', 'city'],
                ['alice', 'NEW YORK'],
                ['bob', 'LOS ANGELES'],
            ]);
        });
    });

    describe('trim()', function () {
        it('removes whitespace from field', function () {
            $data = [
                ['name', 'city'],
                ['  alice  ', ' new york'],
                ['bob  ', '  los angeles  '],
            ];

            $result = iterator_to_array(StringTransformer::trim($data, 'name'));

            expect($result)->toBe([
                ['name', 'city'],
                ['alice', ' new york'],
                ['bob', '  los angeles  '],
            ]);
        });

        it('trims custom characters', function () {
            $data = [
                ['name'],
                ['__alice__'],
                ['__bob__'],
            ];

            $result = iterator_to_array(StringTransformer::trim($data, 'name', '_'));

            expect($result)->toBe([
                ['name'],
                ['alice'],
                ['bob'],
            ]);
        });
    });

    describe('ltrim()', function () {
        it('removes left whitespace', function () {
            $data = [
                ['name'],
                ['  alice'],
                ['  bob  '],
            ];

            $result = iterator_to_array(StringTransformer::ltrim($data, 'name'));

            expect($result)->toBe([
                ['name'],
                ['alice'],
                ['bob  '],
            ]);
        });
    });

    describe('rtrim()', function () {
        it('removes right whitespace', function () {
            $data = [
                ['name'],
                ['alice  '],
                ['  bob  '],
            ];

            $result = iterator_to_array(StringTransformer::rtrim($data, 'name'));

            expect($result)->toBe([
                ['name'],
                ['alice'],
                ['  bob'],
            ]);
        });
    });

    describe('substring()', function () {
        it('extracts substring from field', function () {
            $data = [
                ['name'],
                ['alice'],
                ['bob'],
                ['charlie'],
            ];

            $result = iterator_to_array(StringTransformer::substring($data, 'name', 0, 3));

            expect($result)->toBe([
                ['name'],
                ['ali'],
                ['bob'],
                ['cha'],
            ]);
        });

        it('extracts substring without length', function () {
            $data = [
                ['name'],
                ['alice'],
                ['bob'],
            ];

            $result = iterator_to_array(StringTransformer::substring($data, 'name', 2));

            expect($result)->toBe([
                ['name'],
                ['ice'],
                ['b'],
            ]);
        });
    });

    describe('left()', function () {
        it('extracts leftmost characters', function () {
            $data = [
                ['name'],
                ['alice'],
                ['bob'],
            ];

            $result = iterator_to_array(StringTransformer::left($data, 'name', 3));

            expect($result)->toBe([
                ['name'],
                ['ali'],
                ['bob'],
            ]);
        });
    });

    describe('right()', function () {
        it('extracts rightmost characters', function () {
            $data = [
                ['name'],
                ['alice'],
                ['bob'],
            ];

            $result = iterator_to_array(StringTransformer::right($data, 'name', 3));

            expect($result)->toBe([
                ['name'],
                ['ice'],
                ['bob'],
            ]);
        });
    });

    describe('pad()', function () {
        it('pads string to specified length', function () {
            $data = [
                ['code'],
                ['1'],
                ['42'],
                ['123'],
            ];

            $result = iterator_to_array(StringTransformer::pad($data, 'code', 5, '0', STR_PAD_LEFT));

            expect($result)->toBe([
                ['code'],
                ['00001'],
                ['00042'],
                ['00123'],
            ]);
        });

        it('pads string on right by default', function () {
            $data = [
                ['name'],
                ['alice'],
            ];

            $result = iterator_to_array(StringTransformer::pad($data, 'name', 10, '_'));

            expect($result)->toBe([
                ['name'],
                ['alice_____'],
            ]);
        });
    });

    describe('concat()', function () {
        it('concatenates multiple fields', function () {
            $data = [
                ['first', 'last', 'full'],
                ['Alice', 'Smith', null],
                ['Bob', 'Jones', null],
            ];

            $result = iterator_to_array(StringTransformer::concat($data, 'full', ['first', 'last'], ' '));

            expect($result)->toBe([
                ['first', 'last', 'full'],
                ['Alice', 'Smith', 'Alice Smith'],
                ['Bob', 'Jones', 'Bob Jones'],
            ]);
        });

        it('concatenates without separator', function () {
            $data = [
                ['a', 'b', 'c', 'result'],
                ['X', 'Y', 'Z', null],
            ];

            $result = iterator_to_array(StringTransformer::concat($data, 'result', ['a', 'b', 'c']));

            expect($result)->toBe([
                ['a', 'b', 'c', 'result'],
                ['X', 'Y', 'Z', 'XYZ'],
            ]);
        });
    });

    describe('split()', function () {
        it('splits field into array', function () {
            $data = [
                ['tags'],
                ['php,python,javascript'],
                ['ruby,go'],
            ];

            $result = iterator_to_array(StringTransformer::split($data, 'tags', ','));

            expect($result)->toBe([
                ['tags'],
                [['php', 'python', 'javascript']],
                [['ruby', 'go']],
            ]);
        });

        it('limits split results', function () {
            $data = [
                ['path'],
                ['a/b/c/d'],
            ];

            $result = iterator_to_array(StringTransformer::split($data, 'path', '/', 2));

            expect($result)->toBe([
                ['path'],
                [['a', 'b/c/d']],
            ]);
        });
    });

    describe('replace()', function () {
        it('replaces substring with regex', function () {
            $data = [
                ['text'],
                ['hello world'],
                ['goodbye world'],
            ];

            $result = iterator_to_array(StringTransformer::replace($data, 'text', '/world/', 'universe'));

            expect($result)->toBe([
                ['text'],
                ['hello universe'],
                ['goodbye universe'],
            ]);
        });

        it('replaces multiple occurrences', function () {
            $data = [
                ['text'],
                ['the cat and the dog'],
            ];

            $result = iterator_to_array(StringTransformer::replace($data, 'text', '/the/', 'a'));

            expect($result)->toBe([
                ['text'],
                ['a cat and a dog'],
            ]);
        });
    });

    describe('extract()', function () {
        it('extracts pattern from field', function () {
            $data = [
                ['email', 'domain'],
                ['alice@example.com', null],
                ['bob@test.org', null],
            ];

            $result = iterator_to_array(StringTransformer::extract($data, 'email', 'domain', '/@(.+)$/'));

            expect($result)->toBe([
                ['email', 'domain'],
                ['alice@example.com', 'example.com'],
                ['bob@test.org', 'test.org'],
            ]);
        });

        it('returns null when no match', function () {
            $data = [
                ['text', 'number'],
                ['no numbers here', null],
                ['has 123 numbers', null],
            ];

            $result = iterator_to_array(StringTransformer::extract($data, 'text', 'number', '/(\d+)/'));

            expect($result)->toBe([
                ['text', 'number'],
                ['no numbers here', null],
                ['has 123 numbers', '123'],
            ]);
        });
    });

    describe('match()', function () {
        it('checks if field matches pattern', function () {
            $data = [
                ['email', 'valid'],
                ['alice@example.com', null],
                ['invalid-email', null],
                ['bob@test.org', null],
            ];

            $result = iterator_to_array(StringTransformer::match($data, 'email', 'valid', '/^[a-z]+@[a-z]+\.[a-z]+$/'));

            expect($result)->toBe([
                ['email', 'valid'],
                ['alice@example.com', true],
                ['invalid-email', false],
                ['bob@test.org', true],
            ]);
        });
    });

    describe('length()', function () {
        it('calculates field length', function () {
            $data = [
                ['name', 'length'],
                ['alice', null],
                ['bob', null],
                ['charlie', null],
            ];

            $result = iterator_to_array(StringTransformer::length($data, 'name', 'length'));

            expect($result)->toBe([
                ['name', 'length'],
                ['alice', 5],
                ['bob', 3],
                ['charlie', 7],
            ]);
        });

        it('handles null values', function () {
            $data = [
                ['name', 'length'],
                ['alice', null],
                [null, null],
            ];

            $result = iterator_to_array(StringTransformer::length($data, 'name', 'length'));

            expect($result)->toBe([
                ['name', 'length'],
                ['alice', 5],
                [null, 0],
            ]);
        });
    });
});

describe('Table string methods', function () {
    it('converts to uppercase', function () {
        $table = Table::fromArray([
            ['name', 'city'],
            ['alice', 'new york'],
            ['bob', 'los angeles'],
        ]);

        $result = $table->upper('name')->toArray();

        expect($result)->toBe([
            ['name', 'city'],
            ['ALICE', 'new york'],
            ['BOB', 'los angeles'],
        ]);
    });

    it('converts to lowercase', function () {
        $table = Table::fromArray([
            ['name'],
            ['ALICE'],
            ['BOB'],
        ]);

        $result = $table->lower('name')->toArray();

        expect($result)->toBe([
            ['name'],
            ['alice'],
            ['bob'],
        ]);
    });

    it('trims whitespace', function () {
        $table = Table::fromArray([
            ['name'],
            ['  alice  '],
            ['bob  '],
        ]);

        $result = $table->trim('name')->toArray();

        expect($result)->toBe([
            ['name'],
            ['alice'],
            ['bob'],
        ]);
    });

    it('concatenates fields', function () {
        $table = Table::fromArray([
            ['first', 'last', 'full'],
            ['Alice', 'Smith', null],
        ]);

        $result = $table->concatFields('full', ['first', 'last'], ' ')->toArray();

        expect($result)->toBe([
            ['first', 'last', 'full'],
            ['Alice', 'Smith', 'Alice Smith'],
        ]);
    });

    it('extracts pattern', function () {
        $table = Table::fromArray([
            ['email', 'domain'],
            ['alice@example.com', null],
        ]);

        $result = $table->extractPattern('email', 'domain', '/@(.+)$/')->toArray();

        expect($result)->toBe([
            ['email', 'domain'],
            ['alice@example.com', 'example.com'],
        ]);
    });

    it('chains string operations', function () {
        $table = Table::fromArray([
            ['name', 'email'],
            ['  ALICE  ', 'ALICE@EXAMPLE.COM'],
            ['  bob  ', 'bob@test.org'],
        ]);

        $result = $table
            ->trim('name')
            ->lower('name')
            ->lower('email')
            ->toArray();

        expect($result)->toBe([
            ['name', 'email'],
            ['alice', 'alice@example.com'],
            ['bob', 'bob@test.org'],
        ]);
    });
});
