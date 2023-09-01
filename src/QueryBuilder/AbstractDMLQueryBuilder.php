<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Generator;
use JsonException;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function preg_match;
use function sort;

/**
 * It's used to manipulate data in tables.
 *
 * This manipulation involves inserting data into database tables, retrieving existing data, deleting data from existing
 * tables and modifying existing data.
 *
 * @link https://en.wikipedia.org/wiki/Data_manipulation_language
 */
abstract class AbstractDMLQueryBuilder implements DMLQueryBuilderInterface
{
    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
        protected QuoterInterface $quoter,
        protected SchemaInterface $schema
    ) {
    }

    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        if (($tableSchema = $this->schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        } else {
            $columnSchemas = [];
        }

        $mappedNames = $this->getNormalizeColumnNames($table, $columns);
        $values = [];

        /** @psalm-var array<array-key, array<array-key, string>> $rows */
        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($row as $index => $value) {
                if (isset($columns[$index], $mappedNames[$columns[$index]], $columnSchemas[$mappedNames[$columns[$index]]])) {
                    /** @psalm-var mixed $value */
                    $value = $this->getTypecastValue($value, $columnSchemas[$mappedNames[$columns[$index]]]);
                }

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->queryBuilder->buildExpression($value, $params);
                } else {
                    $placeholders[] = $this->queryBuilder->bindParam($value, $params);
                }
            }
            $values[] = '(' . implode(', ', $placeholders) . ')';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $this->quoter->quoteColumnName($mappedNames[$name]);
        }

        return 'INSERT INTO '
            . $this->quoter->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->quoter->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function insert(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        /**
         * @psalm-var string[] $names
         * @psalm-var string[] $placeholders
         * @psalm-var string $values
         */
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        return 'INSERT INTO '
            . $this->quoter->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);
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
        /** @psalm-var string[] $lines */
        [$lines, $params] = $this->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->quoter->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        /** @psalm-var array $params */
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
     */
    protected function prepareInsertSelectSubQuery(QueryInterface $columns, array $params = []): array
    {
        if (empty($columns->getSelect()) || in_array('*', $columns->getSelect(), true)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->queryBuilder->build($columns, $params);

        $names = [];
        $values = ' ' . $values;
        /** @psalm-var string[] $select */
        $select = $columns->getSelect();

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
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    protected function prepareInsertValues(string $table, array|QueryInterface $columns, array $params = []): array
    {
        $tableSchema = $this->schema->getTableSchema($table);
        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];
        $names = [];
        $placeholders = [];
        $values = ' DEFAULT VALUES';

        if ($columns instanceof QueryInterface) {
            [$names, $values, $params] = $this->prepareInsertSelectSubQuery($columns, $params);
        } else {
            $columns = $this->normalizeColumnNames($table, $columns);
            /**
             * @psalm-var mixed $value
             * @psalm-var array<string, mixed> $columns
             */
            foreach ($columns as $name => $value) {
                $names[] = $this->quoter->quoteColumnName($name);
                /** @var mixed $value */
                $value = $this->getTypecastValue($value, $columnSchemas[$name] ?? null);

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->queryBuilder->buildExpression($value, $params);
                } else {
                    $placeholders[] = $this->queryBuilder->bindParam($value, $params);
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * Prepare column names and placeholders for `UPDATE` SQL statement.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     */
    protected function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $tableSchema = $this->schema->getTableSchema($table);
        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];
        $sets = [];
        $columns = $this->normalizeColumnNames($table, $columns);

        /**
         * @psalm-var array<string, mixed> $columns
         * @psalm-var mixed $value
         */
        foreach ($columns as $name => $value) {
            /** @psalm-var mixed $value */
            $value = isset($columnSchemas[$name]) ? $columnSchemas[$name]->dbTypecast($value) : $value;
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
     * Prepare column names and placeholders for "upsert" operation.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @psalm-param Constraint[] $constraints
     */
    protected function prepareUpsertColumns(
        string $table,
        QueryInterface|array $insertColumns,
        QueryInterface|bool|array $updateColumns,
        array &$constraints = []
    ): array {
        $insertNames = [];

        if (!$insertColumns instanceof QueryInterface) {
            $insertColumns = $this->normalizeColumnNames($table, $insertColumns);
        }

        if (is_array($updateColumns)) {
            $updateColumns = $this->normalizeColumnNames($table, $updateColumns);
        }

        if ($insertColumns instanceof QueryInterface) {
            /** @psalm-var list<string> $insertNames */
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            /** @psalm-var array<string, string> $insertColumns */
            foreach ($insertColumns as $key => $_value) {
                $insertNames[] = $this->quoter->quoteColumnName($key);
            }
        }

        /** @psalm-var string[] $uniqueNames */
        $uniqueNames = $this->getTableUniqueColumnNames($table, $insertNames, $constraints);

        foreach ($uniqueNames as $key => $name) {
            $insertNames[$key] = $this->quoter->quoteColumnName($name);
        }

        if ($updateColumns !== true) {
            return [$uniqueNames, $insertNames, null];
        }

        return [$uniqueNames, $insertNames, array_diff($insertNames, $uniqueNames)];
    }

    /**
     * Returns all column names belonging to constraints enforcing uniqueness (`PRIMARY KEY`, `UNIQUE INDEX`, etc.)
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
     * @return array The column list.
     *
     * @psalm-param Constraint[] $constraints
    */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$constraints = []): array
    {
        $primaryKey = $this->schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        /** @psalm-var IndexConstraint[] $tableIndexes */
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
                static function (Constraint $constraint) {
                    $columns = $constraint->getColumnNames() ?? [];
                    $columns = is_array($columns) ? $columns : [$columns];
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
                static function (Constraint $constraint) use ($quoter, $columns, &$columnNames) {
                    /** @psalm-var string[]|string $getColumnNames */
                    $getColumnNames = $constraint->getColumnNames() ?? [];
                    $constraintColumnNames = [];

                    if (is_array($getColumnNames)) {
                        foreach ($getColumnNames as $columnName) {
                            $constraintColumnNames[] = $quoter->quoteColumnName($columnName);
                        }
                    }

                    $result = !array_diff($constraintColumnNames, $columns);

                    if ($result) {
                        $columnNames = array_merge((array) $columnNames, $constraintColumnNames);
                    }

                    return $result;
                }
            )
        );

        /** @psalm-var Constraint[] $columnNames */
        return array_unique($columnNames);
    }

    /**
     * @return mixed The typecast value of the given column.
     */
    protected function getTypecastValue(mixed $value, ColumnSchemaInterface $columnSchema = null): mixed
    {
        if ($columnSchema) {
            return $columnSchema->dbTypecast($value);
        }

        return $value;
    }

    /**
     * Normalizes the column names for the given table.
     *
     * @param string $table The table to save the data into.
     * @param array $columns The column data (name => value) to save into the table or instance of
     * {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement. Passing of {@see QueryInterface}.
     *
     * @return array The normalized column names (name => value).
     */
    protected function normalizeColumnNames(string $table, array $columns): array
    {
        /** @var string[] $columnList */
        $columnList = array_keys($columns);
        $mappedNames = $this->getNormalizeColumnNames($table, $columnList);

        /** @psalm-var array $normalizedColumns */
        $normalizedColumns = [];

        /**
         * @psalm-var string $name
         * @psalm-var mixed $value
         */
        foreach ($columns as $name => $value) {
            $mappedName = $mappedNames[$name] ?? $name;
            /** @psalm-var mixed */
            $normalizedColumns[$mappedName] = $value;
        }

        return $normalizedColumns;
    }

    /**
     * Get a map of normalized columns
     *
     * @param string $table The table to save the data into.
     * @param string[] $columns The column data (name => value) to save into the table or instance of
     * {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement. Passing of {@see QueryInterface}.
     *
     * @return string[] Map of normalized columns.
     */
    protected function getNormalizeColumnNames(string $table, array $columns): array
    {
        $normalizedNames = [];
        $rawTableName = $this->schema->getRawTableName($table);

        foreach ($columns as $name) {
            $parts = $this->quoter->getTableNameParts($name, true);

            if (count($parts) === 2 && $this->schema->getRawTableName($parts[0]) === $rawTableName) {
                $normalizedName = $parts[count($parts) - 1];
            } else {
                $normalizedName = $name;
            }
            $normalizedName = $this->quoter->ensureColumnName($normalizedName);

            $normalizedNames[$name] = $normalizedName;
        }

        return $normalizedNames;
    }
}
