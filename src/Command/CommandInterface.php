<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Closure;
use JsonException;
use PDOException;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * This interface represents a database command, such as a `SELECT`, `INSERT`, `UPDATE`, or `DELETE` statement.
 *
 * A command object is usually created by calling {@see \Yiisoft\Db\Connection\ConnectionInterface::createCommand()}.
 */
interface CommandInterface
{
    public const QUERY_MODE_EXECUTE = 1;
    public const QUERY_MODE_ROW = 2;
    public const QUERY_MODE_ALL = 4;
    public const QUERY_MODE_COLUMN = 8;
    public const QUERY_MODE_CURSOR = 16;

    /**
     * Creates an SQL command for adding a `CHECK` constraint to an existing table.
     *
     * @param string $name The name of the check constraint.
     * @param string $table The table that the check constraint will be added to.
     * @param string $expression The SQL of the `CHECK` constraint.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function addCheck(string $name, string $table, string $expression): static;

    /**
     * Creates an SQL command for adding a new DB column.
     *
     * @param string $table The table that the new column will be added to.
     * @param string $column The name of the new column.
     * @param string $type The column type. {@see QueryBuilder::getColumnType()} will be called to convert the give
     * column type to the physical one. For example, `string` will be converted as `varchar(255)`, and `string not null`
     * becomes `varchar(255) not null`.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function addColumn(string $table, string $column, string $type): static;

    /**
     * Builds an SQL command for adding comment to column.
     *
     * @param string $table The table whose column is to be commented.
     * @param string $column The name of the column to be commented.
     * @param string $comment The text of the comment to be added.
     *
     * @throws \Exception
     *
     * Note: The method will quote the `table`, `column` and `comment` parameters before using them in the generated
     * SQL.
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): static;

    /**
     * Builds an SQL command for adding comment to the table.
     *
     * @param string $table The table whose column is to be commented.
     * @param string $comment The text of the comment to be added.
     *
     * @throws \Exception
     *
     * Note: The method will quote the `table` and `comment` parameters before using them in the generated SQL.
     */
    public function addCommentOnTable(string $table, string $comment): static;

    /**
     * Creates an SQL command for adding a default value constraint to an existing table.
     *
     * @param string $name The name of the default value constraint.
     * @param string $table The table that the default value constraint will be added to.
     * @param string $column The name of the column to that the constraint will be added on.
     * @param mixed $value Default value.
     *
     * @throws Exception
     * @throws NotSupportedException
     *
     * Note: The method will quote the `name`, `table` and `column` parameters before using them in the generated SQL.
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): static;

    /**
     * Creates an SQL command for adding a foreign key constraint to an existing table.
     *
     * The method will quote the table and column names.
     *
     * @param string $name The name of the foreign key constraint.
     * @param string $table The table that the foreign key constraint will be added to.
     * @param array|string $columns The name of the column to that the constraint will be added on. If there are
     * many columns, separate them with commas.
     * @param string $refTable The table that the foreign key references to.
     * @param array|string $refColumns The name of the column that the foreign key references to. If there are many
     * columns, separate them with commas.
     * @param string|null $delete The ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     * @param string|null $update The ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * Note: The method will quote the `name`, `table`, refTable` parameters before using them in the generated SQL.
     */
    public function addForeignKey(
        string $name,
        string $table,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string $delete = null,
        string $update = null
    ): static;

