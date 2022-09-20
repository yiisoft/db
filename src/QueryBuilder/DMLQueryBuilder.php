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
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_combine;
use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function preg_match;

abstract class DMLQueryBuilder implements DMLQueryBuilderInterface
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
    }

    /**
     * @psalm-suppress MixedArrayOffset
     */
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

        $values = [];

        /** @psalm-var array<array-key, array<array-key, string>> $rows */
        foreach ($rows as $row) {
            $placeholders = [];
            foreach ($row as $index => $value) {
                if (isset($columns[$index], $columnSchemas[$columns[$index]])) {
                    /** @var mixed $value */
                    $value = $this->getTypecastValue($value, $columnSchemas[$columns[$index]]);
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

        /** @psalm-var string[] $columns */
        foreach ($columns as $i => $name) {
            $columns[$i] = $this->quoter->quoteColumnName($name);
        }

        return 'INSERT INTO '
            . $this->quoter->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->quoter->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
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

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function insertEx(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->insert($table, $columns, $params);
    }

    /**
     * @throws NotSupportedException
     */
    public function resetSequence(string $tableName, array|int|string|null $value = null): string
    {
        throw new NotSupportedException(static::class . ' does not support resetting sequence.');
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->quoter->quoteTableName($table);
    }

    /**
     * @psalm-suppress MixedArgument
     */
    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        /** @psalm-var string[] $lines */
        [$lines, $params] = $this->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->quoter->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * @throws NotSupportedException
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params
    ): string {
        throw new NotSupportedException(static::class . ' does not support upsert.');
    }

    /**
     * Prepare select-subquery and field names for INSERT INTO ... SELECT SQL statement.
     *
     * @param QueryInterface $columns Object, which represents select query.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array array of column names, values and params.
     */
    protected function prepareInsertSelectSubQuery(QueryInterface $columns, array $params = []): array
    {
        if (empty($columns->getSelect()) || in_array('*', $columns->getSelect(), true)) {
            throw new InvalidArgumentException('Expected select query object with enumerated (named) parameters');
        }

        [$values, $params] = $this->queryBuilder->build($columns, $params);

        $names = [];
        $values = ' ' . $values;
        /** @psalm-var string[] */
        $select = $columns->getSelect();

        foreach ($select as $title => $field) {
            if (is_string($title)) {
                $names[] = $this->quoter->quoteColumnName($title);
            } elseif (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_.]+)$/', $field, $matches)) {
                $names[] = $this->quoter->quoteColumnName($matches[2]);
            } else {
                $names[] = $this->quoter->quoteColumnName($field);
            }
        }

        return [$names, $values, $params];
    }

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
            /**
             * @var mixed $value
             * @psalm-var array<string, mixed> $columns
             */
            foreach ($columns as $name => $value) {
                $names[] = $this->quoter->quoteColumnName($name);
                /** @var mixed $value */
                $value = $this->getTypecastValue($value, $columnSchemas[$name] ?? null);

                if ($value instanceof ExpressionInterface) {
                    $placeholders[] = $this->queryBuilder->buildExpression($value, $params);
                } elseif ($value instanceof QueryInterface) {
                    [$sql, $params] = $this->queryBuilder->build($value, $params);
                    $placeholders[] = "($sql)";
                } else {
                    $placeholders[] = $this->queryBuilder->bindParam($value, $params);
                }
            }
        }

        return [$names, $placeholders, $values, $params];
    }

    protected function prepareUpdateSets(string $table, array $columns, array $params = []): array
    {
        $tableSchema = $this->schema->getTableSchema($table);

        $columnSchemas = $tableSchema !== null ? $tableSchema->getColumns() : [];

        $sets = [];

        /**
         * @psalm-var array<string, mixed> $columns
         * @psalm-var mixed $value
         */
        foreach ($columns as $name => $value) {
            /** @var mixed */
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
     * @psalm-param Constraint[] $constraints
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|JsonException|NotSupportedException
     */
    protected function prepareUpsertColumns(
        string $table,
        QueryInterface|array $insertColumns,
        QueryInterface|bool|array $updateColumns,
        array &$constraints = []
    ): array {
        $insertNames = [];

        if ($insertColumns instanceof QueryInterface) {
            /** @psalm-var list<string> $insertNames */
            [$insertNames] = $this->prepareInsertSelectSubQuery($insertColumns);
        } else {
            /** @psalm-var array<string, string> $insertColumns */
            foreach ($insertColumns as $key => $_value) {
                $insertNames[] = $this->quoter->quoteColumnName($key);
            }
        }

        /** @psalm-var string[] */
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
     * for the named table removing constraints which did not cover the specified column list.
     *
     * The column list will be unique by column names.
     *
     * @param string $name table name. The table name may contain schema name if any. Do not quote the table name.
     * @param string[] $columns source column list.
     * @param Constraint[] $constraints this parameter optionally receives a matched constraint list. The constraints
     * will be unique by their column names.
     *
     * @throws JsonException
     *
     * @return array column list.
     * @psalm-suppress ReferenceConstraintViolation
    */
    private function getTableUniqueColumnNames(string $name, array $columns, array &$constraints = []): array
    {
        $constraints = [];
        $primaryKey = $this->schema->getTablePrimaryKey($name);

        if ($primaryKey !== null) {
            $constraints[] = $primaryKey;
        }

        /** @psalm-var IndexConstraint[] */
        $tableIndexes = $this->schema->getTableIndexes($name);

        foreach ($tableIndexes as $constraint) {
            if ($constraint->isUnique()) {
                $constraints[] = $constraint;
            }
        }

        $constraints = array_merge($constraints, $this->schema->getTableUniques($name));

        /** Remove duplicates */
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

        // Remove all constraints which do not cover the specified column list.
        $constraints = array_values(
            array_filter(
                $constraints,
                static function (Constraint $constraint) use ($quoter, $columns, &$columnNames) {
                    /** @psalm-var string[]|string */
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

        /** @psalm-var array $columnNames */
        return array_unique($columnNames);
    }

    /**
     * @param mixed $value
     * @param ColumnSchemaInterface|null $columnSchema
     *
     * @return mixed
     */
    protected function getTypecastValue(mixed $value, ColumnSchemaInterface $columnSchema = null): mixed
    {
        if ($columnSchema) {
            return $columnSchema->dbTypecast($value);
        }

        return $value;
    }
}
