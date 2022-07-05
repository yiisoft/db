<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function implode;
use function is_string;
use function preg_split;

abstract class DDLQueryBuilder implements DDLQueryBuilderInterface
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
    }

    public function addCheck(string $name, string $table, string $expression): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT '
            . $this->quoter->quoteColumnName($name)
            . ' CHECK (' . $this->quoter->quoteSql($expression) . ')';
    }

    public function addColumn(string $table, string $column, string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD '
            . $this->quoter->quoteColumnName($column)
            . ' '
            . $this->queryBuilder->getColumnType($type);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return 'COMMENT ON COLUMN '
            . $this->quoter->quoteTableName($table)
            . '.'
            . $this->quoter->quoteColumnName($column)
            . ' IS '
            . (string) $this->quoter->quoteValue($comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'COMMENT ON TABLE '
            . $this->quoter->quoteTableName($table)
            . ' IS '
            . (string) $this->quoter->quoteValue($comment);
    }

    /**
     * @throws NotSupportedException
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        throw new NotSupportedException(static::class . ' does not support adding default value constraints.');
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
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
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->queryBuilder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->quoter->quoteTableName($refTable)
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

        /** @var string[] $columns */
        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoter->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' PRIMARY KEY (' . implode(', ', $columns) . ')';
    }

    public function addUnique(string $name, string $table, array|string $columns): string
    {
        if (is_string($columns)) {
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var string[] $columns */
        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoter->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' UNIQUE (' . implode(', ', $columns) . ')';
    }

    public function alterColumn(string $table, string $column, ColumnSchemaBuilder|string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' CHANGE '
            . $this->quoter->quoteColumnName($column)
            . ' '
            . $this->quoter->quoteColumnName($column) . ' '
            . $this->queryBuilder->getColumnType($type);
    }

    /**
     * @throws NotSupportedException
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException(static::class . ' does not support enabling/disabling integrity check.');
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    public function createIndex(string $name, string $table, array|string $columns, ?string $indexType = null, ?string $indexMethod = null): string
    {
        return 'CREATE ' . ($indexType ? ($indexType . ' ') : '') . 'INDEX '
            . $this->quoter->quoteTableName($name)
            . ' ON ' . $this->quoter->quoteTableName($table)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function createTable(string $table, array $columns, ?string $options = null): string
    {
        $cols = [];

        /** @psalm-var string[] $columns */
        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $cols[] = "\t"
                    . $this->quoter->quoteColumnName($name)
                    . ' '
                    . $this->queryBuilder->getColumnType($type);
            } else {
                $cols[] = "\t" . $type;
            }
        }

        $sql = 'CREATE TABLE '
            . $this->quoter->quoteTableName($table) . " (\n" . implode(",\n", $cols) . "\n)";

        return $options === null ? $sql : $sql . ' ' . $options;
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): string
    {
        if ($subQuery instanceof QueryInterface) {
            [$rawQuery, $params] = $this->queryBuilder->build($subQuery);

            /** @var mixed $value */
            foreach ($params as $key => $value) {
                /** @var mixed */
                $params[$key] = $this->quoter->quoteValue($value);
            }

            $subQuery = strtr($rawQuery, $params);
        }

        return 'CREATE VIEW ' . $this->quoter->quoteTableName($viewName) . ' AS ' . $subQuery;
    }

    public function dropCheck(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropColumn(string $table, string $column): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP COLUMN '
            . $this->quoter->quoteColumnName($column);
    }

    public function dropCommentFromColumn(string $table, string $column): string
    {
        return 'COMMENT ON COLUMN '
            . $this->quoter->quoteTableName($table)
            . '.'
            . $this->quoter->quoteColumnName($column)
            . ' IS NULL';
    }

    public function dropCommentFromTable(string $table): string
    {
        return 'COMMENT ON TABLE '
             . $this->quoter->quoteTableName($table)
             . ' IS NULL';
    }

    /**
     * @throws NotSupportedException
     */
    public function dropDefaultValue(string $name, string $table): string
    {
        throw new NotSupportedException(static::class . ' does not support dropping default value constraints.');
    }

    public function dropForeignKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropIndex(string $name, string $table): string
    {
        return 'DROP INDEX '
            . $this->quoter->quoteTableName($name)
            . ' ON '
            . $this->quoter->quoteTableName($table);
    }

    public function dropPrimaryKey(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropTable(string $table): string
    {
        return 'DROP TABLE ' . $this->quoter->quoteTableName($table);
    }

    public function dropUnique(string $name, string $table): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropView(string $viewName): string
    {
        return 'DROP VIEW ' . $this->quoter->quoteTableName($viewName);
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' RENAME COLUMN ' . $this->quoter->quoteColumnName($oldName)
            . ' TO ' . $this->quoter->quoteColumnName($newName);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'RENAME TABLE '
            . $this->quoter->quoteTableName($oldName)
            . ' TO ' . $this->quoter->quoteTableName($newName);
    }

    public function truncateTable(string $table): string
    {
        return 'TRUNCATE TABLE ' . $this->quoter->quoteTableName($table);
    }
}
