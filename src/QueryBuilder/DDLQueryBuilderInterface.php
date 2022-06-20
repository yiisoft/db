<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;

interface DDLQueryBuilderInterface
{
    /**
     * Creates a SQL command for adding a check constraint to an existing table.
     *
     * @param string $name the name of the check constraint. The name will be properly quoted by the method.
     * @param string $table the table that the check constraint will be added to. The name will be properly quoted by
     * the method.
     * @param string $expression the SQL of the `CHECK` constraint.
     *
     * @return string the SQL statement for adding a check constraint to an existing table.
     */
    public function addCheck(string $name, string $table, string $expression): string;

    /**
     * Builds a SQL statement for adding a new DB column.
     *
     * @param string $table the table that the new column will be added to. The table name will be properly quoted by
     * the method.
     * @param string $column the name of the new column. The name will be properly quoted by the method.
     * @param string $type the column type. The {@see getColumnType()} method will be invoked to convert abstract column
     * type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the
     * generated SQL.
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @return string the SQL statement for adding a new column.
     */
    public function addColumn(string $table, string $column, string $type): string;

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string;

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $comment the text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     *
     * @return string the SQL statement for adding comment on table.
     */
    public function addCommentOnTable(string $table, string $comment): string;

    /**
     * Creates a SQL command for adding a default value constraint to an existing table.
     *
     * @param string $name the name of the default value constraint.
     * The name will be properly quoted by the method.
     * @param string $table the table that the default value constraint will be added to.
     * The name will be properly quoted by the method.
     * @param string $column the name of the column to that the constraint will be added on.
     * The name will be properly quoted by the method.
     * @param mixed $value default value.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for adding a default value constraint to an existing table.
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string;

    /**
     * Builds a SQL statement for adding a foreign key constraint to an existing table. The method will properly quote
     * the table and column names.
     *
     * @param string $name the name of the foreign key constraint.
     * @param string $table the table that the foreign key constraint will be added to.
     * @param array|string $columns the name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas or use an array to represent them.
     * @param string $refTable the table that the foreign key references to.
     * @param array|string $refColumns the name of the column that the foreign key references to. If there are multiple
     * columns, separate them with commas or use an array to represent them.
     * @param string|null $delete the ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     * @param string|null $update the ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for adding a foreign key constraint to an existing table.
     */
    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        ?string $delete = null,
        ?string $update = null
    ): string;

    /**
     * Builds a SQL statement for adding a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint.
     * @param string $table the table that the primary key constraint will be added to.
     * @param array|string $columns comma separated string or array of columns that the primary key will consist of.
     *
     * @return string the SQL statement for adding a primary key constraint to an existing table.
     */
    public function addPrimaryKey(string $name, string $table, array|string $columns): string;

    /**
     * Creates a SQL command for adding a unique constraint to an existing table.
     *
     * @param string $name the name of the unique constraint. The name will be properly quoted by the method.
     * @param string $table the table that the unique constraint will be added to. The name will be properly quoted by
     * the method.
     * @param array|string $columns the name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for adding a unique constraint to an existing table.
     */
    public function addUnique(string $name, string $table, array|string $columns): string;

    /**
     * Builds a SQL statement for changing the definition of a column.
     *
     * @param string $table the table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be changed. The name will be properly quoted by the method.
     * @param ColumnSchemaBuilder|string $type the new column type. The {@see getColumnType()} method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL.
     *
     * For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, ColumnSchemaBuilder|string $type): string;

    /**
     * Builds a SQL statement for enabling or disabling integrity check.
     *
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @param string $table the table name. Defaults to empty string, meaning that no table will be changed.
     * @param bool $check whether to turn on or off the integrity check.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for checking integrity.
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string;

    /**
     * Builds a SQL statement for creating a new index.
     *
     * @param string $name the name of the index. The name will be properly quoted by the method.
     * @param string $table the table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param array|string $columns the column(s) that should be included in the index. If there are multiple columns,
     * separate them with commas or use an array to represent them. Each column name will be properly quoted by the
     * method, unless a parenthesis is found in the name.
     * @param string|null $indexType type of index supported DBMS - for example: UNIQUE, FULLTEXT, SPATIAL, BITMAP or null as default
     * @param string|null $indexMethod for setting index organization method (with 'USING', not all DBMS)
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for creating a new index.
     */
    public function createIndex(string $name, string $table, array|string $columns, ?string $indexType = null, ?string $indexMethod = null): string;

    /**
     * Builds a SQL statement for creating a new DB table.
     *
     * The columns in the new  table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be properly quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The {@see getColumnType()} method will be invoked to convert any abstract type into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly inserted
     * into the generated SQL.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->createTable('user', [
     *  'id' => 'pk',
     *  'name' => 'string',
     *  'age' => 'integer',
     * ]);
     * ```
     *
     * @param string $table the name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns the columns (name => definition) in the new table.
     * @param string|null $options additional SQL fragments that will be appended to the generated SQL.
     *
     * @return string the SQL statement for creating a new DB table.
     */
    public function createTable(string $table, array $columns, ?string $options = null): string;

    /**
     * Creates a SQL View.
     *
     * @param string $viewName the name of the view to be created.
     * @param QueryInterface|string $subQuery the select statement which defines the view.
     *
     * This can be either a string or a {@see Query} object.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the `CREATE VIEW` SQL statement.
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): string;

    /**
     * Creates a SQL command for dropping a check constraint.
     *
     * @param string $name the name of the check constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose check constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for dropping a check constraint.
     */
    public function dropCheck(string $name, string $table): string;

    /**
     * Builds a SQL statement for dropping a DB column.
     *
     * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a DB column.
     */
    public function dropColumn(string $table, string $column): string;

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column the name of the column to be commented. The column name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromColumn(string $table, string $column): string;

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table the table whose column is to be commented. The table name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for adding comment on column.
     */
    public function dropCommentFromTable(string $table): string;

    /**
     * Creates a SQL command for dropping a default value constraint.
     *
     * @param string $name the name of the default value constraint to be dropped.
     * The name will be properly quoted by the method.
     * @param string $table the table whose default value constraint is to be dropped.
     * The name will be properly quoted by the method.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for dropping a default value constraint.
     */
    public function dropDefaultValue(string $name, string $table): string;

    /**
     * Builds a SQL statement for dropping a foreign key constraint.
     *
     * @param string $name the name of the foreign key constraint to be dropped. The name will be properly quoted by
     * the method.
     * @param string $table the table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a foreign key constraint.
     */
    public function dropForeignKey(string $name, string $table): string;

    /**
     * Builds a SQL statement for dropping an index.
     *
     * @param string $name the name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table the table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping an index.
     */
    public function dropIndex(string $name, string $table): string;

    /**
     * Builds a SQL statement for removing a primary key constraint to an existing table.
     *
     * @param string $name the name of the primary key constraint to be removed.
     * @param string $table the table that the primary key constraint will be removed from.
     *
     * @return string the SQL statement for removing a primary key constraint from an existing table.
     */
    public function dropPrimaryKey(string $name, string $table): string;

    /**
     * Builds a SQL statement for dropping a DB table.
     *
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for dropping a DB table.
     */
    public function dropTable(string $table): string;

    /**
     * Creates a SQL command for dropping a unique constraint.
     *
     * @param string $name the name of the unique constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table the table whose unique constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @return string the SQL statement for dropping an unique constraint.
     */
    public function dropUnique(string $name, string $table): string;

    /**
     * Drops a SQL View.
     *
     * @param string $viewName the name of the view to be dropped.
     *
     * @return string the `DROP VIEW` SQL statement.
     */
    public function dropView(string $viewName): string;

    /**
     * Builds a SQL statement for renaming a column.
     *
     * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName the old name of the column. The name will be properly quoted by the method.
     * @param string $newName the new name of the column. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB column.
     */
    public function renameColumn(string $table, string $oldName, string $newName): string;

    /**
     * Builds a SQL statement for renaming a DB table.
     *
     * @param string $oldName the table to be renamed. The name will be properly quoted by the method.
     * @param string $newName the new table name. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for renaming a DB table.
     */
    public function renameTable(string $oldName, string $newName): string;
}
