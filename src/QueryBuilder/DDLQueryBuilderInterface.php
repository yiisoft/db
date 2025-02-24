<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

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
     * @param ColumnInterface|string $type The column type which can contain a native database column type,
     * {@see ColumnType abstract} or {@see PseudoType pseudo} type, or can be represented as instance of
     * {@see ColumnInterface}.
     *
     * The {@see QueryBuilderInterface::buildColumnDefinition()} method will be invoked to convert column definitions
     * into SQL representation. For example, it will convert `string not null` to `varchar(255) not null`
     * and `pk` to `int PRIMARY KEY AUTO_INCREMENT` (for MySQL).
     *
     * The preferred way is to use {@see ColumnBuilder} to generate column definitions as instances of
     * {@see ColumnInterface}.
     *
     * @return string The SQL statement for adding a new column.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function addColumn(string $table, string $column, ColumnInterface|string $type): string;

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
     * The method will quote the `name`, `table`, `referenceTable` parameters before using them in the generated SQL.
     *
     * @param string $table The table to add the foreign key constraint will to.
     * @param string $name The name of the foreign key constraint.
     * @param array|string $columns The name of the column to add the constraint will on. If there are
     * many columns, separate them with commas or use an array to represent them.
     * @param string $referenceTable The table that the foreign key references to.
     * @param array|string $referenceColumns The name of the column that the foreign key references to.
     * If there are many columns, separate them with commas or use an array to represent them.
     * @param string|null $delete The `ON DELETE` option. See {@see ReferentialAction} class for possible values.
     * @param string|null $update The `ON UPDATE` option. See {@see ReferentialAction} class for possible values.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL statement for adding a foreign key constraint to an existing table.
     *
     * @psalm-param ReferentialAction::*|null $delete
     * @psalm-param ReferentialAction::*|null $update
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
     * @param ColumnInterface|string $type The column type which can contain a native database column type,
     * {@see ColumnType abstract} or {@see PseudoType pseudo} type, or can be represented as instance of
     * {@see ColumnInterface}.
     *
     * The {@see QueryBuilderInterface::buildColumnDefinition()} method will be invoked to convert column definitions
     * into SQL representation. For example, it will convert `string not null` to `varchar(255) not null`
     * and `pk` to `int PRIMARY KEY AUTO_INCREMENT` (for MySQL).
     *
     * The preferred way is to use {@see ColumnBuilder} to generate column definitions as instances of
     * {@see ColumnInterface}.
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
     * @param string|null $indexType The index type, `UNIQUE` or a DBMS specific index type or `null` by default.
     * See {@see IndexType} or driver specific `IndexType` class.
     * @param string|null $indexMethod The index organization method, if supported by DBMS.
     * See driver specific `IndexMethod` class.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL statement for creating a new index.
     *
     * @psalm-param IndexType::*|null $indexType
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        ?string $indexType = null,
        ?string $indexMethod = null
    ): string;

    /**
     * Builds an SQL statement for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * is the name of the column which will be properly quoted by the method, and definition is the type of the column
     * which can contain a native database column type, {@see ColumnType abstract} or {@see PseudoType pseudo} type,
     * or can be represented as instance of {@see ColumnInterface}.
     *
     * The {@see QueryBuilderInterface::buildColumnDefinition()} method will be invoked to convert column definitions
     * into SQL representation. For example, it will convert `string not null` to `varchar(255) not null`
     * and `pk` to `int PRIMARY KEY AUTO_INCREMENT` (for MySQL).
     *
     * The preferred way is to use {@see ColumnBuilder} to generate column definitions as instances of
     * {@see ColumnInterface}.
     *
     * ```php
     * $this->createTable(
     *     'example_table',
     *     [
     *         'id' => ColumnBuilder::primaryKey(),
     *         'name' => ColumnBuilder::string(64)->notNull(),
     *         'type' => ColumnBuilder::integer()->notNull()->defaultValue(10),
     *         'description' => ColumnBuilder::text(),
     *         'rule_name' => ColumnBuilder::string(64),
     *         'data' => ColumnBuilder::text(),
     *         'created_at' => ColumnBuilder::datetime()->notNull(),
     *         'updated_at' => ColumnBuilder::datetime(),
     *     ],
     * );
     * ```
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly put into the
     * generated SQL.
     *
     * @param string $table The name of the table to create.
     * @param array $columns The columns (name => definition) in the new table.
     * The definition can be `string` or {@see ColumnInterface} instance.
     * @param string|null $options More SQL fragments to append to the generated SQL.
     *
     * @return string The SQL statement for creating a new DB table.
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     *
     * @psalm-param array<string, ColumnInterface>|string[] $columns
     */
    public function createTable(string $table, array $columns, ?string $options = null): string;

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
