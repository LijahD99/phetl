<?php

declare(strict_types=1);

namespace Phetl;

use IteratorAggregate;
use PDO;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Contracts\LoaderInterface;
use Phetl\Extract\Extractors\ArrayExtractor;
use Phetl\Extract\Extractors\CsvExtractor;
use Phetl\Extract\Extractors\DatabaseExtractor;
use Phetl\Extract\Extractors\ExcelExtractor;
use Phetl\Extract\Extractors\JsonExtractor;
use Phetl\Extract\Extractors\RestApiExtractor;
use Phetl\Load\Loaders\CsvLoader;
use Phetl\Load\Loaders\DatabaseLoader;
use Phetl\Load\Loaders\ExcelLoader;
use Phetl\Load\Loaders\JsonLoader;
use Phetl\Support\LoadResult;
use Phetl\Transform\Aggregation\Aggregator;
use Phetl\Transform\Columns\ColumnAdder;
use Phetl\Transform\Columns\ColumnRenamer;
use Phetl\Transform\Columns\ColumnSelector;
use Phetl\Transform\Joins\Join;
use Phetl\Transform\Reshaping\Reshaper;
use Phetl\Transform\Rows\Deduplicator;
use Phetl\Transform\Rows\RowFilter;
use Phetl\Transform\Rows\RowSelector;
use Phetl\Transform\Rows\RowSorter;
use Phetl\Transform\Set\SetOperation;
use Phetl\Transform\Validation\Validator;
use Phetl\Transform\Values\ConditionalTransformer;
use Phetl\Transform\Values\StringTransformer;
use Phetl\Transform\Values\ValueConverter;
use Phetl\Transform\Values\ValueReplacer;
use RuntimeException;
use Traversable;

/**
 * Main Table class for PHETL ETL operations.
 *
 * Wraps an iterable data source and provides fluent API for transformations.
 * Headers are stored separately from data rows for clarity and flexibility.
 *
 * @implements IteratorAggregate<int, array<int|string, mixed>>
 */
class Table implements IteratorAggregate
{
    /**
     * @var array<string>
     */
    private readonly array $headers;

    /**
     * @var array<int, array<int|string, mixed>>
     */
    private readonly array $materializedData;

    /**
     * @param array<string> $headers Column names
     * @param iterable<int, array<int|string, mixed>> $data Data rows (without header)
     */
    public function __construct(
        array $headers,
        iterable $data
    ) {
        $this->headers = $headers;
        // Materialize the data to allow multiple iterations
        $this->materializedData = is_array($data) ? $data : iterator_to_array($data, false);
    }

    /**
     * Create a Table from an array.
     *
     * Supports two formats:
     * 1. Backward compatible: First row is headers
     *    fromArray([['name', 'age'], ['Alice', 30]])
     *
     * 2. Explicit headers (recommended):
     *    fromArray([['Alice', 30]], ['name', 'age'])
     *
     * @param array<int, array<int|string, mixed>> $data
     * @param array<string>|null $headers Explicit headers (null = first row is header)
     */
    public static function fromArray(array $data, ?array $headers = null): self
    {
        $extractor = new ArrayExtractor($data, $headers);
        [$extractedHeaders, $extractedData] = $extractor->extract();

        return new self($extractedHeaders, $extractedData);
    }

