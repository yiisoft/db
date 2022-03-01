<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Generator;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Strings\NumericHelper;

abstract class DMLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string
    {
        if (empty($rows)) {
            return '';
        }

        if (($tableSchema = $this->queryBuilder->schema()->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->getColumns();
        } else {
            $columnSchemas = [];
        }

        $values = [];

        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]])) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }
                if (is_string($value)) {
                    $value = $this->queryBuilder->quoter()->quoteValue($value);
                } elseif (is_float($value)) {
                    /* ensure type cast always has . as decimal separator in all locales */
                    $value = NumericHelper::normalize((string) $value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                } elseif ($value instanceof ExpressionInterface) {
                    $value = $this->queryBuilder->buildExpression($value, $params);
                }
                $vs[] = $value;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        if (empty($values)) {
            return '';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $this->queryBuilder->quoter()->quoteColumnName($name);
        }

        return 'INSERT INTO '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);
    }

    public function delete(string $table, array|string $condition, array &$params): string
    {
        $sql = 'DELETE FROM ' . $this->queryBuilder->quoter()->quoteTableName($table);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function insert(string $table, Query|array $columns, array &$params = []): string
    {
        [$names, $placeholders, $values, $params] = $this->queryBuilder->prepareInsertValues($table, $columns, $params);

        return 'INSERT INTO '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);
    }

    public function insertEx(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        return $this->insert($table, $columns, $params);
    }

    public function resetSequence(string $tableName, array|int|string|null $value = null): string
    {
        throw new NotSupportedException(static::class . ' does not support resetting sequence.');
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT EXISTS(' . $rawSql . ')';
    }

    public function update(string $table, array $columns, array|string $condition, array &$params = []): string
    {
        /**
         * @var array $lines
         * @var array $params
         */
        [$lines, $params] = $this->queryBuilder->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->queryBuilder->quoter()->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->queryBuilder->buildWhere($condition, $params);

        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params
    ): string {
        throw new NotSupportedException(static::class . ' does not support upsert.');
    }
}