    /**
     * Creates an SQL command for adding a primary key constraint to an existing table.
     *
     * The method will quote the table and column names.
     *
     * @param string $name The name of the primary key constraint.
     * @param string $table The table that the primary key constraint will be added to.
     * @param array|string $columns The comma separated string or array of columns that the primary key will consist of.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function addPrimaryKey(string $name, string $table, array|string $columns): static;

    /**
     * Creates an SQL command for adding a unique constraint to an existing table.
     *
     * @param string $name The name of the unique constraint.
     * @param string $table The table that the unique constraint will be added to.
     * @param array|string $columns The name of the column to that the constraint will be added on. If there are
     * many columns, separate them with commas.
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function addUnique(string $name, string $table, array|string $columns): static;

    /**
     * Creates an SQL command for changing the definition of a column.
     *
     * @param string $table The table whose column is to be changed.
     * @param string $column The name of the column to be changed.
     * @param string $type The column type. {@see QueryBuilder::getColumnType()} will be called to convert the give
     * column type to the physical one. For example, `string` will be converted as `varchar(255)`, and `string not null`
     * becomes `varchar(255) not null`.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function alterColumn(string $table, string $column, string $type): static;

    /**
     * Creates a batch INSERT command.
     *
     * For example,
     *
     * ```php
     * $connectionInterface->createCommand()->batchInsert(
     *     'user',
     *     ['name', 'age'],
     *     [
     *         ['Tom', 30],
     *         ['Jane', 20],
     *         ['Linda', 25],
     *     ]
     * )->execute();
     * ```
     *
     * The method will escape the column names, and quote the values to be inserted.
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * Also note that the created command isn't executed until {@see execute()} is called.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column names.
     * @param iterable $rows The rows to be batched inserted into the table.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function batchInsert(string $table, array $columns, iterable $rows): static;

    /**
     * Binds a parameter to the SQL statement to be executed.
     *
     * @param int|string $name The parameter identifier. For a prepared statement using named placeholders, this will be
     * a parameter name of the form `:name`. For a prepared statement using question mark placeholders, this will be the
     * 1-indexed position of the parameter.
     * @param mixed $value The PHP variable to bind to the SQL statement parameter (passed by reference).
     * @param int|null $dataType The SQL data type of the parameter. If null, the type is determined by the PHP type of
     * the value.
     * @param int|null $length The length of the data type.
     * @param mixed|null $driverOptions The driver-specific options.
     *
     * @throws Exception
     *
     * @link http://www.php.net/manual/en/function.PDOStatement-bindParam.php
     */
    public function bindParam(
        int|string $name,
        mixed &$value,
        int $dataType = null,
        int $length = null,
        mixed $driverOptions = null
    ): static;

    /**
     * Binds a value to a parameter.
     *
     * @param int|string $name Parameter identifier. For a prepared statement using named placeholders, this will be a
     * parameter name of the form `:name`. For a prepared statement using question mark placeholders, this will be the
     * 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter.
     * @param int|null $dataType The SQL data type of the parameter. If null, the type is determined by the PHP type of
     * the value.
     */
    public function bindValue(int|string $name, mixed $value, int $dataType = null): static;

    /**
     * Binds a list of values to the corresponding parameters.
     *
     * This is similar to {@see bindValue()} except that it binds many values at a time.
     *
     * Note that the SQL data type of each value is determined by its PHP type.
     *
     * @param array|ParamInterface[] $values The values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and an array values the corresponding parameter values,
     * for example, `[':name' => 'John', ':age' => 25]`.
     * By default, the {@see PDO} type of each value is determined by its PHP type. You may explicitly specify the
     * {@see PDO} type by using a {@see Param} class: `new Param(value, type)`, for example,
     * `[':name' => 'John', ':profile' => new Param($profile, PDO::PARAM_LOB)]`.
     */
    public function bindValues(array $values): static;

    /**
     * Cancels the execution of the SQL statement.
     */
    public function cancel(): void;

    /**
     * Builds an SQL command for enabling or disabling integrity check.
     *
     * @param string $schema The schema name of the tables. Defaults to empty string, meaning the current or default
     * schema.
     * @param string $table The table name to be checked.
     * @param bool $check Whether to turn on or off the integrity check.
     *
     * @throws Exception
     * @throws NotSupportedException
     *
     * Note: The method will quote the `table` parameters before using them in the generated SQL.
     */
    public function checkIntegrity(string $schema, string $table, bool $check = true): static;

    /**
     * Creates an SQL command for creating a new index.
     *
     * @param string $name The name of the index.
     * @param string $table The table that the new index will be created for.
     * @param array|string $columns The column(s) that should be included in the index. If there are many columns.
     * Please separate them by commas.
     * @param string|null $indexType The type of index-supported DBMS - for example: `UNIQUE`, `FULLTEXT`, `SPATIAL`,
     * `BITMAP` or null as default.
     * @param string|null $indexMethod The setting index organization method (with `USING`, not all DBMS).
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * Note: The method will quote the `name`, `table`, and `column` parameters before using them in the generated SQL.
     */
    public function createIndex(
        string $name,
        string $table,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): static;

