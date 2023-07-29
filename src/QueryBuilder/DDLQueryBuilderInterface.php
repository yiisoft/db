<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Builder\ColumnInterface;

/**
 * Defines methods for building SQL statements for DDL (data definition language).
 *
 * @link https://en.wikipedia.org/wiki/Data_definition_language
 */
interface DDLQueryBuilderInterface
{
    /**
     * Creates an SQL command for adding a check constraint to an existing table.
     *
     * @param string $table The table to add the check constraint to.
     * @param string $name The name of the check constraint.
     * @param string $expression The SQL of the `CHECK` constraint.
     *
     * @return string The SQL statement for adding a check constraint to an existing table.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function addCheck(string $table, string $name, string $expression): string;

    /**
     * Builds an SQL statement for adding a new DB column.
     *
     * @param string $table The table to add the new column will to.
     * @param string $column The name of the new column.
     * @param string $type The column type.
     * {@see getColumnType()} Method will be invoked to convert an abstract column type (if any) into the physical one.
     * Anything that isn't recognized as an abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string The SQL statement for adding a new column.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function addColumn(string $table, string $column, string $type): string;

    /**
     * Builds an SQL command for adding comment to column.
     *
     * @param string $table The table whose column to be comment.
     * @param string $column The name of the column to comment.
     * @param string $comment The text of the comment to add.
     *
     * @throws Exception
     *
     * @return string The SQL statement for adding comment on column.
     *
     * Note: The method will quote the `table`, `column`, and `comment` parameters before using them in the generated
     * SQL.
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string;

    /**
     * Builds an SQL command for adding comment to the table.
     *
     * @param string $table The table whose column is to comment.
     * @param string $comment The text of the comment to add.
     *
     * @throws Exception
     *
     * @return string The SQL statement for adding comment on the table.
     *
     * Note: The method will quote the `table` and `comment` parameters before using them in the generated SQL.
     */
    public function addCommentOnTable(string $table, string $comment): string;

