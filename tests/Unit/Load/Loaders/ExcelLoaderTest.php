<?php

declare(strict_types=1);

use Phetl\Contracts\LoaderInterface;
use Phetl\Load\Loaders\ExcelLoader;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/phetl_excel_test_' . uniqid();
    mkdir($this->tempDir);

    $this->tempFile = $this->tempDir . '/output.xlsx';
});

afterEach(function () {
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

test('ExcelLoader implements LoaderInterface', function () {
    $loader = new ExcelLoader($this->tempFile);
    expect($loader)->toBeInstanceOf(LoaderInterface::class);
});

test('it creates excel file with data', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        ['Name', 'Age', 'City'],
        ['Alice', 30, 'NYC'],
        ['Bob', 25, 'LA'],
    ];

    $rowCount = $loader->load($data)->rowCount();

    expect($rowCount)->toBe(2);
    expect($this->tempFile)->toBeFile();

    // Verify content
    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('B1')->getValue())->toBe('Age');
    expect($worksheet->getCell('C1')->getValue())->toBe('City');
    expect($worksheet->getCell('A2')->getValue())->toBe('Alice');
    expect($worksheet->getCell('B2')->getValue())->toBe(30);
    expect($worksheet->getCell('C2')->getValue())->toBe('NYC');
    expect($worksheet->getCell('A3')->getValue())->toBe('Bob');
    expect($worksheet->getCell('B3')->getValue())->toBe(25);
    expect($worksheet->getCell('C3')->getValue())->toBe('LA');
});

test('it handles empty rows', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        ['Name', 'Age'],
        [],
        ['Alice', 30],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('A2')->getValue())->toBeNull();
    expect($worksheet->getCell('A3')->getValue())->toBe('Alice');
});

test('it preserves null values', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        ['Name', 'Age', 'City'],
        ['Alice', null, 'NYC'],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('B2')->getValue())->toBeNull();
});

test('it preserves boolean values', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        ['Active', 'Verified'],
        [true, false],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A2')->getValue())->toBe(true);
    expect($worksheet->getCell('B2')->getValue())->toBe(false);
});

test('it preserves numeric values', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        ['Integer', 'Float'],
        [42, 3.14],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A2')->getValue())->toBe(42);
    expect($worksheet->getCell('B2')->getValue())->toBe(3.14);
});

test('it can write to specific sheet by name', function () {
    $loader = new ExcelLoader($this->tempFile, 'CustomSheet');

    $data = [
        ['Name', 'Age'],
        ['Alice', 30],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);

    expect($spreadsheet->getSheetByName('CustomSheet'))->not->toBeNull();

    $worksheet = $spreadsheet->getSheetByName('CustomSheet');
    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('A2')->getValue())->toBe('Alice');
});

test('it can write to specific sheet by index', function () {
    $loader = new ExcelLoader($this->tempFile, 0);

    $data = [
        ['Name', 'Age'],
        ['Alice', 30],
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getSheet(0);

    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('A2')->getValue())->toBe('Alice');
});

test('it validates file path is provided', function () {
    new ExcelLoader('');
})->throws(InvalidArgumentException::class);

test('it creates parent directory if needed', function () {
    $nestedFile = $this->tempDir . '/nested/dir/output.xlsx';
    $loader = new ExcelLoader($nestedFile);

    $data = [['Name'], ['Alice']];

    $loader->load($data);

    expect($nestedFile)->toBeFile();

    // Cleanup
    unlink($nestedFile);
    rmdir(dirname($nestedFile));
    rmdir(dirname(dirname($nestedFile)));
});

test('it handles wide rows', function () {
    $loader = new ExcelLoader($this->tempFile);

    $data = [
        range('A', 'Z'), // 26 columns
        range(1, 26),
    ];

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A1')->getValue())->toBe('A');
    expect($worksheet->getCell('Z1')->getValue())->toBe('Z');
    expect($worksheet->getCell('A2')->getValue())->toBe(1);
    expect($worksheet->getCell('Z2')->getValue())->toBe(26);
});

test('it handles many rows efficiently', function () {
    $loader = new ExcelLoader($this->tempFile);

    // Build data array
    $data = [['ID', 'Value']];

    for ($i = 1; $i <= 1000; $i++) {
        $data[] = [$i, "Value $i"];
    }

    $loader->load($data);

    $spreadsheet = IOFactory::load($this->tempFile);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A1')->getValue())->toBe('ID');
    expect($worksheet->getCell('A1001')->getValue())->toBe(1000);
    expect($worksheet->getCell('B1001')->getValue())->toBe('Value 1000');
});

test('it handles empty file gracefully', function () {
    $loader = new ExcelLoader($this->tempFile);

    // Load empty data
    $loader->load([]);

    expect($this->tempFile)->toBeFile();

    $spreadsheet = IOFactory::load($this->tempFile);

    // Should have at least the default empty sheet
    expect($spreadsheet->getSheetCount())->toBeGreaterThanOrEqual(1);
});