    /**
     * Creates an SQL command for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The method {@see QueryBuilder::getColumnType()} will be called to convert the abstract column types to physical
     * ones. For example, `string` will be converted as `varchar(255)`, and `string not null` becomes
     * `varchar(255) not null`.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly inserted
     * into the generated SQL.
     *
     * @param string $table The name of the table to be created.
     * @param array $columns The columns (name => definition) in the new table.
     * @param string|null $options More SQL fragments that will be appended to the generated SQL.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     */
    public function createTable(string $table, array $columns, string $options = null): static;

    /**
     * Creates a SQL View.
     *
     * @param string $viewName The name of the view to be created.
     * @param QueryInterface|string $subQuery The select statement which defines the view. This can be either a string
     * or a {@see QueryInterface}.
     *
     * @throws InvalidArgumentException
     * @throws Exception
     *
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): static;

    /**
     * Creates a DELETE command.
     *
     * For example,
     *
     * ```php
     * $connectionInterface->createCommand()->delete('user', 'status = 0')->execute();
     * ```
     *
     * or with using parameter binding for the condition:
     *
     * ```php
     * $status = 0;
     * $connectionInterface->createCommand()->delete('user', 'status = :status', [':status' => $status])->execute();
     * ```
     *
     * The method will escape the table and column names.
     *
     * Note that the created command isn't executed until {@see execute()} is called.
     *
     * @param string $table The table where the data will be deleted from.
     * @param array|string $condition The condition that will be put in the WHERE part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function delete(string $table, array|string $condition = '', array $params = []): static;

    /**
     * Creates an SQL command for dropping a check constraint.
     *
     * @param string $name The name of the check constraint to be dropped.
     * @param string $table The table whose check constraint is to be dropped.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropCheck(string $name, string $table): static;

    /**
     * Creates an SQL command for dropping a DB column.
     *
     * @param string $table The table whose column is to be dropped.
     * @param string $column The name of the column to be dropped.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function dropColumn(string $table, string $column): static;

    /**
     * Builds an SQL command for dropping comment from column.
     *
     * @param string $table The table whose column is to be commented.
     * @param string $column The name of the column to be commented.
     *
     * Note: The method will quote the `table` and `column` parameters before using them in the generated SQL.
     */
    public function dropCommentFromColumn(string $table, string $column): static;

    /**
     * Builds an SQL command for dropping comment from the table.
     *
     * @param string $table The table whose column is to be commented.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function dropCommentFromTable(string $table): static;

    /**
     * Creates an SQL command for dropping a default value constraint.
     *
     * @param string $name The name of the default value constraint to be dropped.
     * @param string $table The table whose default value constraint is to be dropped.
     *
     * @throws Exception
     * @throws NotSupportedException
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropDefaultValue(string $name, string $table): static;

    /**
     * Creates an SQL command for dropping a foreign key constraint.
     *
     * @param string $name The name of the foreign key constraint to be dropped.
     * @param string $table The table whose foreign is to be dropped.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropForeignKey(string $name, string $table): static;

    /**
     * Creates an SQL command for dropping an index.
     *
     * @param string $name The name of the index to be dropped.
     * @param string $table The table whose index is to be dropped.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropIndex(string $name, string $table): static;

    /**
     * Creates an SQL command for removing a primary key constraint to an existing table.
     *
     * @param string $name The name of the primary key constraint to be removed.
     * @param string $table The table that the primary key constraint will be removed from.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropPrimaryKey(string $name, string $table): static;

    /**
     * Creates an SQL command for dropping a DB table.
     *
     * @param string $table The table to be dropped.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function dropTable(string $table): static;

    /**
     * Creates an SQL command for dropping a unique constraint.
     *
     * @param string $name The name of the unique constraint to be dropped.
     * @param string $table The table whose unique constraint is to be dropped.
     *
     * Note: The method will quote the `name` and `table` parameters before using them in the generated SQL.
     */
    public function dropUnique(string $name, string $table): static;

    /**
     * Drops a SQL View.
     *
     * @param string $viewName The name of the view to be dropped.
     *
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function dropView(string $viewName): static;

    /**
     * Executes the SQL statement.
     *
     * This method should only be used for executing a non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE`
     * SQLs. No result set will be returned.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return int The number of rows affected by the execution.
     */
    public function execute(): int;

