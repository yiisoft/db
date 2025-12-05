<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_map;
use function implode;
use function is_string;
use function preg_split;

/**
 * It's used to create and change the structure of database objects in a database.
 *
 * These database objects include views, schemas, tables, indexes, etc.
 *
 * @link https://en.wikipedia.org/wiki/Data_definition_language
 */
abstract class AbstractDDLQueryBuilder implements DDLQueryBuilderInterface
{
    public function __construct(
        protected QueryBuilderInterface $queryBuilder,
        protected QuoterInterface $quoter,
        protected SchemaInterface $schema,
    ) {}

    public function addCheck(string $table, string $name, string $expression): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT '
            . $this->quoter->quoteColumnName($name)
            . ' CHECK (' . $this->quoter->quoteSql($expression) . ')';
    }

    public function addColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD '
            . $this->quoter->quoteColumnName($column)
            . ' '
            . $this->queryBuilder->buildColumnDefinition($type);
    }

    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return 'COMMENT ON COLUMN '
            . $this->quoter->quoteTableName($table)
            . '.'
            . $this->quoter->quoteColumnName($column)
            . ' IS '
            . $this->quoter->quoteValue($comment);
    }

    public function addCommentOnTable(string $table, string $comment): string
    {
        return 'COMMENT ON TABLE '
            . $this->quoter->quoteTableName($table)
            . ' IS '
            . $this->quoter->quoteValue($comment);
    }

    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        ?string $delete = null,
        ?string $update = null,
    ): string {
        $sql = 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' FOREIGN KEY (' . $this->queryBuilder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->quoter->quoteTableName($referenceTable)
            . ' (' . $this->queryBuilder->buildColumns($referenceColumns) . ')';

        if ($delete !== null) {
            $sql .= ' ON DELETE ' . $delete;
        }

        if ($update !== null) {
            $sql .= ' ON UPDATE ' . $update;
        }

        return $sql;
    }

    public function addPrimaryKey(string $table, string $name, array|string $columns): string
    {
        if (is_string($columns)) {
            /** @psalm-var list<string> */
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoter->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' PRIMARY KEY (' . implode(', ', $columns) . ')';
    }

    public function addUnique(string $table, string $name, array|string $columns): string
    {
        if (is_string($columns)) {
            /** @psalm-var list<string> */
            $columns = preg_split('/\s*,\s*/', $columns, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($columns as $i => $col) {
            $columns[$i] = $this->quoter->quoteColumnName($col);
        }

        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT ' . $this->quoter->quoteColumnName($name)
            . ' UNIQUE (' . implode(', ', $columns) . ')';
    }

    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' CHANGE '
            . $this->quoter->quoteColumnName($column)
            . ' '
            . $this->quoter->quoteColumnName($column) . ' '
            . $this->queryBuilder->buildColumnDefinition($type);
    }

    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        ?string $indexType = null,
        ?string $indexMethod = null,
    ): string {
        return 'CREATE ' . (!empty($indexType) ? $indexType . ' ' : '') . 'INDEX '
            . $this->quoter->quoteTableName($name)
            . ' ON ' . $this->quoter->quoteTableName($table)
            . ' (' . $this->queryBuilder->buildColumns($columns) . ')';
    }

    public function createTable(string $table, array $columns, ?string $options = null): string
    {
        $cols = [];
        $quoter = $this->quoter;
        $queryBuilder = $this->queryBuilder;

        foreach ($columns as $name => $type) {
            if (is_string($name)) {
                $columnDefinition = match (true) {
                    $type instanceof ColumnInterface => $queryBuilder->buildColumnDefinition($type->withName($name)),
                    $type instanceof ExpressionInterface => $queryBuilder->buildExpression($type),
                    default => $queryBuilder->buildColumnDefinition($type),
                };

                $cols[] = "{$quoter->quoteColumnName($name)} $columnDefinition";
            } else {
                /** @var string $type */
                $cols[] = $type;
            }
        }

        $sql = "CREATE TABLE {$quoter->quoteTableName($table)} (\n\t"
            . implode(",\n\t", $cols)
            . "\n)";

        return $options === null ? $sql : $sql . ' ' . $options;
    }

    public function createView(string $viewName, QueryInterface|string $subQuery): string
    {
        if ($subQuery instanceof QueryInterface) {
            [$rawQuery, $params] = $this->queryBuilder->build($subQuery);

            $params = array_map($this->queryBuilder->prepareValue(...), $params);
            $subQuery = $this->queryBuilder->replacePlaceholders($rawQuery, $params);
        }

        return 'CREATE VIEW ' . $this->quoter->quoteTableName($viewName) . ' AS ' . $subQuery;
    }

    public function dropCheck(string $table, string $name): string
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

    public function dropDefaultValue(string $table, string $name): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function dropForeignKey(string $table, string $name): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropIndex(string $table, string $name): string
    {
        return 'DROP INDEX '
            . $this->quoter->quoteTableName($name)
            . ' ON '
            . $this->quoter->quoteTableName($table);
    }

    public function dropPrimaryKey(string $table, string $name): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): string
    {
        return 'DROP TABLE '
            . ($ifExists ? 'IF EXISTS ' : '')
            . $this->quoter->quoteTableName($table)
            . ($cascade ? ' CASCADE' : '');
    }

    public function dropUnique(string $table, string $name): string
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
