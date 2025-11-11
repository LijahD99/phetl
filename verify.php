#!/usr/bin/env php
<?php

/**
 * PHETL Project Verification Script
 *
 * Verifies that the project scaffolding is complete and ready for development
 */

declare(strict_types=1);

echo "🔍 PHETL Project Verification\n";
echo str_repeat('=', 50) . "\n\n";

$checks = [
    'PHP Version' => function() {
        $version = PHP_VERSION;
        $required = '8.1.0';
        $ok = version_compare($version, $required, '>=');
        return [
            'status' => $ok,
            'message' => $ok
                ? "✓ PHP $version (>= $required required)"
                : "✗ PHP $version (>= $required required)"
        ];
    },

    'composer.json' => function() {
        $exists = file_exists(__DIR__ . '/composer.json');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'PSR-12 Config' => function() {
        $exists = file_exists(__DIR__ . '/.php-cs-fixer.php');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'PHPStan Config' => function() {
        $exists = file_exists(__DIR__ . '/phpstan.neon');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'PHPUnit Config' => function() {
        $exists = file_exists(__DIR__ . '/phpunit.xml');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Source Directory' => function() {
        $exists = is_dir(__DIR__ . '/src');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Tests Directory' => function() {
        $exists = is_dir(__DIR__ . '/tests');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Extract Directory' => function() {
        $exists = is_dir(__DIR__ . '/src/Extract/Extractors');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Load Directory' => function() {
        $exists = is_dir(__DIR__ . '/src/Load/Loaders');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Transform Directory' => function() {
        $exists = is_dir(__DIR__ . '/src/Transform');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Engine Directory' => function() {
        $exists = is_dir(__DIR__ . '/src/Engine');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },

    'Contracts Directory' => function() {
        $exists = is_dir(__DIR__ . '/src/Contracts');
        return [
            'status' => $exists,
            'message' => $exists ? '✓ Found' : '✗ Missing'
        ];
    },
];

$passed = 0;
$failed = 0;

foreach ($checks as $name => $check) {
    $result = $check();
    echo sprintf("%-25s %s\n", $name . ':', $result['message']);

    if ($result['status']) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Results: $passed passed, $failed failed\n\n";

if ($failed === 0) {
    echo "✅ All checks passed! Project is ready for development.\n\n";
    echo "Next steps:\n";
    echo "1. composer install\n";
    echo "2. git init && git add . && git commit -m 'Initial scaffolding'\n";
    echo "3. See GETTING_STARTED_DEV.md for TDD workflow\n";
    exit(0);
} else {
    echo "⚠️  Some checks failed. Please review the output above.\n";
    exit(1);
}