    /**
     * Return the params used in the last query.
     *
     * @param bool $asValues By default, returned array of pair name => value, if true - be returned array of
     * ParamInterface.
     *
     * @psalm-return array|ParamInterface[]
     *
     * @return array The params used in the last query.
     */
    public function getParams(bool $asValues = true): array;

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in {@see sql}.
     *
     * Note that the return value of this method should mainly be used for logging.
     *
     * It's likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     *
     * @throws \Exception
     *
     * @return string The raw SQL with parameter values inserted into the corresponding placeholders in {@see sql}.
     */
    public function getRawSql(): string;

    /**
     * Returns the SQL statement for this command.
     *
     * @return string The SQL statement to be executed.
     */
    public function getSql(): string;

    /**
     * Creates an INSERT command.
     *
     * For example,
     *
     * ```php
     * $connectionInterface->createCommand()->insert(
     *     'user',
     *     [
     *         'name' => 'Sam',
     *         'age' => 30,
     *     ]
     * )->execute();
     * ```
     *
     * The method will escape the column names, and bind the values to be inserted.
     *
     * Note that the created command isn't executed until {@see execute()} is called.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array|QueryInterface $columns The column data (name => value) to be inserted into the table or instance of
     * {@see QueryInterface} to perform INSERT INTO ... SELECT SQL statement.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     */
    public function insert(string $table, QueryInterface|array $columns): static;

    /**
     * Attention! Please use function only as a last resort. The feature will be refactored in future releases.
     * Executes the INSERT command, returning primary key inserted values.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column data (name => value) to be inserted into the table.
     *
     * @throws Exception
     * @throws InvalidCallException
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array|false The primary key values or false if the command fails.
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     */
    public function insertWithReturningPks(string $table, array $columns): bool|array;

    /**
     * Prepares the SQL statement to be executed.
     *
     * For complex SQL statement that's to be executed many times, this may improve performance.
     *
     * For SQL statement with binding parameters, this method is invoked automatically.
     *
     * @param bool|null $forRead Whether this method is called for a read query. If null, it means the SQL statement
     * should be used to deciding whether it's to read or write.
     *
     * @throws Exception If there is any DB error.
     * @throws InvalidConfigException
     * @throws PDOException
     */
    public function prepare(bool $forRead = null): void;

    /**
     * Executes the SQL statement and returns a query result.
     *
     * This method is for executing an SQL query that returns result set, such as `SELECT`.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return DataReaderInterface The reader object for fetching the query result.
     */
    public function query(): DataReaderInterface;

    /**
     * Executes the SQL statement and returns ALL rows at once.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return array All rows of the query result. Each array element is an array representing a row of data.
     * Empty array is returned if the query results in nothing.
     */
    public function queryAll(): array;

    /**
     * Create query builder instance.
     *
     * @return QueryBuilderInterface The query builder instance.
     */
    public function queryBuilder(): QueryBuilderInterface;

    /**
     * Execute the SQL statement and returns the first column of the result.
     *
     * This method is best used when only the first column of a result (that's the first element in each row) is needed
     * for a query.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return array The first column of the query result. Empty array is returned if the query results in nothing.
     */
    public function queryColumn(): array;

    /**
     * Executes the SQL statement and returns the first row of the result.
     *
     * This method is best used when only the first row of a result is needed for a query.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return array|null The first row (in terms of an array) of the query result. Null is returned if the query
     * results in nothing.
     */
    public function queryOne(): array|null;

    /**
     * Execute the SQL statement and returns the value of the first column in the first row of data.
     *
     * This method is best used when only a single value is needed for a query.
     *
     * @throws Exception
     * @throws Throwable If execution failed.
     *
     * @return false|float|int|string|null The value of the first column in the first row of the query result.
     * False is returned if there is no value.
     *
     * @psalm-return null|scalar
     */
    public function queryScalar(): bool|string|null|int|float;

    /**
     * Creates an SQL command for renaming a column.
     *
     * @param string $table The table whose column is to be renamed.
     * @param string $oldName The old name of the column.
     * @param string $newName The new name of the column.
     *
     * Note: The method will quote the `table`, `oldName` and `newName` parameter before using it in the generated SQL.
     */
    public function renameColumn(string $table, string $oldName, string $newName): static;

    /**
     * Creates an SQL command for renaming a DB table.
     *
     * @param string $table The table to be renamed.
     * @param string $newName The new table name.
     *
     * Note: The method will quote the `table` and `newName` parameter before using it in the generated SQL.
     */
    public function renameTable(string $table, string $newName): static;

