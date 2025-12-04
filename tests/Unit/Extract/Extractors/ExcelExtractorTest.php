<?php

declare(strict_types=1);

use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\ExcelExtractor;

describe('ExcelExtractor', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/phetl_test_' . uniqid();
        mkdir($this->tempDir);

        // Helper to create a simple Excel file
        $this->createSimpleExcel = function (string $filePath, array $data): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            foreach ($data as $rowIndex => $row) {
                foreach ($row as $colIndex => $value) {
                    $sheet->setCellValue(
                        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1),
                        $value
                    );
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
        };

        // Helper to create multi-sheet Excel
        $this->createMultiSheetExcel = function (string $filePath, array $sheets): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $firstSheet = true;
            foreach ($sheets as $sheetName => $data) {
                if ($firstSheet) {
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle($sheetName);
                    $firstSheet = false;
                }
                else {
                    $sheet = $spreadsheet->createSheet();
                    $sheet->setTitle($sheetName);
                }

                foreach ($data as $rowIndex => $row) {
                    foreach ($row as $colIndex => $value) {
                        $sheet->setCellValue(
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1),
                            $value
                        );
                    }
                }
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
        };

        // Helper to create Excel with formulas
        $this->createExcelWithFormulas = function (string $filePath): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'A');
            $sheet->setCellValue('B1', 'B');
            $sheet->setCellValue('C1', 'Sum');

            $sheet->setCellValue('A2', 10);
            $sheet->setCellValue('B2', 5);
            $sheet->setCellValue('C2', '=A2+B2'); // Formula

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filePath);
        };
    });

    afterEach(function () {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    });

    it('implements extractor interface', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Age'],
            ['Alice', 25],
        ]);

        $extractor = new ExcelExtractor($filePath);
        expect($extractor)->toBeInstanceOf(ExtractorInterface::class);
    });

    it('extracts excel data', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Age', 'City'],
            ['Alice', 25, 'New York'],
            ['Bob', 30, 'London'],
        ]);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($headers)->toBe(['Name', 'Age', 'City']);
        expect($data)->toBe([
            ['Alice', 25, 'New York'],
            ['Bob', 30, 'London'],
        ]);
    });

    it('validates file exists', function () {
        expect(fn () => new ExcelExtractor('/nonexistent/file.xlsx'))
            ->toThrow(InvalidArgumentException::class, 'Excel file does not exist');
    });

    it('yields rows lazily', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name'],
            ['Alice'],
            ['Bob'],
        ]);

        $extractor = new ExcelExtractor($filePath);
        $result = $extractor->extract();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    it('can be iterated multiple times', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name'],
            ['Alice'],
        ]);

        $extractor = new ExcelExtractor($filePath);

        $first = $extractor->extract();
        $second = $extractor->extract();

        expect($first)->toBe($second);
    });

    it('handles empty sheet', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, []);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($headers)->toBe([]);
        expect($data)->toBe([]);
    });

    it('handles header only sheet', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Age'],
        ]);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($headers)->toBe(['Name', 'Age']);
        expect($data)->toBe([]);
    });

    it('preserves numeric values', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Age', 'Salary'],
            ['Alice', 25, 50000.50],
            ['Bob', 30, 75000],
        ]);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($data[0][1])->toBe(25);
        expect($data[0][2])->toBe(50000.50);
        expect($data[1][2])->toBe(75000);
    });

    it('handles null values', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Age'],
            ['Alice', null],
            [null, 30],
        ]);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($data[0][1])->toBeNull();
        expect($data[1][0])->toBeNull();
    });

    it('handles boolean values', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [
            ['Name', 'Active'],
            ['Alice', true],
            ['Bob', false],
        ]);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        expect($data[0][1])->toBe(true);
        expect($data[1][1])->toBe(false);
    });

    it('can extract specific sheet by name', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createMultiSheetExcel)($filePath, [
            'Sheet1' => [
                ['Name'],
                ['Alice'],
            ],
            'Sheet2' => [
                ['Name'],
                ['Bob'],
            ],
        ]);

        $extractor = new ExcelExtractor($filePath, 'Sheet2');
        [$headers, $data] = $extractor->extract();

        expect($headers)->toBe(['Name']);
        expect($data)->toBe([
            ['Bob'],
        ]);
    });

    it('can extract specific sheet by index', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createMultiSheetExcel)($filePath, [
            'Sheet1' => [
                ['Name'],
                ['Alice'],
            ],
            'Sheet2' => [
                ['Name'],
                ['Bob'],
            ],
        ]);

        $extractor = new ExcelExtractor($filePath, 1); // Second sheet (0-indexed)
        [$headers, $data] = $extractor->extract();

        expect($headers)->toBe(['Name']);
        expect($data)->toBe([
            ['Bob'],
        ]);
    });

    it('throws exception for invalid sheet name', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [['Name']]);

        expect(fn () => new ExcelExtractor($filePath, 'NonExistentSheet'))
            ->toThrow(InvalidArgumentException::class, 'Sheet "NonExistentSheet" not found');
    });

    it('throws exception for invalid sheet index', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createSimpleExcel)($filePath, [['Name']]);

        expect(fn () => new ExcelExtractor($filePath, 99))
            ->toThrow(InvalidArgumentException::class, 'Sheet index 99 not found');
    });

    it('handles formulas by evaluating them', function () {
        $filePath = $this->tempDir . '/test.xlsx';
        ($this->createExcelWithFormulas)($filePath);

        $extractor = new ExcelExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        // Formula in cell should be evaluated to its result
        expect($data[0][2])->toBe(15); // A1 + B1 = 10 + 5 = 15
    });
});