    /**
     * Creates an SQL command for adding a default value constraint to an existing table.
     *
     * @param string $table The table toi add the default value constraint to.
     * @param string $name The name of the default value constraint.
     * @param string $column The name of the column to add constraint on.
     * @param mixed $value The default value to set for the column.
     *
     * @throws Exception
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @return string the SQL statement for adding a default value constraint to an existing table.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string;

    /**
     * Builds an SQL statement for adding a foreign key constraint to an existing table.
     *
     * @param string $table The table to add the foreign key constraint will to.
     * @param string $name The name of the foreign key constraint.
     * @param array|string $columns The name of the column to add the constraint will on. If there are
     * many columns, separate them with commas or use an array to represent them.
     * @param string $referenceTable The table that the foreign key references to.
     * @param array|string $referenceColumns The name of the column that the foreign key references to.
     * If there are many columns, separate them with commas or use an array to represent them.
     * @param string|null $delete The `ON DELETE` option. Most DBMS support these options: `RESTRICT`, `CASCADE`, `NO ACTION`,
     * `SET DEFAULT`, `SET NULL`.
     * @param string|null $update The `ON UPDATE` option. Most DBMS support these options: `RESTRICT`, `CASCADE`, `NO ACTION`,
     * `SET DEFAULT`, `SET NULL`.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL statement for adding a foreign key constraint to an existing table.
     *
     * Note: The method will quote the `name`, `table`, `referenceTable` parameters before using them in the generated SQL.
     */
    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $referenceTable,
        array|string $referenceColumns,
        string|null $delete = null,
        string|null $update = null
    ): string;

    /**
     * Builds an SQL statement for adding a primary key constraint to an existing table.
     *
     * @param string $table The table to add the primary key constraint will to.
     * @param string $name The name of the primary key constraint.
     * @param array|string $columns Comma separated string or array of columns that the primary key will consist of.
     *
     * @return string The SQL statement for adding a primary key constraint to an existing table.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function addPrimaryKey(string $table, string $name, array|string $columns): string;

    /**
     * Creates an SQL command for adding a unique constraint to an existing table.
     *
     * @param string $table The table to add the unique constraint to.
     * @param string $name The name of the unique constraint.
     * @param array|string $columns The name of the column to add the constraint on. If there are many
     * columns, separate them with commas.
     *
     * @return string The SQL statement for adding a unique constraint to an existing table.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function addUnique(string $table, string $name, array|string $columns): string;

    /**
     * Builds an SQL statement for changing the definition of a column.
     *
     * @param string $table The table whose column is to change.
     * @param string $column The name of the column to change.
     * @param ColumnInterface|string $type The new column type.
     * {@see getColumnType()} Method will be invoked to convert an abstract column type (if any) into the physical one.
     * Anything that isn't recognized as an abstract type will be kept in the generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string The SQL statement for changing the definition of a column.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string;

    /**
     * Builds an SQL statement for enabling or disabling integrity check.
     *
     * @param string $schema The schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @param string $table The table name. Defaults to empty string, meaning that no table will be changed.
     * @param bool $check Whether to turn on or off the integrity check.
     *
     * @throws Exception
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @return string The SQL statement for checking integrity.
     *
     * Note: The method will quote the `table` parameters before using them in the generated SQL.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string;

    /**
     * Builds an SQL statement for creating a new index.
     *
     * @param string $table The table to create the new index for.
     * @param string $name The name of the index.
     * @param array|string $columns The column(s) to include in the index.
     * If there are many columns, separate them with commas or use an array to represent them.
     * @param string|null $indexType Type of index-supported DBMS - for example, `UNIQUE`, `FULLTEXT`, `SPATIAL`, `BITMAP` or
     * `null` as default
     * @param string|null $indexMethod For setting index organization method (with `USING`, not all DBMS)
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL statement for creating a new index.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): string;

    /**
     * Builds an SQL statement for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs ('name' => 'string'), where name
     * stands for a column name which will be quoted by the method, and definition stands for the column type which can
     * contain an abstract DB type.
     *
     * The {@see getColumnType()} method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only ('PRIMARY KEY (name, type)'), it will be directly inserted
     * into the generated SQL.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->createTable('user', ['id' => 'pk', 'name' => 'string', 'age' => 'integer']);
     * ```
     *
     * @param string $table The name of the table to create.
     * @param array $columns The columns (name => definition) in the new table.
     * @param string|null $options More SQL fragments to append to the generated SQL.
     *
     * @return string The SQL statement for creating a new DB table.
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     */
    public function createTable(string $table, array $columns, string $options = null): string;

    /**
     * Creates an SQL View.
     *
     * @param string $viewName The name of the view to create.
     * @param QueryInterface|string $subQuery The select statement which defines the view.
     * This can be either a string or a {@see Query} object.
     *
     * @throws InvalidConfigException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     * @throws Exception
     * @return string The `CREATE VIEW` SQL statement.
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): string;

    /**
     * Creates an SQL command for dropping a check constraint.
     *
     * @param string $table The table whose check constraint is to drop.
     * @param string $name The name of the check constraint to drop.
     *
     * @return string The SQL statement for dropping a check constraint.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropCheck(string $table, string $name): string;

    /**
     * Builds an SQL statement for dropping a DB column.
     *
     * @param string $table The table whose column is to drop.
     * @param string $column The name of the column to drop.
     *
     * @return string The SQL statement for dropping a DB column.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function dropColumn(string $table, string $column): string;

    /**
     * Builds an SQL command for dropping comment to column.
     *
     * @param string $table The table whose column is to comment.
     * @param string $column The name of the column to comment.
     *
     * @return string The SQL statement for dropping comment on column.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function dropCommentFromColumn(string $table, string $column): string;

    /**
     * Builds an SQL command for dropping comment to the table.
     *
     * @param string $table The table whose column is to comment.
     *
     * @return string The SQL statement for dropping comment on column.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function dropCommentFromTable(string $table): string;

    /**
     * Creates an SQL command for dropping a default value constraint.
     *
     * @param string $table The table whose default value constraint is to drop.
     * @param string $name The name of the default value constraint to drop.
     *
     * @throws Exception
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * @return string The SQL statement for dropping a default value constraint.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropDefaultValue(string $table, string $name): string;

    /**
     * Builds an SQL statement for dropping a foreign key constraint.
     *
     * @param string $table The table whose foreign is to drop.
     * @param string $name The name of the foreign key constraint to drop.
     *
     * @return string The SQL statement for dropping a foreign key constraint.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropForeignKey(string $table, string $name): string;

    /**
     * Builds an SQL statement for dropping an index.
     *
     * @param string $table The table whose index is to drop.
     * @param string $name The name of the index to drop.
     *
     * @return string The SQL statement for dropping an index.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropIndex(string $table, string $name): string;

    /**
     * Builds an SQL statement for removing a primary key constraint to an existing table.
     *
     * @param string $table The table to remove the primary key constraint from.
     * @param string $name The name of the primary key constraint to remove.
     *
     * @return string The SQL statement for removing a primary key constraint from an existing table.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropPrimaryKey(string $table, string $name): string;

    /**
     * Builds an SQL statement for dropping a DB table.
     *
     * @param string $table The table to drop.
     *
     * @return string The SQL statement for dropping a DB table.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function dropTable(string $table): string;

    /**
     * Creates an SQL command for dropping a unique constraint.
     *
     * @param string $table The table whose unique constraint is to drop.
     * @param string $name The name of the unique constraint to drop.
     *
     * @return string The SQL statement for dropping an unique constraint.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropUnique(string $table, string $name): string;

    /**
     * Drops an SQL View.
     *
     * @param string $viewName The name of the view to drop.
     *
     * @return string The `DROP VIEW` SQL statement.
     *
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function dropView(string $viewName): string;

    /**
     * Builds an SQL statement for renaming a column.
     *
     * @param string $table The table whose column is to rename.
     * @param string $oldName The old name of the column.
     * @param string $newName The new name of the column.
     *
     * @return string The SQL statement for renaming a DB column.
     *
     * Note: The method will quote the `table`, `oldName` and `newName` parameters before using them in the generated
     * SQL.
     */
    public function renameColumn(string $table, string $oldName, string $newName): string;

    /**
     * Builds an SQL statement for renaming a DB table.
     *
     * @param string $oldName The table to rename.
     * @param string $newName The new table name.
     *
     * @return string The SQL statement for renaming a DB table.
     *
     * Note: The method will quote the `oldName` and `newName` parameters before using them in the generated SQL.
     */
    public function renameTable(string $oldName, string $newName): string;

    /**
     * Builds an SQL statement for truncating a DB table.
     *
     * @param string $table The table to truncate.
     *
     * @return string The SQL statement for truncating a DB table.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function truncateTable(string $table): string;
}
