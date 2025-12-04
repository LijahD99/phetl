<?php

declare(strict_types=1);

namespace Phetl\Load\Loaders;

use InvalidArgumentException;
use Phetl\Contracts\LoaderInterface;
use Phetl\Support\LoadResult;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Loads data into Excel files (.xlsx format)
 *
 * Supports writing to specific sheets by name or index, preserves data types,
 * and automatically saves when destroyed.
 */
class ExcelLoader implements LoaderInterface
{
    private Spreadsheet $spreadsheet;
    private Worksheet $worksheet;
    private int $currentRow = 1;

    /**
     * Create a new Excel loader
     *
     * @param string $filePath Path to the Excel file to create
     * @param string|int|null $sheet Sheet name (string) or index (int), null for active sheet
     * @throws InvalidArgumentException If file path is empty
     */
    public function __construct(
        private string $filePath,
        private string|int|null $sheet = null
    ) {
        $this->validate();
        $this->initialize();
    }

    /**
     * Load data to the Excel file
     *
     * @param array<string> $headers Column names
     * @param iterable<int, array<int|string, mixed>> $data Data rows (without header)
     * @return LoadResult Result containing row count and operation details
     */
    public function load(array $headers, iterable $data): LoadResult
    {
        $rowCount = 0;

        // Write headers
        if (! empty($headers)) {
            $col = 1;
            foreach ($headers as $value) {
                $coordinate = Coordinate::stringFromColumnIndex($col) . $this->currentRow;
                $this->worksheet->setCellValue($coordinate, $value);
                $col++;
            }
            $this->currentRow++;
        }

        // Write data rows
        foreach ($data as $row) {
            if (empty($row)) {
                $this->currentRow++;

                continue;
            }

            $col = 1;
            foreach ($row as $value) {
                $coordinate = Coordinate::stringFromColumnIndex($col) . $this->currentRow;
                $this->worksheet->setCellValue($coordinate, $value);
                $col++;
            }

            $this->currentRow++;
            $rowCount++;
        }

        $this->save();

        return new LoadResult($rowCount);
    }

    /**
     * Validate the file path
     *
     * @throws InvalidArgumentException If file path is empty
     */
    private function validate(): void
    {
        if (empty($this->filePath)) {
            throw new InvalidArgumentException('File path cannot be empty');
        }
    }

    /**
     * Initialize the spreadsheet and worksheet
     */
    private function initialize(): void
    {
        $this->spreadsheet = new Spreadsheet();

        if ($this->sheet === null) {
            $this->worksheet = $this->spreadsheet->getActiveSheet();
        }
        elseif (is_string($this->sheet)) {
            $this->worksheet = $this->spreadsheet->getActiveSheet();
            $this->worksheet->setTitle($this->sheet);
        }
        else {
            // Sheet index
            if ($this->sheet === 0) {
                $this->worksheet = $this->spreadsheet->getActiveSheet();
            }
            else {
                // Create additional sheets if needed
                while ($this->spreadsheet->getSheetCount() <= $this->sheet) {
                    $this->spreadsheet->createSheet();
                }
                $this->worksheet = $this->spreadsheet->getSheet($this->sheet);
            }
        }
    }

    /**
     * Save the spreadsheet to the file
     */
    private function save(): void
    {
        // Create parent directory if it doesn't exist
        $dir = dirname($this->filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->filePath);
    }
}
