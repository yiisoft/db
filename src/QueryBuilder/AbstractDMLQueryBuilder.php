<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Iterator;
use IteratorAggregate;
use JsonException;
use Traversable;
use Yiisoft\Db\Command\QueryStatement;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
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
    public function batchInsert(string $table, array $columns, iterable $rows): array
    {
        return $this->insertBatch($table, $rows, $columns);
    }

    public function insertBatch(string $table, iterable $rows, array $columns = [], int $rowsAtOnceLimit = 0): array
    {
        if (!is_array($rows)) {
            $rows = $this->prepareTraversable($rows);
        }

        if (empty($rows)) {
            return [];
        }

        $statements = [];

        $columns = $this->extractColumnNames($rows, $columns);

        $parameters = $this->prepareBatchInsertValues($table, $rows, $columns, $rowsAtOnceLimit);

        $query = 'INSERT INTO ' . $this->quoter->quoteTableName($table);

        if (count($columns) > 0) {
            $quotedColumnNames = array_map($this->quoter->quoteColumnName(...), $columns);

            $query .= ' (' . implode(', ', $quotedColumnNames) . ')';
        }

        foreach ($parameters as $parameter) {
            $statements[] = new QueryStatement(
                $query . ' VALUES (' . implode('), (', $parameter['values']) . ')',
                $parameter['params']
            );
        }

        return $statements;
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->quoter->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        return 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values);
    }

    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function resetSequence(string $table, int|string|null $value = null): string
    {
        throw new NotSupportedException(__METHOD__ . '() is not supported by this DBMS.');
    }

    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        [$lines, $params] = $this->prepareUpdateSets($table, $columns, $params);

        $sql = 'UPDATE ' . $this->quoter->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params
    ): string {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
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
     * @param int $rowsAtOnceLimit The limit of rows inserted at once.
     *
     * @return array The values.
     *
     * @psalm-param ParamsType $params
     */
    protected function prepareBatchInsertValues(string $table, iterable $rows, array $columnNames, int $rowsAtOnceLimit = 0): array
    {
        $queryStatementParameters = [];

        $statementParameters = ['values' => [], 'params' => []];
        $maxParametersLimit = $this->queryBuilder->getParametersLimit();

        $currentStatementParams = [];
        $insertedRowsCount = 0;
        foreach ($rows as $row) {
            $statementParameters['params'] = $currentStatementParams;

            $placeholders = $this->prepareRowBatchInsertValues($table, $row, $columnNames, $currentStatementParams);

            $insertedRowsCount++;
            if ((!empty($rowsAtOnceLimit) && $insertedRowsCount > $rowsAtOnceLimit) ||
                (!empty($maxParametersLimit) && count($currentStatementParams) > $maxParametersLimit)) {
                $queryStatementParameters[] = $statementParameters;
                $statementParameters = ['values' => [], 'params' => []];
                $insertedRowsCount = 1;
                $currentStatementParams = [];
                $placeholders = $this->prepareRowBatchInsertValues($table, $row, $columnNames, $currentStatementParams);
            }

            $statementParameters['values'][] = implode(', ', $placeholders);
        }

        $statementParameters['params'] = $currentStatementParams;
        $queryStatementParameters[] = $statementParameters;

        return $queryStatementParameters;
    }

    protected function prepareRowBatchInsertValues(
        string $table,
        mixed $row,
        array $columnNames,
        array &$currentStatementParams
    ): array {
        $i = 0;

        /** @var string[] $names */
        $names = array_values($columnNames);
        $keys = array_fill_keys($names, false);
        $columns = $this->schema->getTableSchema($table)?->getColumns() ?? [];

        $placeholders = $keys;



        /** @var int|string $key */
        foreach ($row as $key => $value) {
            $columnName = $columnNames[$key] ?? (isset($keys[$key]) ? $key : $names[$i] ?? $i);

            if (isset($columns[$columnName])) {
                $value = $columns[$columnName]->dbTypecast($value);
            }

            if ($value instanceof ExpressionInterface) {
                $placeholders[$columnName] = $this->queryBuilder->buildExpression($value, $currentStatementParams);
            } else {
                $placeholders[$columnName] = $this->queryBuilder->bindParam($value, $currentStatementParams);
            }

            ++$i;
        }

        return $placeholders;
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
    final protected function extractColumnNames(array|Iterator $rows, array $columns): array
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
     * @return array Array of quoted column names, values, and params.
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
                $names[] = $this->quoter->quoteColumnName($title);
            } else {
                if ($field instanceof ExpressionInterface) {
                    $field = $this->queryBuilder->buildExpression($field, $params);
                }

                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                    $names[] = $this->quoter->quoteColumnName($matches[2]);
                } else {
                    $names[] = $this->quoter->quoteColumnName($field);
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
     * @return array Array of quoted column names, placeholders, values, and params.
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

        $names = [];
        $placeholders = [];
        $columns = $this->normalizeColumnNames($columns);
        $tableColumns = $this->schema->getTableSchema($table)?->getColumns() ?? [];

        foreach ($columns as $name => $value) {
            $names[] = $this->quoter->quoteColumnName($name);

            if (isset($tableColumns[$name])) {
                $value = $tableColumns[$name]->dbTypecast($value);
            }

            if ($value instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($value, $params);
            } else {
                $placeholders[] = $this->queryBuilder->bindParam($value, $params);
            }
        }

        return [$names, $placeholders, '', $params];
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
     * @psalm-return array{0: string[], 1: array}
     */
    protected function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $sets = [];
        $columns = $this->normalizeColumnNames($columns);
        $tableColumns = $this->schema->getTableSchema($table)?->getColumns() ?? [];

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

        return [$sets, $params];
    }

    /**
     * Prepare column names and constraints for "upsert" operation.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     * @psalm-param Constraint[] $constraints
     *
     * @return array Array of unique, insert and update quoted column names.
     * @psalm-return array{0: string[], 1: string[], 2: string[]|null}
     */
    protected function prepareUpsertColumns(
        string $table,
        QueryInterface|array $insertColumns,
        QueryInterface|bool|array $updateColumns,
        array &$constraints = []
    ): array {
        if ($insertColumns instanceof QueryInterface) {
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            $insertNames = $this->getNormalizeColumnNames(array_keys($insertColumns));

            $insertNames = array_map(
                $this->quoter->quoteColumnName(...),
                $insertNames,
            );
        }

        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        if ($updateColumns === true) {
            return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
        }

        return [$uniqueNames, $insertNames, null];
    }

    /**
     * Returns all quoted column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
     * for the named table removing constraints which didn't cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name The table name, may contain schema name if any. Don't quote the table name.
     * @param string[] $columns Source column list.
     * @param array $constraints This parameter optionally receives a matched constraint list. The constraints
     * will be unique by their column names.
     *
     * @throws JsonException
     *
     * @return string[] The quoted column names.
     *
     * @psalm-param Constraint[] $constraints
    */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$constraints = []): array
    {
        $primaryKey = $this->schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        $tableIndexes = $this->schema->getTableIndexes($name);

        foreach ($tableIndexes as $constraint) {
            if ($constraint->isUnique()) {
                $constraints[] = $constraint;
            }
        }

        $constraints = array_merge($constraints, $this->schema->getTableUniques($name));

        /**
         * Remove duplicates
         *
         * @psalm-var Constraint[] $constraints
         */
        $constraints = array_combine(
            array_map(
                static function (Constraint $constraint): string {
                    $columns = (array) $constraint->getColumnNames();
                    sort($columns, SORT_STRING);
                    return json_encode($columns, JSON_THROW_ON_ERROR);
                },
                $constraints
            ),
            $constraints
        );

        $columnNames = [];
        $quoter = $this->quoter;

        // Remove all constraints which don't cover the specified column list.
        $constraints = array_values(
            array_filter(
                $constraints,
                static function (Constraint $constraint) use ($quoter, $columns, &$columnNames): bool {
                    /** @psalm-var string[] $constraintColumnNames */
                    $constraintColumnNames = (array) $constraint->getColumnNames();

                    $constraintColumnNames = array_map(
                        $quoter->quoteColumnName(...),
                        $constraintColumnNames,
                    );

                    $result = empty(array_diff($constraintColumnNames, $columns));

                    if ($result) {
                        $columnNames = array_merge($columnNames, $constraintColumnNames);
                    }

                    return $result;
                }
            )
        );

        /** @psalm-var string[] $columnNames */
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
