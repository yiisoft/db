<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Iterator;
use IteratorAggregate;
use Traversable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Index;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_combine;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function get_object_vars;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function preg_match;
use function reset;
use function sort;

use const JSON_THROW_ON_ERROR;

/**
 * It's used to manipulate data in tables.
 *
 * This manipulation involves inserting data into database tables, retrieving existing data, deleting data from existing
 * tables and modifying existing data.
 *
 * @link https://en.wikipedia.org/wiki/Data_manipulation_language
 *
 * @psalm-import-type ParamsType from ConnectionInterface
 * @psalm-import-type BatchValues from DMLQueryBuilderInterface
 */
abstract class AbstractDMLQueryBuilder implements DMLQueryBuilderInterface
{
    protected bool $typecasting = true;

    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
        protected QuoterInterface $quoter,
        protected SchemaInterface $schema
    ) {
    }

    /**
     * @param string[] $columns
     *
     * @psalm-param BatchValues $rows
     * @psalm-param ParamsType $params
     *
     * @deprecated Use {@see insertBatch()} instead. It will be removed in version 3.0.0.
     */
    public function batchInsert(string $table, array $columns, iterable $rows, array &$params = []): string
    {
        return $this->insertBatch($table, $rows, $columns, $params);
    }

    public function insertBatch(string $table, iterable $rows, array $columns = [], array &$params = []): string
    {
        if (!is_array($rows)) {
            $rows = $this->prepareTraversable($rows);
        }

        if (empty($rows)) {
            return '';
        }

        $columns = $this->extractColumnNames($rows, $columns);
        $values = $this->prepareBatchInsertValues($table, $rows, $columns, $params);

        $query = 'INSERT INTO ' . $this->quoter->quoteTableName($table);

        if (count($columns) > 0) {
            $quotedColumnNames = array_map($this->quoter->quoteColumnName(...), $columns);

            $query .= ' (' . implode(', ', $quotedColumnNames) . ')';
        }

        return $query . ' VALUES (' . implode('), (', $values) . ')';
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->quoter->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function insert(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        $quotedNames = array_map($this->quoter->quoteColumnName(...), $names);

        return 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($quotedNames) ? ' (' . implode(', ', $quotedNames) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values);
    }

    /** @throws NotSupportedException */
    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function isTypecastingEnabled(): bool
    {
        return $this->typecasting;
    }

    /** @throws NotSupportedException */
    public function resetSequence(string $table, int|string|null $value = null): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        $updates = $this->prepareUpdateSets($table, $columns, $params);

        $sql = 'UPDATE ' . $this->quoter->quoteTableName($table) . ' SET ' . implode(', ', $updates);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /** @throws NotSupportedException */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    /** @throws NotSupportedException */
    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function withTypecasting(bool $typecasting = true): static
    {
        $new = clone $this;
        $new->typecasting = $typecasting;
        return $new;
    }

    /**
     * Prepare traversable for batch insert.
     *
     * @param Traversable $rows The rows to be batch inserted into the table.
     *
     * @return array|Iterator The prepared rows.
     *
     * @psalm-return Iterator|array<iterable<array-key, mixed>>
     */
    final protected function prepareTraversable(Traversable $rows): Iterator|array
    {
        while ($rows instanceof IteratorAggregate) {
            $rows = $rows->getIterator();
        }

        /** @var Iterator $rows */
        if (!$rows->valid()) {
            return [];
        }

        return $rows;
    }

    /**
     * Prepare values for batch insert.
     *
     * @param string $table The table name.
     * @param iterable $rows The rows to be batch inserted into the table.
     * @param string[] $columnNames The column names.
     * @param array $params The binding parameters that will be generated by this method.
     *
     * @return string[] The values.
     *
     * @psalm-param ParamsType $params
     */
    protected function prepareBatchInsertValues(string $table, iterable $rows, array $columnNames, array &$params): array
    {
        $values = [];
        /** @var string[] $names */
        $names = array_values($columnNames);
        $keys = array_fill_keys($names, false);
        $columns = $this->typecasting ? $this->schema->getTableSchema($table)?->getColumns() ?? [] : [];
        $queryBuilder = $this->queryBuilder;

        foreach ($rows as $row) {
            $i = 0;
            $placeholders = $keys;

            /** @var int|string $key */
            foreach ($row as $key => $value) {
                $columnName = $columnNames[$key] ?? (isset($keys[$key]) ? $key : $names[$i] ?? $i);

                if (isset($columns[$columnName])) {
                    $value = $columns[$columnName]->dbTypecast($value);
                }

                $placeholders[$columnName] = $queryBuilder->buildValue($value, $params);

                ++$i;
            }

            $values[] = implode(', ', $placeholders);
        }

        return $values;
    }

    /**
     * Extract column names from columns and rows.
     *
     * @param array[]|Iterator $rows The rows to be batch inserted into the table.
     * @param string[] $columns The column names.
     *
     * @return string[] The column names.
     *
     * @psalm-param Iterator|non-empty-array<iterable<array-key, mixed>> $rows
     */
    protected function extractColumnNames(array|Iterator $rows, array $columns): array
    {
        $columns = $this->getNormalizeColumnNames($columns);

        if (!empty($columns)) {
            return $columns;
        }

        if ($rows instanceof Iterator) {
            $row = $rows->current();
        } else {
            $row = reset($rows);
        }

        $row = match (gettype($row)) {
            'array' => $row,
            'object' => $row instanceof Traversable
                ? iterator_to_array($row)
                : get_object_vars($row),
            default => [],
        };

        if (array_key_exists(0, $row)) {
            return [];
        }

        /** @var string[] $columnNames */
        $columnNames = array_keys($row);

        return array_combine($columnNames, $columnNames);
    }

    /**
     * Prepare select-subQuery and field names for `INSERT INTO ... SELECT` SQL statement.
     *
     * @param QueryInterface $columns Object, which represents a select query.
     * @param array $params The parameters to bind to the generated SQL statement. These parameters will be included
     * in the result, with the more parameters generated during the query building process.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return array Array of column names, values, and params.
     *
     * @psalm-param ParamsType $params
     * @psalm-return array{0: string[], 1: string, 2: array}
     */
    protected function prepareInsertSelectSubQuery(QueryInterface $columns, array $params = []): array
    {
        /** @psalm-var string[] $select */
        $select = $columns->getSelect();

        if (empty($select) || in_array('*', $select, true)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->queryBuilder->build($columns, $params);

        $names = [];

        foreach ($select as $title => $field) {
            if (is_string($title)) {
                $names[] = $title;
            } else {
                if ($field instanceof ExpressionInterface) {
                    $field = $this->queryBuilder->buildExpression($field, $params);
                }

                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                    $names[] = $matches[2];
                } else {
                    $names[] = $field;
                }
            }
        }

        return [$names, $values, $params];
    }

    /**
     * Prepare column names and placeholders for `INSERT` SQL statement.
     *
     * @param string $table The table to insert new rows into.
     * @param array|QueryInterface $columns The column data (name => value) to insert into the table or instance of
     * {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array $params The binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @return array Array of column names, placeholders, values, and params.
     *
     * @psalm-param ParamsType $params
     * @psalm-return array{0: string[], 1: string[], 2: string, 3: array}
     */
    protected function prepareInsertValues(string $table, array|QueryInterface $columns, array $params = []): array
    {
        if (empty($columns)) {
            return [[], [], 'DEFAULT VALUES', []];
        }

        if ($columns instanceof QueryInterface) {
            [$names, $values, $params] = $this->prepareInsertSelectSubQuery($columns, $params);
            return [$names, [], $values, $params];
        }

        $placeholders = [];
        $columns = $this->normalizeColumnNames($columns);
        $tableColumns = $this->typecasting ? $this->schema->getTableSchema($table)?->getColumns() ?? [] : [];

        foreach ($columns as $name => $value) {
            if (isset($tableColumns[$name])) {
                $value = $tableColumns[$name]->dbTypecast($value);
            }

            if ($value instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $placeholders[] = $this->queryBuilder->bindParam($value, $params);
            }
        }

        return [array_keys($columns), $placeholders, '', $params];
    }

    /**
     * Prepare column names and placeholders for `UPDATE` SQL statement.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     *
     * @psalm-param ParamsType $params
     *
     * @return string[]
     */
    protected function prepareUpdateSets(string $table, array $columns, array &$params): array
    {
        $sets = [];
        $columns = $this->normalizeColumnNames($columns);
        $tableColumns = $this->typecasting ? $this->schema->getTableSchema($table)?->getColumns() ?? [] : [];

        foreach ($columns as $name => $value) {
            if (isset($tableColumns[$name])) {
                $value = $tableColumns[$name]->dbTypecast($value);
            }

            if ($value instanceof ExpressionInterface) {
                $placeholder = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $placeholder = $this->queryBuilder->bindParam($value, $params);
            }

            $sets[] = $this->quoter->quoteColumnName($name) . '=' . $placeholder;
        }

        return $sets;
    }

    /**
     * Prepare column names and constraints for "upsert" operation.
     *
     * @param Index[] $constraints
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     *
     * @return array Array of unique, insert and update column names.
     * @psalm-return array{0: string[], 1: string[], 2: string[]|null}
     */
    protected function prepareUpsertColumns(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        array &$constraints = []
    ): array {
        if ($insertColumns instanceof QueryInterface) {
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            $insertNames = $this->getNormalizeColumnNames(array_keys($insertColumns));
        }

        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        if ($updateColumns === true) {
            return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
        }

        return [$uniqueNames, $insertNames, null];
    }

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which didn't cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name The table name, may contain schema name if any. Don't quote the table name.
     * @param string[] $columns Source column list.
     * @param Index[] $indexes This parameter optionally receives a matched index list.
     * The constraints will be unique by their column names.
     *
     * @return string[] The column names.
    */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$indexes = []): array
    {
        $primaryKey = $this->schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $indexes[] = $primaryKey;
        }

        $tableIndexes = $this->schema->getTableIndexes($name);

        foreach ($tableIndexes as $index) {
            if ($index->isUnique) {
                $indexes[] = $index;
            }
        }

        $indexes = array_merge($indexes, $this->schema->getTableUniques($name));

        /**
         * Remove duplicates
         *
         * @var Index[] $indexes
         */
        $indexes = array_combine(
            array_map(
                static function (Index $index): string {
                    $columns = $index->columnNames;
                    sort($columns, SORT_STRING);
                    return json_encode($columns, JSON_THROW_ON_ERROR);
                },
                $indexes
            ),
            $indexes
        );

        $columnNames = [];

        // Remove all indexes which don't cover the specified column list.
        $indexes = array_values(
            array_filter(
                $indexes,
                static function (Index $index) use ($columns, &$columnNames): bool {
                    $indexColumnNames = $index->columnNames;

                    $result = empty(array_diff($indexColumnNames, $columns));

                    if ($result) {
                        $columnNames = array_merge($columnNames, $indexColumnNames);
                    }

                    return $result;
                }
            )
        );

        /** @var string[] $columnNames */
        return array_unique($columnNames);
    }

    /**
     * Normalizes the column names.
     *
     * @param array $columns The column data (name => value).
     *
     * @return array The normalized column names (name => value).
     *
     * @psalm-return array<string, mixed>
     */
    protected function normalizeColumnNames(array $columns): array
    {
        /** @var string[] $columnNames */
        $columnNames = array_keys($columns);
        $normalizedNames = $this->getNormalizeColumnNames($columnNames);

        return array_combine($normalizedNames, $columns);
    }

    /**
     * Get normalized column names
     *
     * @param string[] $columns The column names.
     *
     * @return string[] Normalized column names.
     */
    protected function getNormalizeColumnNames(array $columns): array
    {
        foreach ($columns as &$name) {
            $name = $this->quoter->ensureColumnName($name);
            $name = $this->quoter->unquoteSimpleColumnName($name);
        }

        return $columns;
    }
}
