<?php

declare(strict_types=1);

use Phetl\Table;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir() . '/phetl_table_excel_test_' . uniqid();
    mkdir($this->tempDir);
});

afterEach(function () {
    // Clean up all files
    $files = glob($this->tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    if (is_dir($this->tempDir)) {
        rmdir($this->tempDir);
    }
});

test('Table::fromExcel() extracts data from Excel file', function () {
    $filePath = $this->tempDir . '/test.xlsx';

    // Create test file
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();

    $worksheet->setCellValue('A1', 'Name');
    $worksheet->setCellValue('B1', 'Age');
    $worksheet->setCellValue('C1', 'City');
    $worksheet->setCellValue('A2', 'Alice');
    $worksheet->setCellValue('B2', 30);
    $worksheet->setCellValue('C2', 'NYC');
    $worksheet->setCellValue('A3', 'Bob');
    $worksheet->setCellValue('B3', 25);
    $worksheet->setCellValue('C3', 'LA');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // Test extraction
    $table = Table::fromExcel($filePath);
    $data = $table->toArray();

    expect($data)->toHaveCount(3);
    expect($data[0])->toBe(['Name', 'Age', 'City']);
    expect($data[1])->toBe(['Alice', 30, 'NYC']);
    expect($data[2])->toBe(['Bob', 25, 'LA']);
});

test('Table::fromExcel() can extract from specific sheet by name', function () {
    $filePath = $this->tempDir . '/test.xlsx';

    // Create test file with multiple sheets
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->createSheet();

    // First sheet
    $sheet1 = $spreadsheet->getSheet(0);
    $sheet1->setTitle('Sheet1');
    $sheet1->setCellValue('A1', 'Wrong');

    // Second sheet
    $sheet2 = $spreadsheet->getSheet(1);
    $sheet2->setTitle('CustomSheet');
    $sheet2->setCellValue('A1', 'Name');
    $sheet2->setCellValue('A2', 'Alice');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // Test extraction from specific sheet
    $table = Table::fromExcel($filePath, 'CustomSheet');
    $data = $table->toArray();

    expect($data[0])->toBe(['Name']);
    expect($data[1])->toBe(['Alice']);
});

test('Table::fromExcel() can extract from specific sheet by index', function () {
    $filePath = $this->tempDir . '/test.xlsx';

    // Create test file with multiple sheets
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->createSheet();

    // First sheet
    $sheet1 = $spreadsheet->getSheet(0);
    $sheet1->setCellValue('A1', 'Wrong');

    // Second sheet
    $sheet2 = $spreadsheet->getSheet(1);
    $sheet2->setCellValue('A1', 'Name');
    $sheet2->setCellValue('A2', 'Bob');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // Test extraction from specific sheet by index
    $table = Table::fromExcel($filePath, 1);
    $data = $table->toArray();

    expect($data[0])->toBe(['Name']);
    expect($data[1])->toBe(['Bob']);
});

test('Table::toExcel() writes data to Excel file', function () {
    $filePath = $this->tempDir . '/output.xlsx';

    $table = Table::fromArray([
        ['Name', 'Age', 'City'],
        ['Alice', 30, 'NYC'],
        ['Bob', 25, 'LA'],
    ]);

    $rowCount = $table->toExcel($filePath)->rowCount();

    expect($rowCount)->toBe(2);
    expect($filePath)->toBeFile();

    // Verify content
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();

    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('B1')->getValue())->toBe('Age');
    expect($worksheet->getCell('C1')->getValue())->toBe('City');
    expect($worksheet->getCell('A2')->getValue())->toBe('Alice');
    expect($worksheet->getCell('B2')->getValue())->toBe(30);
    expect($worksheet->getCell('C2')->getValue())->toBe('NYC');
});

test('Table::toExcel() can write to specific sheet by name', function () {
    $filePath = $this->tempDir . '/output.xlsx';

    $table = Table::fromArray([
        ['Name'],
        ['Alice'],
    ]);

    $table->toExcel($filePath, 'CustomSheet');

    $spreadsheet = IOFactory::load($filePath);

    expect($spreadsheet->getSheetByName('CustomSheet'))->not->toBeNull();

    $worksheet = $spreadsheet->getSheetByName('CustomSheet');
    expect($worksheet->getCell('A1')->getValue())->toBe('Name');
    expect($worksheet->getCell('A2')->getValue())->toBe('Alice');
});

test('Excel round-trip preserves data', function () {
    $filePath = $this->tempDir . '/roundtrip.xlsx';

    $originalData = [
        ['Name', 'Age', 'Score', 'Active'],
        ['Alice', 30, 95.5, true],
        ['Bob', 25, 87.3, false],
        ['Charlie', 35, null, true],
    ];

    // Write
    $table = Table::fromArray($originalData);
    $table->toExcel($filePath);

    // Read back
    $result = Table::fromExcel($filePath);
    $roundtripData = $result->toArray();

    expect($roundtripData)->toBe($originalData);
});

test('Table transformations work with Excel', function () {
    $filePath = $this->tempDir . '/test.xlsx';
    $outputPath = $this->tempDir . '/output.xlsx';

    // Create test file
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $worksheet = $spreadsheet->getActiveSheet();

    $worksheet->setCellValue('A1', 'Name');
    $worksheet->setCellValue('B1', 'Age');
    $worksheet->setCellValue('A2', 'Alice');
    $worksheet->setCellValue('B2', 30);
    $worksheet->setCellValue('A3', 'Bob');
    $worksheet->setCellValue('B3', 25);
    $worksheet->setCellValue('A4', 'Charlie');
    $worksheet->setCellValue('B4', 35);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // Extract, transform, and load
    $table = Table::fromExcel($filePath)
        ->whereGreaterThan('Age', 25)
        ->selectColumns('Name')
        ->sortBy('Name');

    $table->toExcel($outputPath);

    // Verify
    $result = Table::fromExcel($outputPath);
    $data = $result->toArray();

    expect($data)->toBe([
        ['Name'],
        ['Alice'],
        ['Charlie'],
    ]);
});
