<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Exception\NotSupportedException;

abstract class DDLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    public function addCheck(string $name, string $table, string $expression): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' ADD CONSTRAINT '
            . $this->queryBuilder->quoter()->quoteColumnName($name)
            . ' CHECK (' . $this->queryBuilder->quoter()->quoteSql($expression) . ')';
    }

    public function addColumn(string $table, string $column, string $type): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' ADD '
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . ' '
            . $this->queryBuilder->getColumnType($type);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return 'COMMENT ON COLUMN '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . '.'
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . ' IS '
            . $this->queryBuilder->quoter()->quoteValue($comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'COMMENT ON TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' IS '
            . $this->queryBuilder->quoter()->quoteValue($comment);
    }

    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        throw new NotSupportedException(static::class . ' does not support adding default value constraints.');
    }

    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): string {
        $sql = 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->queryBuilder->quoter()->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->queryBuilder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->queryBuilder->quoter()->quoteTableName($refTable)
            . ' (' . $this->queryBuilder->buildColumns($refColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            $sql .= ' ON UPDATE ' . $update;
        }

        return $sql;
    }

    public function addPrimaryKey(string $name, string $table, array|string $columns): string
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->queryBuilder->quoter()->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->queryBuilder->quoter()->quoteColumnName($name)
            . ' PRIMARY KEY (' . implode(', ', $columns) . ')';
    }

    public function addUnique(string $name, string $table, array|string $columns): string
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->queryBuilder->quoter()->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->queryBuilder->quoter()->quoteColumnName($name)
            . ' UNIQUE (' . implode(', ', $columns) . ')';
    }

    public function alterColumn(string $table, string $column, string $type): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' CHANGE '
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . ' '
            . $this->queryBuilder->quoter()->quoteColumnName($column) . ' '
            . $this->queryBuilder->getColumnType($type);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException(static::class . ' does not support enabling/disabling integrity check.');
    }

    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string
    {
        return ($unique ? 'CREATE UNIQUE INDEX ' : 'CREATE INDEX ')
            . $this->queryBuilder->quoter()->quoteTableName($name)
            . ' ON ' . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function createTable(string $table, array $columns, ?string $options = null): string
    {
        $cols = [];

        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $cols[] = "\t"
                    . $this->queryBuilder->quoter()->quoteColumnName($name)
                    . ' '
                    . $this->queryBuilder->getColumnType($type);
            } else {
                $cols[] = "\t" . $type;
            }
        }

        $sql = 'CREATE TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table) . " (\n" . implode(",\n", $cols) . "\n)";

        return $options === null ? $sql : $sql . ' ' . $options;
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): string
    {
        if ($subQuery instanceof QueryInterface) {
            [$rawQuery, $params] = $this->queryBuilder->build($subQuery);

            foreach ($params as $key => $value) {
                $params[$key] = $this->queryBuilder->quoter()->quoteValue($value);
            }

            $subQuery = strtr($rawQuery, $params);
        }

        return 'CREATE VIEW ' . $this->queryBuilder->quoter()->quoteTableName($viewName) . ' AS ' . $subQuery;
    }

    public function dropCheck(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->queryBuilder->quoter()->quoteColumnName($name);
    }

    public function dropColumn(string $table, string $column): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP COLUMN '
            . $this->queryBuilder->quoter()->quoteColumnName($column);
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . '.'
            . $this->queryBuilder->quoter()->quoteColumnName($column)
            . ' IS NULL';
    }

    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE '
             . $this->queryBuilder->quoter()->quoteTableName($table)
             . ' IS NULL';
    }

    public function dropDefaultValue(string $name, string $table): string
    {
        throw new NotSupportedException(static::class . ' does not support dropping default value constraints.');
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->queryBuilder->quoter()->quoteColumnName($name);
    }

    public function dropIndex(string $name, string $table): string
    {
        return 'DROP INDEX '
            . $this->queryBuilder->quoter()->quoteTableName($name)
            . ' ON '
            . $this->queryBuilder->quoter()->quoteTableName($table);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->queryBuilder->quoter()->quoteColumnName($name);
    }

    public function dropTable(string $table): string
    {
        return 'DROP TABLE ' . $this->queryBuilder->quoter()->quoteTableName($table);
    }

    public function dropUnique(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->queryBuilder->quoter()->quoteColumnName($name);
    }

    public function dropView(string $viewName): string
    {
        return 'DROP VIEW ' . $this->queryBuilder->quoter()->quoteTableName($viewName);
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return 'ALTER TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . ' RENAME COLUMN ' . $this->queryBuilder->quoter()->quoteColumnName($oldName)
            . ' TO ' . $this->queryBuilder->quoter()->quoteColumnName($newName);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'RENAME TABLE '
            . $this->queryBuilder->quoter()->quoteTableName($oldName)
            . ' TO ' . $this->queryBuilder->quoter()->quoteTableName($newName);
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->queryBuilder->quoter()->quoteTableName($table);
    }
}
