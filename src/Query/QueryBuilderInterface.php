<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Generator;
use JsonException;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\ConditionInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

interface QueryBuilderInterface
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
     * @psalm-param array<array-key, string>|string $columns
     * @psalm-param array<array-key, string>|string $refColumns
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
     * @psalm-param array<array-key, string>|string $columns
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
     * @psalm-param array<array-key, string>|string $columns
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
     * @param string $type the new column type. The {@see getColumnType()} method will be invoked to convert abstract
     * column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept
     * in the generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null'
     * will become 'varchar(255) not null'.
     *
     * @return string the SQL statement for changing the definition of a column.
     */
    public function alterColumn(string $table, string $column, string $type): string;

    /**
     * Generates a batch INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->batchInsert('user', ['name', 'age'], [
     *     ['Tom', 30],
     *     ['Jane', 20],
     *     ['Linda', 25],
     * ]);
     * ```
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column names.
     * @param Generator|iterable $rows the rows to be batched inserted into the table.
     * @param array $params the binding parameters. This parameter exists.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the batch INSERT SQL statement.
     */
    public function batchInsert(string $table, array $columns, iterable|Generator $rows, array &$params = []): string;

    /**
     * Helper method to add $value to $params array using {@see PARAM_PREFIX}.
     *
     * @param mixed $value
     * @param array $params passed by reference.
     *
     * @return string the placeholder name in $params array.
     */
    public function bindParam(mixed $value, array &$params = []): string;

    /**
     * Generates a SELECT SQL statement from a {@see Query} object.
     *
     * @param Query $query the {@see Query} object from which the SQL statement will be generated.
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be included
     * in the result with the additional parameters generated during the query building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array the generated SQL statement (the first array element) and the corresponding parameters to be bound
     * to the SQL statement (the second array element). The parameters returned include those provided in `$params`.
     *
     * @psalm-return array{0: string, 1: array}
     */
    public function build(Query $query, array $params = []): array;

    /**
     * Processes columns and properly quotes them if necessary.
     *
     * It will join all columns into a string with comma as separators.
     *
     * @param array|string $columns the columns to be processed.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the processing result.
     */
    public function buildColumns(array|string $columns): string;

    /**
     * Parses the condition specification and generates the corresponding SQL expression.
     *
     * @param array|ExpressionInterface|string|null $condition the condition specification.
     * Please refer to {@see Query::where()} on how to specify a condition.
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the generated SQL expression.
     */
    public function buildCondition(array|string|ExpressionInterface|null $condition, array &$params = []): string;

    /**
     * Builds given $expression.
     *
     * @param ExpressionInterface $expression the expression to be built
     * @param array $params the parameters to be bound to the generated SQL statement. These parameters will be
     * included in the result with the additional parameters generated during the expression building process.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException when $expression building
     * is not supported by this QueryBuilder.
     *
     * @return string the SQL statement that will not be neither quoted nor encoded before passing to DBMS.
     *
     * @see ExpressionInterface
     * @see ExpressionBuilderInterface
     * @see expressionBuilders
     */
    public function buildExpression(ExpressionInterface $expression, array &$params = []): string;

    /**
     * @param array|null $tables
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<array-key, array|Query|string> $tables
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the FROM clause built from {@see Query::$from}.
     */
    public function buildFrom(?array $tables, array &$params): string;

    /**
     * @param array $columns
     * @psalm-param array<string, Expression|string> $columns
     *
     * @param array $params the binding parameters to be populated
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the GROUP BY clause
     */
    public function buildGroupBy(array $columns, array &$params = []): string;

    /**
     * @param array|string|null $condition
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the HAVING clause built from {@see Query::$having}.
     */
    public function buildHaving(array|string|null $condition, array &$params = []): string;

    /**
     * @param array $joins
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<
     *   array-key,
     *   array{
     *     0?:string,
     *     1?:array<array-key, Query|string>|string,
     *     2?:array|ExpressionInterface|string|null
     *   }|null
     * > $joins
     *
     * @throws Exception if the $joins parameter is not in proper format.
     *
     * @return string the JOIN clause built from {@see Query::$join}.
     */
    public function buildJoin(array $joins, array &$params): string;

    /**
     * @param Expression|int|null $limit
     * @param Expression|int|null $offset
     *
     * @return string the LIMIT and OFFSET clauses.
     */
    public function buildLimit(Expression|int|null $limit, Expression|int|null $offset): string;

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated
     *
     * @psalm-param array<string, Expression|int|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the ORDER BY clause built from {@see Query::$orderBy}.
     */
    public function buildOrderBy(array $columns, array &$params = []): string;

    /**
     * Builds the ORDER BY and LIMIT/OFFSET clauses and appends them to the given SQL.
     *
     * @param string $sql the existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy the order by columns. See {@see Query::orderBy} for more details on how to specify this
     * parameter.
     * @param Expression|int|null $limit the limit number. See {@see Query::limit} for more details.
     * @param Expression|int|null $offset the offset number. See {@see Query::offset} for more details.
     * @param array $params the binding parameters to be populated.
     *
     * @psalm-param array<string, Expression|int|string> $orderBy
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|int|null $limit,
        Expression|int|null $offset,
        array &$params = []
    ): string;

    /**
     * @param array $columns
     * @param array $params the binding parameters to be populated.
     * @param bool|null $distinct
     * @param string|null $selectOption
     *
     * @psalm-param array<array-key, ExpressionInterface|Query|string> $columns
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the SELECT clause built from {@see Query::$select}.
     */
    public function buildSelect(
        array $columns,
        array &$params,
        ?bool $distinct = false,
        string $selectOption = null
    ): string;

    /**
     * @param array $unions
     * @param array $params the binding parameters to be populated
     *
     * @psalm-param array<array{query:Query|string, all:bool}> $unions
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the UNION clause built from {@see Query::$union}.
     */
    public function buildUnion(array $unions, array &$params): string;

    /**
     * @param array|ConditionInterface|ExpressionInterface|string|null $condition
     * @param array $params the binding parameters to be populated.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the WHERE clause built from {@see Query::$where}.
     */
    public function buildWhere(
        array|string|ConditionInterface|ExpressionInterface|null $condition,
        array &$params = []
    ): string;

    /**
     * @param array $withs
     * @param array $params
     *
     * @psalm-param array<array-key, array{query:string|Query, alias:string, recursive:bool}> $withs
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string
     */
    public function buildWithQueries(array $withs, array &$params): string;

    /**
     * Transforms $condition defined in array format (as described in {@see Query::where()} to instance of
     *
     * @param array $condition.
     *
     * @throws InvalidArgumentException
     *
     * @return ConditionInterface
     *
     * {@see ConditionInterface|ConditionInterface} according to {@see conditionClasses} map.
     */
    public function createConditionFromArray(array $condition): ConditionInterface;

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
     * @param bool $unique whether to add UNIQUE constraint on the created index.
     *
     * @psalm-param array<array-key, ExpressionInterface|string>|string $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the SQL statement for creating a new index.
     */
    public function createIndex(string $name, string $table, array|string $columns, bool $unique = false): string;

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
     * @psalm-param array<array-key, ColumnSchemaBuilder|string> $columns
     *
     * @return string the SQL statement for creating a new DB table.
     */
    public function createTable(string $table, array $columns, ?string $options = null): string;

    /**
     * Creates a SQL View.
     *
     * @param string $viewName the name of the view to be created.
     * @param Query|string $subQuery the select statement which defines the view.
     *
     * This can be either a string or a {@see Query} object.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException
     *
     * @return string the `CREATE VIEW` SQL statement.
     */
    public function createView(string $viewName, Query|string $subQuery): string;

    /**
     * Return command interface instance.
     */
    public function command(): CommandInterface;

    /**
     * Creates a DELETE SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->delete('user', 'status = 0');
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table where the data will be deleted from.
     * @param array|string $condition the condition that will be put in the WHERE part. Please refer to
     * {@see Query::where()} on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the DELETE SQL.
     */
    public function delete(string $table, array|string $condition, array &$params): string;

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
     * Converts an abstract column type into a physical column type.
     *
     * The conversion is done using the type map specified in {@see typeMap}.
     * The following abstract column types are supported (using MySQL as an example to explain the corresponding
     * physical types):
     *
     * - `pk`: an auto-incremental primary key type, will be converted into "int(11) NOT NULL AUTO_INCREMENT PRIMARY
     *    KEY"
     * - `bigpk`: an auto-incremental primary key type, will be converted into "bigint(20) NOT NULL AUTO_INCREMENT
     *    PRIMARY KEY"
     * - `upk`: an unsigned auto-incremental primary key type, will be converted into "int(10) UNSIGNED NOT NULL
     *    AUTO_INCREMENT PRIMARY KEY"
     * - `char`: char type, will be converted into "char(1)"
     * - `string`: string type, will be converted into "varchar(255)"
     * - `text`: a long string type, will be converted into "text"
     * - `smallint`: a small integer type, will be converted into "smallint(6)"
     * - `integer`: integer type, will be converted into "int(11)"
     * - `bigint`: a big integer type, will be converted into "bigint(20)"
     * - `boolean`: boolean type, will be converted into "tinyint(1)"
     * - `float``: float number type, will be converted into "float"
     * - `decimal`: decimal number type, will be converted into "decimal"
     * - `datetime`: datetime type, will be converted into "datetime"
     * - `timestamp`: timestamp type, will be converted into "timestamp"
     * - `time`: time type, will be converted into "time"
     * - `date`: date type, will be converted into "date"
     * - `money`: money type, will be converted into "decimal(19,4)"
     * - `binary`: binary data type, will be converted into "blob"
     *
     * If the abstract type contains two or more parts separated by spaces (e.g. "string NOT NULL"), then only the first
     * part will be converted, and the rest of the parts will be appended to the converted result.
     *
     * For example, 'string NOT NULL' is converted to 'varchar(255) NOT NULL'.
     *
     * For some of the abstract types you can also specify a length or precision constraint by appending it in round
     * brackets directly to the type.
     *
     * For example `string(32)` will be converted into "varchar(32)" on a MySQL database. If the underlying DBMS does
     * not support these kind of constraints for a type it will be ignored.
     *
     * If a type cannot be found in {@see typeMap}, it will be returned without any change.
     *
     * @param ColumnSchemaBuilder|string $type abstract column type.
     *
     * @return string physical column type.
     */
    public function getColumnType(ColumnSchemaBuilder|string $type): string;

    /**
     * Gets object of {@see ExpressionBuilderInterface} that is suitable for $expression.
     *
     * Uses {@see expressionBuilders} array to find a suitable builder class.
     *
     * @param ExpressionInterface $expression
     *
     * @throws InvalidArgumentException when $expression building is not supported by this QueryBuilder.
     *
     * @return ExpressionBuilderInterface|QueryBuilderInterface|string
     *
     * @see expressionBuilders
     */
    public function getExpressionBuilder(
        ExpressionInterface $expression
    ): ExpressionBuilderInterface|QueryBuilderInterface|string;

    /**
     * Creates an INSERT SQL statement.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->insert('user', [
     *     'name' => 'Sam',
     *     'age' => 30,
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|Query $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement. Passing of {@see Query}.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the
     * DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the INSERT SQL.
     */
    public function insert(string $table, Query|array $columns, array &$params = []): string;

    /**
     * Prepares a `VALUES` part for an `INSERT` SQL statement.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array|QueryInterface $columns the column data (name => value) to be inserted into the table or instance of
     * {@see Query} to perform INSERT INTO ... SELECT SQL statement.
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array array of column names, placeholders, values and params.
     */
    public function prepareInsertValues(string $table, QueryInterface|array $columns, array $params = []): array;

    /**
     * Prepares a `SET` parts for an `UPDATE` SQL statement.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @psalm-param array<string, ExpressionInterface|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return array `SET` parts for an `UPDATE` SQL statement (the first array element) and params (the second array
     * element).
     */
    public function prepareUpdateSets(string $table, array $columns, array $params = []): array;

    /**
     * @param string $table
     * @param array|QueryInterface $insertColumns
     * @param array|bool|QueryInterface $updateColumns
     * @param Constraint[] $constraints this parameter receives a matched constraint list.
     * The constraints will be unique by their column names.
     *
     * @throws Exception|JsonException
     *
     * @return array
     */
    public function prepareUpsertColumns(
        string $table,
        QueryInterface|array $insertColumns,
        QueryInterface|bool|array $updateColumns,
        array &$constraints = []
    ): array;

    /**
     * Return quoter interface instance.
     */
    public function quoter(): QuoterInterface;

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

    /**
     * Creates a SQL statement for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $tableName the name of the table whose primary key sequence will be reset.
     * @param array|int|string|null $value the value for the primary key of the next new row inserted. If this is not
     * set, the next new row's primary key will have a value 1.
     *
     * @throws Exception|NotSupportedException if this is not supported by the underlying DBMS.
     *
     * @return string the SQL statement for resetting sequence.
     */
    public function resetSequence(string $tableName, array|int|string|null $value = null): string;

    /**
     * Creates a SELECT EXISTS() SQL statement.
     *
     * @param string $rawSql the sub-query in a raw form to select from.
     *
     * @return string the SELECT EXISTS() SQL statement.
     */
    public function selectExists(string $rawSql): string;

    /**
     * Return schema interface instance.
     */
    public function schema(): SchemaInterface;

    /**
     * Builds a SQL statement for truncating a DB table.
     *
     * @param string $table the table to be truncated. The name will be properly quoted by the method.
     *
     * @return string the SQL statement for truncating a DB table.
     */
    public function truncateTable(string $table): string;

    /**
     * Creates an UPDATE SQL statement.
     *
     * For example,
     *
     * ```php
     * $params = [];
     * $sql = $queryBuilder->update('user', ['status' => 1], 'age > 30', $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table to be updated.
     * @param array $columns the column data (name => value) to be updated.
     * @param array|string $condition the condition that will be put in the WHERE part. Please refer to
     * {@see Query::where()} on how to specify condition.
     * @param array $params the binding parameters that will be modified by this method so that they can be bound to the
     * DB command later.
     *
     * @psalm-param array<string, ExpressionInterface|string> $columns
     *
     * @throws Exception|InvalidArgumentException
     *
     * @return string the UPDATE SQL.
     */
    public function update(string $table, array $columns, array|string $condition, array &$params = []): string;

    /**
     * Creates an SQL statement to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert('pages', [
     *     'name' => 'Front page',
     *     'url' => 'http://example.com/', // url is unique
     *     'visits' => 0,
     * ], [
     *     'visits' => new Expression('visits + 1'),
     * ], $params);
     * ```
     *
     * The method will properly escape the table and column names.
     *
     * @param string $table the table that new rows will be inserted into/updated in.
     * @param array|Query $insertColumns the column data (name => value) to be inserted into the table or
     * instance of {@see Query} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns the column data (name => value) to be updated if they already exist. If `true`
     * is passed, the column data will be updated to match the insert column data. If `false` is passed, no update will
     * be performed if the column data already exists.
     * @param array $params the binding parameters that will be generated by this method. They should be bound to the DB
     * command later.
     *
     * @throws Exception|InvalidConfigException|JsonException|NotSupportedException if this is not supported by the
     * underlying DBMS.
     *
     * @return string the resulting SQL.
     */
    public function upsert(
        string $table,
        Query|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
    ): string;
}
