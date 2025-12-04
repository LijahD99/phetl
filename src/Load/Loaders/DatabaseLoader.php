<?php

declare(strict_types=1);

namespace Phetl\Load\Loaders;

use InvalidArgumentException;
use PDO;
use Phetl\Contracts\LoaderInterface;
use Phetl\Support\LoadResult;

/**
 * Loads data into database tables using PDO.
 *
 * Handles batch inserts with transactions for performance and atomicity.
 * Automatically matches data columns to table columns.
 */
final class DatabaseLoader implements LoaderInterface
{
    /** @var array<string>|null */
    private ?array $tableColumns = null;

    /**
     * @param PDO $pdo Database connection
     * @param string $tableName Target table name
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $tableName
    ) {
        $this->validate();
    }

    /**
     * @param array<string> $headers Column names
     * @param iterable<int, array<int|string, mixed>> $data Data rows (without header)
     * @return LoadResult Result containing row count and operation details
     */
    public function load(array $headers, iterable $data): LoadResult
    {
        $rowCount = 0;

        // Start transaction for atomicity
        $this->pdo->beginTransaction();

        try {
            foreach ($data as $row) {
                $this->insertRow($headers, $row);
                $rowCount++;
            }

            $this->pdo->commit();
        }
        catch (\Exception $e) {
            $this->pdo->rollBack();

            throw $e;
        }

        return new LoadResult($rowCount);
    }

    /**
     * Validate table name is not empty.
     */
    private function validate(): void
    {
        if (trim($this->tableName) === '') {
            throw new InvalidArgumentException('Table name cannot be empty');
        }
    }

    /**
     * Insert a single row into the table.
     *
     * @param array<int, string> $headers
     * @param array<int|string, mixed> $row
     */
    private function insertRow(array $headers, array $row): void
    {
        // Filter headers to only include columns that exist in the table
        $tableColumns = $this->getTableColumns();
        $validHeaders = [];
        $validValues = [];

        foreach ($headers as $index => $header) {
            if (in_array($header, $tableColumns, true)) {
                $validHeaders[] = $header;
                $validValues[] = $row[$index] ?? null;
            }
        }

        // Skip insert if no valid columns
        if ($validHeaders === []) {
            return;
        }

        $columns = implode(', ', $validHeaders);
        $placeholders = implode(', ', array_fill(0, count($validHeaders), '?'));

        $sql = "INSERT INTO {$this->tableName} ({$columns}) VALUES ({$placeholders})";
        $statement = $this->pdo->prepare($sql);

        $statement->execute($validValues);
    }

    /**
     * Get list of columns in the target table.
     *
     * @return array<string>
     */
    private function getTableColumns(): array
    {
        if ($this->tableColumns === null) {
            // Query table schema (SQLite specific, but works for testing)
            $stmt = $this->pdo->query("PRAGMA table_info({$this->tableName})");
            if ($stmt === false) {
                return [];
            }

            /** @var array<string> $columns */
            $columns = [];

            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                /** @var array<string, mixed> $row */
                if (isset($row['name']) && is_string($row['name'])) {
                    $columns[] = $row['name'];
                }
            }

            $this->tableColumns = $columns;
        }

        return $this->tableColumns;
    }
}