    /**
     * Create a Table from a CSV file.
     */
    public static function fromCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        bool $hasHeaders = true
    ): self {
        $extractor = new CsvExtractor($filePath, $delimiter, $enclosure, $escape, $hasHeaders);
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Create a Table from a JSON file.
     */
    public static function fromJson(string $filePath): self
    {
        $extractor = new JsonExtractor($filePath);
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Create a Table from a database query.
     *
     * @param array<string, mixed> $params
     */
    public static function fromDatabase(PDO $pdo, string $query, array $params = []): self
    {
        $extractor = new DatabaseExtractor($pdo, $query, $params);
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Create a Table from a RESTful API.
     *
     * @param array<string, mixed> $config
     */
    public static function fromRestApi(string $url, array $config = []): self
    {
        $extractor = new RestApiExtractor($url, $config);
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Create a Table from an Excel file.
     *
     * @param string|int|null $sheet Sheet name (string), index (int), or null for active sheet
     */
    public static function fromExcel(string $filePath, string|int|null $sheet = null, bool $hasHeaders = true): self
    {
        $extractor = new ExcelExtractor($filePath, $sheet, $hasHeaders);
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Create a Table from any extractor.
     */
    public static function fromExtractor(ExtractorInterface $extractor): self
    {
        [$headers, $data] = $extractor->extract();

        return new self($headers, $data);
    }

    /**
     * Load data to a CSV file.
     */
    public function toCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): LoadResult {
        $loader = new CsvLoader($filePath, $delimiter, $enclosure, $escape);

        return $loader->load($this->headers, $this->materializedData);
    }

    /**
     * Load data to a JSON file.
     */
    public function toJson(string $filePath, bool $prettyPrint = false): LoadResult
    {
        $loader = new JsonLoader($filePath, $prettyPrint);

        return $loader->load($this->headers, $this->materializedData);
    }

    /**
     * Load data to a database table.
     */
    public function toDatabase(PDO $pdo, string $tableName): LoadResult
    {
        $loader = new DatabaseLoader($pdo, $tableName);

        return $loader->load($this->headers, $this->materializedData);
    }

    /**
     * Load data to an Excel file.
     *
     * @param string|int|null $sheet Sheet name (string), index (int), or null for active sheet
     */
    public function toExcel(string $filePath, string|int|null $sheet = null): LoadResult
    {
        $loader = new ExcelLoader($filePath, $sheet);

        return $loader->load($this->headers, $this->materializedData);
    }

    /**
     * Load data using any loader.
     */
    public function toLoader(LoaderInterface $loader): LoadResult
    {
        return $loader->load($this->headers, $this->materializedData);
    }

    /**
     * Get the underlying data as an array (materializes all rows).
     * Returns header row followed by data rows for backward compatibility.
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(): array
    {
        return array_merge([$this->headers], $this->materializedData);
    }

    /**
     * Get iterator for the table data (data rows only, no header).
     *
     * @return Traversable<int, array<int|string, mixed>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->materializedData;
    }

    /**
     * Display first N data rows with header (for debugging/inspection).
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function look(int $limit = 10): array
    {
        $rows = [$this->headers];
        $count = 0;

        foreach ($this->materializedData as $row) {
            $rows[] = $row;
            $count++;

            if ($count >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Count the number of data rows (excluding header).
     */
    public function count(): int
    {
        return count($this->materializedData);
    }

    /**
     * Get the header row (column names).
     *
     * @return array<string>
     */
    public function header(): array
    {
        return $this->headers;
    }

    // ==================== TRANSFORMATIONS ====================

    /**
     * Select the first N rows (data rows, header is preserved).
     */
    public function head(int $limit): self
    {
        [$headers, $data] = RowSelector::head($this->headers, $this->materializedData, $limit);

        return new self($headers, $data);
    }

    /**
     * Select the last N rows (data rows, header is preserved).
     */
    public function tail(int $limit): self
    {
        [$headers, $data] = RowSelector::tail($this->headers, $this->materializedData, $limit);

        return new self($headers, $data);
    }

    /**
     * Select a slice of rows by range.
     * Start and stop indices are for data rows (0-indexed, header is preserved).
     */
    public function slice(int $start, ?int $stop = null, int $step = 1): self
    {
        [$headers, $data] = RowSelector::slice($this->headers, $this->materializedData, $start, $stop, $step);

        return new self($headers, $data);
    }

    /**
     * Skip the first N data rows (header is preserved).
     */
    public function skip(int $count): self
    {
        [$headers, $data] = RowSelector::skip($this->headers, $this->materializedData, $count);

        return new self($headers, $data);
    }

    /**
     * Sort rows by one or more fields.
     *
     * @param string|array<string>|\Closure $key Field name, array of fields, or custom comparator
     * @param bool $reverse Sort in descending order
     */
    public function sort(string|array|\Closure $key, bool $reverse = false): self
    {
        [$headers, $data] = RowSorter::sort($this->headers, $this->materializedData, $key, $reverse);

        return new self($headers, $data);
    }

    /**
     * Sort rows by field(s) in ascending order.
     *
     * @param string ...$fields Field names to sort by
     */
    public function sortBy(string ...$fields): self
    {
        [$headers, $data] = RowSorter::sort($this->headers, $this->materializedData, $fields, false);

        return new self($headers, $data);
    }

    /**
     * Sort rows by field(s) in descending order.
     *
     * @param string ...$fields Field names to sort by
     */
    public function sortByDesc(string ...$fields): self
    {
        [$headers, $data] = RowSorter::sort($this->headers, $this->materializedData, $fields, true);

        return new self($headers, $data);
    }

    /**
     * Select specific columns by name (cut in petl).
     *
     * @param string ...$columns Column names to select
     */
    public function selectColumns(string ...$columns): self
    {
        return new self(...ColumnSelector::select($this->headers, $this->materializedData, $columns));
    }

    /**
     * Alias for selectColumns (petl compatibility).
     *
     * @param string ...$columns Column names to select
     */
    public function cut(string ...$columns): self
    {
        return $this->selectColumns(...$columns);
    }

    /**
     * Remove specific columns by name (cutout in petl).
     *
     * @param string ...$columns Column names to remove
     */
    public function removeColumns(string ...$columns): self
    {
        return new self(...ColumnSelector::remove($this->headers, $this->materializedData, $columns));
    }

    /**
     * Alias for removeColumns (petl compatibility).
     *
     * @param string ...$columns Column names to remove
     */
    public function cutout(string ...$columns): self
    {
        return $this->removeColumns(...$columns);
    }

    /**
     * Rename columns using a mapping array.
     *
     * @param array<string, string> $mapping Old name => New name
     */
    public function renameColumns(array $mapping): self
    {
        return new self(...ColumnRenamer::rename($this->headers, $this->materializedData, $mapping));
    }

    /**
     * Alias for renameColumns (petl compatibility).
     *
     * @param array<string, string> $mapping Old name => New name
     */
    public function rename(array $mapping): self
    {
        return $this->renameColumns($mapping);
    }

    /**
     * Add a new column with a computed value.
     *
     * @param string $name Column name
     * @param \Closure|mixed $value Value or function(array $row): mixed
     */
    public function addColumn(string $name, mixed $value): self
    {
        return new self(...ColumnAdder::add($this->headers, $this->materializedData, $name, $value));
    }

    /**
     * Alias for addColumn (petl compatibility).
     *
     * @param string $name Field name
     * @param \Closure|mixed $value Value or function(array $row): mixed
     */
    public function addField(string $name, mixed $value): self
    {
        return $this->addColumn($name, $value);
    }

    /**
     * Add a row number column (1-indexed, excluding header).
     */
    public function addRowNumbers(string $columnName = 'row_number'): self
    {
        return new self(...ColumnAdder::addRowNumbers($this->headers, $this->materializedData, $columnName));
    }

    /**
     * Apply a conversion function to values in a field.
     *
     * @param string $field Field name
     * @param callable|string $converter Conversion function
     */
    public function convert(string $field, callable|string $converter): self
    {
        return new self(...ValueConverter::convert($this->headers, $this->materializedData, $field, $converter));
    }

    /**
     * Apply conversion functions to multiple fields.
     *
     * @param array<string, callable|string> $conversions Field => converter mapping
     */
    public function convertMultiple(array $conversions): self
    {
        return new self(...ValueConverter::convertMultiple($this->headers, $this->materializedData, $conversions));
    }

    /**
     * Replace a specific value in a field.
     *
     * @param string $field Field name
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     */
    public function replace(
        string $field,
        mixed $oldValue,
        mixed $newValue
    ): self {
        return new self(...ValueReplacer::replace(
            $this->headers,
            $this->materializedData,
            $field,
            $oldValue,
            $newValue
        ));
    }

    /**
     * Replace multiple values in a field using a mapping.
     *
     * @param string $field Field name
     * @param array<mixed, mixed> $mapping Old value => New value
     */
    public function replaceMap(string $field, array $mapping): self
    {
        return new self(...ValueReplacer::replaceMap(
            $this->headers,
            $this->materializedData,
            $field,
            $mapping
        ));
    }

    /**
     * Replace all occurrences of a value across all fields.
     *
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     */
    public function replaceAll(mixed $oldValue, mixed $newValue): self
    {
        return new self(...ValueReplacer::replaceAll(
            $this->headers,
            $this->materializedData,
            $oldValue,
            $newValue
        ));
    }

    /**
     * Filter rows using a custom predicate function.
     *
     * @param \Closure $predicate Function(array $row): bool
     */
    public function filter(\Closure $predicate): self
    {
        return new self(...RowFilter::filter(
            $this->headers,
            $this->materializedData,
            $predicate
        ));
    }

    /**
     * Alias for filter (petl compatibility).
     *
     * @param \Closure $predicate Function(array $row): bool
     */
    public function select(\Closure $predicate): self
    {
        return $this->filter($predicate);
    }

    /**
     * Filter rows where a field equals a value.
     */
    public function whereEquals(string $field, mixed $value): self
    {
        return new self(...RowFilter::whereEquals(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field does not equal a value.
     */
    public function whereNotEquals(string $field, mixed $value): self
    {
        return new self(...RowFilter::whereNotEquals(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field is greater than a value.
     */
    public function whereGreaterThan(string $field, int|float $value): self
    {
        return new self(...RowFilter::whereGreaterThan(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field is less than a value.
     */
    public function whereLessThan(string $field, int|float $value): self
    {
        return new self(...RowFilter::whereLessThan(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field is greater than or equal to a value.
     */
    public function whereGreaterThanOrEqual(string $field, int|float $value): self
    {
        return new self(...RowFilter::whereGreaterThanOrEqual(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field is less than or equal to a value.
     */
    public function whereLessThanOrEqual(string $field, int|float $value): self
    {
        return new self(...RowFilter::whereLessThanOrEqual(
            $this->headers,
            $this->materializedData,
            $field,
            $value
        ));
    }

    /**
     * Filter rows where a field's value is in an array.
     *
     * @param array<mixed> $values
     */
    public function whereIn(string $field, array $values): self
    {
        return new self(...RowFilter::whereIn(
            $this->headers,
            $this->materializedData,
            $field,
            $values
        ));
    }

    /**
     * Filter rows where a field's value is not in an array.
     *
     * @param array<mixed> $values
     */
    public function whereNotIn(string $field, array $values): self
    {
        return new self(...RowFilter::whereNotIn(
            $this->headers,
            $this->materializedData,
            $field,
            $values
        ));
    }

    /**
     * Filter rows where a field is null.
     */
    public function whereNull(string $field): self
    {
        return new self(...RowFilter::whereNull(
            $this->headers,
            $this->materializedData,
            $field
        ));
    }

    /**
     * Filter rows where a field is not null.
     */
    public function whereNotNull(string $field): self
    {
        return new self(...RowFilter::whereNotNull(
            $this->headers,
            $this->materializedData,
            $field
        ));
    }

    /**
     * Filter rows where a field value is true.
     */
    public function whereTrue(string $field): self
    {
        return new self(...RowFilter::whereTrue(
            $this->headers,
            $this->materializedData,
            $field
        ));
    }

    /**
     * Filter rows where a field value is false.
     */
    public function whereFalse(string $field): self
    {
        return new self(...RowFilter::whereFalse(
            $this->headers,
            $this->materializedData,
            $field
        ));
    }

    /**
     * Filter rows where a string field contains a substring.
     */
    public function whereContains(string $field, string $substring): self
    {
        return new self(...RowFilter::whereContains(
            $this->headers,
            $this->materializedData,
            $field,
            $substring
        ));
    }

    // =================================================================
    // Set Operations
    // =================================================================

    /**
     * Concatenate this table with other tables vertically.
     * Headers must match exactly.
     *
     * @param Table ...$tables Tables to concatenate
     */
    public function concat(self ...$tables): self
    {
        $tableTuples = [[$this->headers, $this->materializedData]];

        foreach ($tables as $table) {
            $tableTuples[] = [$table->headers, $table->materializedData];
        }

        return new self(...SetOperation::concat(...$tableTuples));
    }

    /**
     * Union this table with other tables (concat + remove duplicates).
     * Headers must match exactly.
     *
     * @param Table ...$tables Tables to union
     */
    public function union(self ...$tables): self
    {
        $tableTuples = [[$this->headers, $this->materializedData]];

        foreach ($tables as $table) {
            $tableTuples[] = [$table->headers, $table->materializedData];
        }

        return new self(...SetOperation::union(...$tableTuples));
    }

    /**
     * Merge tables with different headers (combines all columns).
     * Missing values are filled with null.
     *
     * @param Table ...$tables Tables to merge
     */
    public function merge(self ...$tables): self
    {
        $tableTuples = [[$this->headers, $this->materializedData]];

        foreach ($tables as $table) {
            $tableTuples[] = [$table->headers, $table->materializedData];
        }

        return new self(...SetOperation::merge(...$tableTuples));
    }

    /**
     * Perform an inner join with another table.
     *
     * @param Table $right Right table to join
     * @param string|array<string> $leftKey Left table key(s)
     * @param string|array<string>|null $rightKey Right table key(s), defaults to $leftKey
     */
    public function innerJoin(self $right, string|array $leftKey, string|array|null $rightKey = null): self
    {
        return new self(...Join::inner(
            $this->headers,
            $this->materializedData,
            $right->headers,
            $right->materializedData,
            $leftKey,
            $rightKey
        ));
    }

    /**
     * Perform a left join with another table.
     *
     * @param Table $right Right table to join
     * @param string|array<string> $leftKey Left table key(s)
     * @param string|array<string>|null $rightKey Right table key(s), defaults to $leftKey
     */
    public function leftJoin(self $right, string|array $leftKey, string|array|null $rightKey = null): self
    {
        return new self(...Join::left(
            $this->headers,
            $this->materializedData,
            $right->headers,
            $right->materializedData,
            $leftKey,
            $rightKey
        ));
    }

    /**
     * Perform a right join with another table.
     *
     * @param Table $right Right table to join
     * @param string|array<string> $leftKey Left table key(s)
     * @param string|array<string>|null $rightKey Right table key(s), defaults to $leftKey
     */
    public function rightJoin(self $right, string|array $leftKey, string|array|null $rightKey = null): self
    {
        return new self(...Join::right(
            $this->headers,
            $this->materializedData,
            $right->headers,
            $right->materializedData,
            $leftKey,
            $rightKey
        ));
    }

    /**
     * Group rows by field(s) and apply aggregations.
     *
     * @param string|array<string> $groupBy Field(s) to group by
     * @param array<string, callable|string> $aggregations Map of output field => aggregation function
     */
    public function aggregate(string|array $groupBy, array $aggregations): self
    {
        return new self(...Aggregator::aggregate($this->headers, $this->materializedData, $groupBy, $aggregations));
    }

    /**
     * Alias for aggregate() - petl compatibility.
     *
     * @param string|array<string> $groupBy Field(s) to group by
     * @param array<string, callable|string> $aggregations Map of output field => aggregation function
     */
    public function groupBy(string|array $groupBy, array $aggregations): self
    {
        return $this->aggregate($groupBy, $aggregations);
    }

    /**
     * Count rows grouped by field(s).
     *
     * @param string|array<string> $groupBy Field(s) to group by
     */
    public function countBy(string|array $groupBy): self
    {
        return new self(...Aggregator::count($this->headers, $this->materializedData, $groupBy));
    }

    /**
     * Sum values of a field, optionally grouped.
     *
     * @param string $field Field to sum
     * @param string|array<string>|null $groupBy Field(s) to group by
     */
    public function sumField(string $field, string|array|null $groupBy = null): self
    {
        return new self(...Aggregator::sum($this->headers, $this->materializedData, $field, $groupBy));
    }

    /**
     * Unpivot table from wide to long format.
     *
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to unpivot (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     */
    public function unpivot(
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): self {
        return new self(...Reshaper::unpivot(
            $this->headers,
            $this->materializedData,
            $idFields,
            $valueFields,
            $variableName,
            $valueName
        ));
    }

    /**
     * Alias for unpivot - petl compatibility.
     *
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to melt (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     */
    public function melt(
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): self {
        return $this->unpivot($idFields, $valueFields, $variableName, $valueName);
    }

    /**
     * Pivot table from long to wide format.
     *
     * @param string|array<string> $indexFields Field(s) to use as row identifiers
     * @param string $columnField Field to pivot into columns
     * @param string $valueField Field to use for values
     * @param callable|string|null $aggregation Aggregation function for duplicates
     */
    public function pivot(
        string|array $indexFields,
        string $columnField,
        string $valueField,
        callable|string|null $aggregation = null
    ): self {
        return new self(...Reshaper::pivot(
            $this->headers,
            $this->materializedData,
            $indexFields,
            $columnField,
            $valueField,
            $aggregation
        ));
    }

    /**
     * Transpose table - swap rows and columns.
     */
    public function transpose(): self
    {
        return new self(...Reshaper::transpose($this->headers, $this->materializedData));
    }

    // =================================================================
    // Deduplication Operations
    // =================================================================

    /**
     * Remove duplicate rows, keeping only distinct rows.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return self
     */
    public function distinct(string|array|null $fields = null): self
    {
        return new self(...Deduplicator::distinct($this->headers, $this->materializedData, $fields));
    }

    /**
     * Alias for distinct - petl compatibility.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness
     * @return self
     */
    public function unique(string|array|null $fields = null): self
    {
        return new self(...Deduplicator::unique($this->headers, $this->materializedData, $fields));
    }

    /**
     * Return only duplicate rows (rows that appear more than once).
     *
     * @param string|array<string>|null $fields Field(s) to check for duplicates (null = all fields)
     * @return self
     */
    public function duplicates(string|array|null $fields = null): self
    {
        return new self(...Deduplicator::duplicates($this->headers, $this->materializedData, $fields));
    }

    /**
     * Count occurrences of each unique row.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @param string $countField Name for the count column (default: 'count')
     * @return self
     */
    public function countDistinct(string|array|null $fields = null, string $countField = 'count'): self
    {
        return new self(...Deduplicator::countDistinct($this->headers, $this->materializedData, $fields, $countField));
    }

    /**
     * Check if all rows are unique.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return bool
     */
    public function isUnique(string|array|null $fields = null): bool
    {
        return Deduplicator::isUnique($this->headers, $this->materializedData, $fields);
    }

    // =================================================================
    // Validation Operations
    // =================================================================

    /**
     * Validate required fields have non-null, non-empty values.
     *
     * @param array<string> $fields
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string}>}
     */
    public function validateRequired(array $fields): array
    {
        return Validator::required($this->headers, $this->materializedData, $fields);
    }

    /**
     * Validate with multiple rules.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string}>}
     */
    public function validate(array $rules): array
    {
        return Validator::validate($this->headers, $this->materializedData, $rules);
    }

    /**
     * Validate and throw exception if validation fails.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return self
     * @throws RuntimeException
     */
    public function validateOrFail(array $rules): self
    {
        $result = Validator::validate($this->headers, $this->materializedData, $rules);

        if (! $result['valid']) {
            $errorCount = count($result['errors']);

            throw new RuntimeException("Validation failed with $errorCount error(s)");
        }

        return $this;
    }

    /**
     * Filter to only valid rows.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return self
     */
    public function filterValid(array $rules): self
    {
        $validationResult = Validator::validate($this->headers, $this->materializedData, $rules);
        $invalidRows = [];

        foreach ($validationResult['errors'] as $error) {
            $invalidRows[$error['row']] = true;
        }

        $filteredData = [];

        foreach ($this->materializedData as $index => $row) {
            // Row indices in validation errors are 1-based (data row numbers)
            // materializedData indices are 0-based
            $rowNumber = $index + 1;

            if (! isset($invalidRows[$rowNumber])) {
                $filteredData[] = $row;
            }
        }

        return new self($this->headers, $filteredData);
    }

    /**
     * Filter to only invalid rows.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return self
     */
    public function filterInvalid(array $rules): self
    {
        $validationResult = Validator::validate($this->headers, $this->materializedData, $rules);
        $invalidRows = [];

        foreach ($validationResult['errors'] as $error) {
            $invalidRows[$error['row']] = true;
        }

        $filteredData = [];

        foreach ($this->materializedData as $index => $row) {
            // Row indices in validation errors are 1-based (data row numbers)
            // materializedData indices are 0-based
            $rowNumber = $index + 1;

            if (isset($invalidRows[$rowNumber])) {
                $filteredData[] = $row;
            }
        }

        return new self($this->headers, $filteredData);
    }

    // =================================================================
    // String Operations
    // =================================================================

    /**
     * Convert field values to uppercase.
     *
     * @param string $field
     * @return self
     */
    public function upper(string $field): self
    {
        return new self(...StringTransformer::upper($this->headers, $this->materializedData, $field));
    }

    /**
     * Convert field values to lowercase.
     *
     * @param string $field
     * @return self
     */
    public function lower(string $field): self
    {
        return new self(...StringTransformer::lower($this->headers, $this->materializedData, $field));
    }

    /**
     * Trim whitespace (or other characters) from field values.
     *
     * @param string $field
     * @param string $characters Characters to trim (default: whitespace)
     * @return self
     */
    public function trim(string $field, string $characters = " \t\n\r\0\x0B"): self
    {
        return new self(...StringTransformer::trim($this->headers, $this->materializedData, $field, $characters));
    }

    /**
     * Concatenate multiple fields into target field.
     *
     * @param string $targetField
     * @param array<string> $sourceFields
     * @param string $separator
     * @return self
     */
    public function concatFields(string $targetField, array $sourceFields, string $separator = ''): self
    {
        return new self(...StringTransformer::concat($this->headers, $this->materializedData, $targetField, $sourceFields, $separator));
    }

    /**
     * Extract pattern from field into new field.
     *
     * @param string $sourceField
     * @param string $targetField
     * @param string $pattern Regex pattern with capture group
     * @return self
     */
    public function extractPattern(string $sourceField, string $targetField, string $pattern): self
    {
        return new self(...StringTransformer::extract($this->headers, $this->materializedData, $sourceField, $targetField, $pattern));
    }

    // ========================================
    // Conditional Transformations
    // ========================================

    /**
     * Apply conditional logic to create new field.
     *
     * @param string $field Field to evaluate
     * @param callable $condition Function returning bool
     * @param string $target Target field name
     * @param mixed|callable $thenValue Value if condition is true
     * @param mixed|callable $elseValue Value if condition is false
     * @return self
     */
    public function when(
        string $field,
        callable $condition,
        string $target,
        mixed $thenValue,
        mixed $elseValue
    ): self {
        return new self(...ConditionalTransformer::when(
            $this->headers,
            $this->materializedData,
            $field,
            $condition,
            $target,
            $thenValue,
            $elseValue
        ));
    }

    /**
     * Return first non-null value from multiple fields.
     *
     * @param string $target Target field name
     * @param array<string> $fields Fields to check in order
     * @return self
     */
    public function coalesce(string $target, array $fields): self
    {
        return new self(...ConditionalTransformer::coalesce($this->headers, $this->materializedData, $target, $fields));
    }

    /**
     * Return null if condition is true, otherwise return original value.
     *
     * @param string $field Field to evaluate
     * @param string $target Target field name
     * @param callable $condition Function returning bool
     * @return self
     */
    public function nullIf(string $field, string $target, callable $condition): self
    {
        return new self(...ConditionalTransformer::nullIf($this->headers, $this->materializedData, $field, $target, $condition));
    }

    /**
     * Replace null values with default.
     *
     * @param string $field Field to check
     * @param string $target Target field name
     * @param mixed|callable $default Default value if null
     * @return self
     */
    public function ifNull(string $field, string $target, mixed $default): self
    {
        return new self(...ConditionalTransformer::ifNull($this->headers, $this->materializedData, $field, $target, $default));
    }

    /**
     * Evaluate multiple conditions (SQL CASE WHEN).
     *
     * @param string $field Field to evaluate
     * @param string $target Target field name
     * @param array<array{callable, mixed|callable}> $conditions Array of [condition, value] pairs
     * @param mixed|callable $default Default value if no match
     * @return self
     */
    public function case(string $field, string $target, array $conditions, mixed $default): self
    {
        return new self(...ConditionalTransformer::case(
            $this->headers,
            $this->materializedData,
            $field,
            $target,
            $conditions,
            $default
        ));
    }
}