    /**
     * Executes a db command resetting the sequence value of a table's primary key.
     *
     * Reason for execute is that some databases (Oracle) need several queries to do so.
     *
     * The sequence is reset such that the primary key of the next new row inserted will have the specified value or the
     * maximum existing value +1.
     *
     * @param string $table The name of the table whose primary key sequence is reset.
     * @param int|string|null $value The value for the primary key of the next new row inserted. If this isn't set, the
     * next new row's primary key will have the maximum existing value +1.
     *
     * @throws Exception
     * @throws NotSupportedException
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function resetSequence(string $table, int|string $value = null): static;

    /**
     * Sets the profiler instance.
     *
     * @param ProfilerInterface|null $profiler The profiler instance.
     */
    public function setProfiler(ProfilerInterface|null $profiler): void;

    /**
     * Specifies the SQL statement to be executed.
     *
     * The SQL statement won't be modified in any way.
     *
     * The earlier SQL (if any) will be discarded, and {@see Param} will be cleared as well.
     *
     * See {@see reset()} for details.
     *
     * @param string $sql The SQL statement to be set.
     *
     * {@see reset()}
     * {@see cancel()}
     */
    public function setRawSql(string $sql): static;

    /**
     * Sets a Closure (e.g. anonymous function) that's called when {@see Exception} is thrown when executing the
     * command. The signature of the Closure should be:.
     *
     * ```php
     * function (Exceptions $e, $attempt)
     * {
     *     // return true or false (whether to retry the command or rethrow $e)
     * }
     * ```
     *
     * The Closure will receive a database exception thrown and a current try (to execute the command) number
     * starting from 1.
     *
     * @param Closure|null $handler A PHP callback to handle database exceptions.
     */
    public function setRetryHandler(Closure|null $handler): static;

    /**
     * Specifies the SQL statement to be executed. The SQL statement will be quoted using
     * {@see ConnectionInterface::quoteSql()}.
     *
     * The previous SQL (if any) will be discarded, and {@see Param} will be cleared as well. See {@see reset()} for
     * details.
     *
     * @param string $sql The SQL statement to be set.
     *
     * {@see reset()}
     * {@see cancel()}
     */
    public function setSql(string $sql): static;

    /**
     * Creates an SQL command for truncating a DB table.
     *
     * @param string $table The table to be truncated.
     *
     * Note: The method will quote the `table` parameter before using it in the generated SQL.
     */
    public function truncateTable(string $table): static;

    /**
     * Creates an UPDATE command.
     *
     * For example,
     *
     * ```php
     * $connectionInterface->createCommand()->update('user', ['status' => 1], 'age > 30')->execute();
     * ```
     *
     * or with using parameter binding for the condition:
     *
     * ```php
     * $minAge = 30;
     * $connectionInterface->createCommand()->update(
     *     'user',
     *     ['status' => 1],
     *     'age > :minAge',
     *     [':minAge' => $minAge]
     * )->execute();
     * ```
     *
     * The method will escape the column names and bind the values to be updated.
     *
     * Note that the created command isn't executed until {@see execute()} is called.
     *
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * Note: The method will quote the `table` and `columns` parameter before using it in the generated SQL.
     */
    public function update(string $table, array $columns, array|string $condition = '', array $params = []): static;

    /**
     * Creates a command to insert rows into a database table if they don't already exist (matching unique constraints)
     * or update them if they do.
     *
     * For example,
     *
     * ```php
     * $sql = $queryBuilder->upsert(
     *     'pages',
     *     [
     *         'name' => 'Front page',
     *         'url' => 'http://example.com/', // url is unique
     *         'visits' => 0,
     *     ],
     *     [
     *         'visits' => new \Yiisoft\Db\Expression\Expression('visits + 1'),
     *     ],
     *     $params,
     * );
     * ```
     *
     * The method will escape the table and column names.
     *
     * @param string $table The table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns The column data (name => value) to be inserted into the table or
     * instance of {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns The column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exist.
     * @param array $params The parameters to be bound to the command.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * Note: The method will quote the `table` and `insertColumns`, `updateColumns` parameters before using it in the
     * generated SQL.
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns = true,
        array $params = []
    ): static;
}
