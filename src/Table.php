<?php

declare(strict_types=1);

namespace Phetl;

use IteratorAggregate;
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
use PDO;
use RuntimeException;
use Traversable;

/**
 * Main Table class for PHETL ETL operations.
 *
 * Wraps an iterable data source and provides fluent API for transformations.
 * First row is expected to be headers, subsequent rows are data.
 *
 * @implements IteratorAggregate<int, array<int|string, mixed>>
 */
class Table implements IteratorAggregate
{
    /**
     * @var array<int, array<int|string, mixed>>
     */
    private readonly array $materializedData;

    /**
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public function __construct(
        iterable $data
    ) {
        // Materialize the data to allow multiple iterations
        $this->materializedData = is_array($data) ? $data : iterator_to_array($data, false);
    }

    /**
     * Create a Table from an array.
     *
     * @param array<int, array<int|string, mixed>> $data
     */
    public static function fromArray(array $data): self
    {
        $extractor = new ArrayExtractor($data);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a CSV file.
     */
    public static function fromCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): self {
        $extractor = new CsvExtractor($filePath, $delimiter, $enclosure, $escape);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a JSON file.
     */
    public static function fromJson(string $filePath): self
    {
        $extractor = new JsonExtractor($filePath);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a database query.
     *
     * @param array<string, mixed> $params
     */
    public static function fromDatabase(PDO $pdo, string $query, array $params = []): self
    {
        $extractor = new DatabaseExtractor($pdo, $query, $params);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a RESTful API.
     *
     * @param array<string, mixed> $config
     */
    public static function fromRestApi(string $url, array $config = []): self
    {
        $extractor = new RestApiExtractor($url, $config);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from an Excel file.
     *
     * @param string|int|null $sheet Sheet name (string), index (int), or null for active sheet
     */
    public static function fromExcel(string $filePath, string|int|null $sheet = null): self
    {
        $extractor = new ExcelExtractor($filePath, $sheet);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from any extractor.
     */
    public static function fromExtractor(ExtractorInterface $extractor): self
    {
        return new self($extractor->extract());
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
        return $loader->load($this->materializedData);
    }

    /**
     * Load data to a JSON file.
     */
    public function toJson(string $filePath, bool $prettyPrint = false): LoadResult
    {
        $loader = new JsonLoader($filePath, $prettyPrint);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data to a database table.
     */
    public function toDatabase(PDO $pdo, string $tableName): LoadResult
    {
        $loader = new DatabaseLoader($pdo, $tableName);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data to an Excel file.
     *
     * @param string|int|null $sheet Sheet name (string), index (int), or null for active sheet
     */
    public function toExcel(string $filePath, string|int|null $sheet = null): LoadResult
    {
        $loader = new ExcelLoader($filePath, $sheet);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data using any loader.
     */
    public function toLoader(LoaderInterface $loader): LoadResult
    {
        return $loader->load($this->materializedData);
    }

    /**
     * Get the underlying data as an array (materializes all rows).
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * Get iterator for the table data.
     *
     * @return Traversable<int, array<int|string, mixed>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->materializedData;
    }

    /**
     * Display first N rows (for debugging/inspection).
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function look(int $limit = 10): array
    {
        $rows = [];
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
     * Count the number of rows (including header).
     */
    public function count(): int
    {
        return count($this->materializedData);
    }

    /**
     * Get the header row (first row).
     *
     * @return array<int|string, mixed>
     */
    public function header(): array
    {
        if ($this->materializedData === []) {
            return [];
        }

        return $this->materializedData[0];
    }

    // ==================== TRANSFORMATIONS ====================

    /**
     * Select the first N rows (plus header).
     */
    public function head(int $limit): self
    {
        return new self(RowSelector::head($this->materializedData, $limit));
    }

    /**
     * Select the last N rows (plus header).
     */
    public function tail(int $limit): self
    {
        return new self(RowSelector::tail($this->materializedData, $limit));
    }

    /**
     * Select a slice of rows by range.
     * Start and stop indices exclude the header (0-indexed data rows).
     */
    public function slice(int $start, ?int $stop = null, int $step = 1): self
    {
        return new self(RowSelector::slice($this->materializedData, $start, $stop, $step));
    }

    /**
     * Skip the first N data rows (header is preserved).
     */
    public function skip(int $count): self
    {
        return new self(RowSelector::skip($this->materializedData, $count));
    }

    /**
     * Sort rows by one or more fields.
     *
     * @param string|array<string>|\Closure $key Field name, array of fields, or custom comparator
     * @param bool $reverse Sort in descending order
     */
    public function sort(string|array|\Closure $key, bool $reverse = false): self
    {
        return new self(RowSorter::sort($this->materializedData, $key, $reverse));
    }

    /**
     * Sort rows by field(s) in ascending order.
     *
     * @param string ...$fields Field names to sort by
     */
    public function sortBy(string ...$fields): self
    {
        return new self(RowSorter::sort($this->materializedData, $fields, false));
    }

    /**
     * Sort rows by field(s) in descending order.
     *
     * @param string ...$fields Field names to sort by
     */
    public function sortByDesc(string ...$fields): self
    {
        return new self(RowSorter::sort($this->materializedData, $fields, true));
    }

    /**
     * Select specific columns by name (cut in petl).
     *
     * @param string ...$columns Column names to select
     */
    public function selectColumns(string ...$columns): self
    {
        return new self(ColumnSelector::select($this->materializedData, $columns));
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
        return new self(ColumnSelector::remove($this->materializedData, $columns));
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
        return new self(ColumnRenamer::rename($this->materializedData, $mapping));
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
     * @param \Closure|mixed $value Value or function(array $row): mixed
     */
    public function addColumn(string $name, mixed $value): self
    {
        return new self(ColumnAdder::add($this->materializedData, $name, $value));
    }

    /**
     * Alias for addColumn (petl compatibility).
     *
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
        return new self(ColumnAdder::addRowNumbers($this->materializedData, $columnName));
    }

    /**
     * Apply a conversion function to values in a field.
     *
     * @param string $field Field name
     * @param callable|string $converter Conversion function
     */
    public function convert(string $field, callable|string $converter): self
    {
        return new self(ValueConverter::convert($this->materializedData, $field, $converter));
    }

    /**
     * Apply conversion functions to multiple fields.
     *
     * @param array<string, callable|string> $conversions Field => converter mapping
     */
    public function convertMultiple(array $conversions): self
    {
        return new self(ValueConverter::convertMultiple($this->materializedData, $conversions));
    }

    /**
     * Replace a specific value in a field.
     *
     * @param string $field Field name
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     */
    public function replace(string $field, mixed $oldValue, mixed $newValue): self
    {
        return new self(ValueReplacer::replace($this->materializedData, $field, $oldValue, $newValue));
    }

    /**
     * Replace multiple values in a field using a mapping.
     *
     * @param string $field Field name
     * @param array<mixed, mixed> $mapping Old value => New value
     */
    public function replaceMap(string $field, array $mapping): self
    {
        return new self(ValueReplacer::replaceMap($this->materializedData, $field, $mapping));
    }

    /**
     * Replace all occurrences of a value across all fields.
     *
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     */
    public function replaceAll(mixed $oldValue, mixed $newValue): self
    {
        return new self(ValueReplacer::replaceAll($this->materializedData, $oldValue, $newValue));
    }

    /**
     * Filter rows using a custom predicate function.
     *
     * @param \Closure $predicate Function(array $row): bool
     */
    public function filter(\Closure $predicate): self
    {
        return new self(RowFilter::filter($this->materializedData, $predicate));
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
        return new self(RowFilter::whereEquals($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field does not equal a value.
     */
    public function whereNotEquals(string $field, mixed $value): self
    {
        return new self(RowFilter::whereNotEquals($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field is greater than a value.
     */
    public function whereGreaterThan(string $field, int|float $value): self
    {
        return new self(RowFilter::whereGreaterThan($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field is less than a value.
     */
    public function whereLessThan(string $field, int|float $value): self
    {
        return new self(RowFilter::whereLessThan($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field is greater than or equal to a value.
     */
    public function whereGreaterThanOrEqual(string $field, int|float $value): self
    {
        return new self(RowFilter::whereGreaterThanOrEqual($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field is less than or equal to a value.
     */
    public function whereLessThanOrEqual(string $field, int|float $value): self
    {
        return new self(RowFilter::whereLessThanOrEqual($this->materializedData, $field, $value));
    }

    /**
     * Filter rows where a field's value is in an array.
     *
     * @param array<mixed> $values
     */
    public function whereIn(string $field, array $values): self
    {
        return new self(RowFilter::whereIn($this->materializedData, $field, $values));
    }

    /**
     * Filter rows where a field's value is not in an array.
     *
     * @param array<mixed> $values
     */
    public function whereNotIn(string $field, array $values): self
    {
        return new self(RowFilter::whereNotIn($this->materializedData, $field, $values));
    }

    /**
     * Filter rows where a field is null.
     */
    public function whereNull(string $field): self
    {
        return new self(RowFilter::whereNull($this->materializedData, $field));
    }

    /**
     * Filter rows where a field is not null.
     */
    public function whereNotNull(string $field): self
    {
        return new self(RowFilter::whereNotNull($this->materializedData, $field));
    }

    /**
     * Filter rows where a field value is true.
     */
    public function whereTrue(string $field): self
    {
        return new self(RowFilter::whereTrue($this->materializedData, $field));
    }

    /**
     * Filter rows where a field value is false.
     */
    public function whereFalse(string $field): self
    {
        return new self(RowFilter::whereFalse($this->materializedData, $field));
    }

    /**
     * Filter rows where a string field contains a substring.
     */
    public function whereContains(string $field, string $substring): self
    {
        return new self(RowFilter::whereContains($this->materializedData, $field, $substring));
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
        $iterables = [$this->materializedData];
        foreach ($tables as $table) {
            $iterables[] = $table->materializedData;
        }

        return new self(SetOperation::concat(...$iterables));
    }

    /**
     * Union this table with other tables (concat + remove duplicates).
     * Headers must match exactly.
     *
     * @param Table ...$tables Tables to union
     */
    public function union(self ...$tables): self
    {
        $iterables = [$this->materializedData];
        foreach ($tables as $table) {
            $iterables[] = $table->materializedData;
        }

        return new self(SetOperation::union(...$iterables));
    }

    /**
     * Merge tables with different headers (combines all columns).
     * Missing values are filled with null.
     *
     * @param Table ...$tables Tables to merge
     */
    public function merge(self ...$tables): self
    {
        $iterables = [$this->materializedData];
        foreach ($tables as $table) {
            $iterables[] = $table->materializedData;
        }

        return new self(SetOperation::merge(...$iterables));
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
        return new self(Join::inner($this->materializedData, $right->materializedData, $leftKey, $rightKey));
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
        return new self(Join::left($this->materializedData, $right->materializedData, $leftKey, $rightKey));
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
        return new self(Join::right($this->materializedData, $right->materializedData, $leftKey, $rightKey));
    }

    /**
     * Group rows by field(s) and apply aggregations.
     *
     * @param string|array<string> $groupBy Field(s) to group by
     * @param array<string, callable|string> $aggregations Map of output field => aggregation function
     */
    public function aggregate(string|array $groupBy, array $aggregations): self
    {
        return new self(Aggregator::aggregate($this->materializedData, $groupBy, $aggregations));
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
        return new self(Aggregator::count($this->materializedData, $groupBy));
    }

    /**
     * Sum values of a field, optionally grouped.
     *
     * @param string $field Field to sum
     * @param string|array<string>|null $groupBy Field(s) to group by
     */
    public function sumField(string $field, string|array|null $groupBy = null): self
    {
        return new self(Aggregator::sum($this->materializedData, $field, $groupBy));
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
        return new self(Reshaper::unpivot($this->materializedData, $idFields, $valueFields, $variableName, $valueName));
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
        return new self(Reshaper::pivot($this->materializedData, $indexFields, $columnField, $valueField, $aggregation));
    }

    /**
     * Transpose table - swap rows and columns.
     */
    public function transpose(): self
    {
        return new self(Reshaper::transpose($this->materializedData));
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
        return new self(Deduplicator::distinct($this->materializedData, $fields));
    }

    /**
     * Alias for distinct - petl compatibility.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness
     * @return self
     */
    public function unique(string|array|null $fields = null): self
    {
        return new self(Deduplicator::unique($this->materializedData, $fields));
    }

    /**
     * Return only duplicate rows (rows that appear more than once).
     *
     * @param string|array<string>|null $fields Field(s) to check for duplicates (null = all fields)
     * @return self
     */
    public function duplicates(string|array|null $fields = null): self
    {
        return new self(Deduplicator::duplicates($this->materializedData, $fields));
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
        return new self(Deduplicator::countDistinct($this->materializedData, $fields, $countField));
    }

    /**
     * Check if all rows are unique.
     *
     * @param string|array<string>|null $fields Field(s) to check for uniqueness (null = all fields)
     * @return bool
     */
    public function isUnique(string|array|null $fields = null): bool
    {
        return Deduplicator::isUnique($this->materializedData, $fields);
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
        return Validator::required($this->materializedData, $fields);
    }

    /**
     * Validate with multiple rules.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string}>}
     */
    public function validate(array $rules): array
    {
        return Validator::validate($this->materializedData, $rules);
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
        $result = Validator::validate($this->materializedData, $rules);

        if (!$result['valid']) {
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
        $validationResult = Validator::validate($this->materializedData, $rules);
        $invalidRows = [];

        foreach ($validationResult['errors'] as $error) {
            $invalidRows[$error['row']] = true;
        }

        $filtered = [];
        $filtered[] = $this->materializedData[0]; // Header

        foreach ($this->materializedData as $index => $row) {
            if ($index === 0) {
                continue; // Skip header
            }

            if (!isset($invalidRows[$index])) {
                $filtered[] = $row;
            }
        }

        return new self($filtered);
    }

    /**
     * Filter to only invalid rows.
     *
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return self
     */
    public function filterInvalid(array $rules): self
    {
        $validationResult = Validator::validate($this->materializedData, $rules);
        $invalidRows = [];

        foreach ($validationResult['errors'] as $error) {
            $invalidRows[$error['row']] = true;
        }

        $filtered = [];
        $filtered[] = $this->materializedData[0]; // Header

        foreach ($this->materializedData as $index => $row) {
            if ($index === 0) {
                continue; // Skip header
            }

            if (isset($invalidRows[$index])) {
                $filtered[] = $row;
            }
        }

        return new self($filtered);
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
        return new self(StringTransformer::upper($this->materializedData, $field));
    }

    /**
     * Convert field values to lowercase.
     *
     * @param string $field
     * @return self
     */
    public function lower(string $field): self
    {
        return new self(StringTransformer::lower($this->materializedData, $field));
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
        return new self(StringTransformer::trim($this->materializedData, $field, $characters));
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
        return new self(StringTransformer::concat($this->materializedData, $targetField, $sourceFields, $separator));
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
        return new self(StringTransformer::extract($this->materializedData, $sourceField, $targetField, $pattern));
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
        return new self(ConditionalTransformer::when(
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
        return new self(ConditionalTransformer::coalesce($this->materializedData, $target, $fields));
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
        return new self(ConditionalTransformer::nullIf($this->materializedData, $field, $target, $condition));
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
        return new self(ConditionalTransformer::ifNull($this->materializedData, $field, $target, $default));
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
        return new self(ConditionalTransformer::case(
            $this->materializedData,
            $field,
            $target,
            $conditions,
            $default
        ));
    }
}
