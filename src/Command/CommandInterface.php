<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\Data\DataReaderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

interface CommandInterface
{
    public const QUERY_MODE_NONE = 0;
    public const QUERY_MODE_ROW = 1;
    public const QUERY_MODE_ALL = 2;
    public const QUERY_MODE_CURSOR = 3;
    public const QUERY_MODE_COLUMN = 7;

    /**
     * Creates a SQL command for adding a check constraint to an existing table.
     *
     * @param string $name The name of the check constraint. The name will be properly quoted by the method.
     * @param string $table The table that the check constraint will be added to. The name will be properly quoted by
     * the method.
     * @param string $expression The SQL of the `CHECK` constraint.
     *
     * @return static
     */
    public function addCheck(string $name, string $table, string $expression): static;

    /**
     * Creates a SQL command for adding a new DB column.
     *
     * @param string $table The table that the new column will be added to. The table name will be properly quoted by
     * the method.
     * @param string $column The name of the new column. The name will be properly quoted by the method.
     * @param string $type The column type. {@see QueryBuilder::getColumnType()} will be called to convert the give
     * column type to the physical one. For example, `string` will be converted as `varchar(255)`, and `string not null`
     * becomes `varchar(255) not null`.
     *
     * @return static
     */
    public function addColumn(string $table, string $column, string $type): static;

    /**
     * Builds a SQL command for adding comment to column.
     *
     * @param string $table The table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column The name of the column to be commented. The column name will be properly quoted by the
     * method.
     * @param string $comment The text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @return static
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): static;

    /**
     * Builds a SQL command for adding comment to table.
     *
     * @param string $table The table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $comment The text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @return static
     */
    public function addCommentOnTable(string $table, string $comment): static;

    /**
     * Creates a SQL command for adding a default value constraint to an existing table.
     *
     * @param string $name The name of the default value constraint. The name will be properly quoted by the method.
     * @param string $table The table that the default value constraint will be added to. The name will be properly
     * quoted by the method.
     * @param string $column The name of the column to that the constraint will be added on. The name will be properly
     * quoted by the method.
     * @param mixed $value Default value.
     *
     * @return static
     */
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): static;

    /**
     * Creates a SQL command for adding a foreign key constraint to an existing table.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $name The name of the foreign key constraint.
     * @param string $table The table that the foreign key constraint will be added to.
     * @param array|string $columns The name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas.
     * @param string $refTable The table that the foreign key references to.
     * @param array|string $refColumns The name of the column that the foreign key references to. If there are multiple
     * columns, separate them with commas.
     * @param string|null $delete The ON DELETE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     * @param string|null $update The ON UPDATE option. Most DBMS support these options: RESTRICT, CASCADE, NO ACTION,
     * SET DEFAULT, SET NULL.
     *
     * @return static
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
     * Creates a SQL command for adding a primary key constraint to an existing table.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $name The name of the primary key constraint.
     * @param string $table The table that the primary key constraint will be added to.
     * @param array|string $columns Comma separated string or array of columns that the primary key will consist of.
     *
     * @return static
     */
    public function addPrimaryKey(string $name, string $table, array|string $columns): static;

    /**
     * Creates a SQL command for adding a unique constraint to an existing table.
     *
     * @param string $name The name of the unique constraint. The name will be properly quoted by the method.
     * @param string $table The table that the unique constraint will be added to. The name will be properly quoted by
     * the method.
     * @param array|string $columns The name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function addUnique(string $name, string $table, array|string $columns): static;

    /**
     * Creates a SQL command for changing the definition of a column.
     *
     * @param string $table The table whose column is to be changed. The table name will be properly quoted by the
     * method.
     * @param string $column The name of the column to be changed. The name will be properly quoted by the method.
     * @param string $type The column type. {@see QueryBuilder::getColumnType()} will be called to convert the give
     * column type to the physical one. For example, `string` will be converted as `varchar(255)`, and `string not null`
     * becomes `varchar(255) not null`.
     *
     * @return static
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
     * The method will properly escape the column names, and quote the values to be inserted.
     *
     * Note that the values in each row must match the corresponding column names.
     *
     * Also note that the created command is not executed until {@see execute()} is called.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column names.
     * @param iterable $rows The rows to be batched inserted into the table.
     *
     * @return static
     */
    public function batchInsert(string $table, array $columns, iterable $rows): static;

    /**
     * Binds a parameter to the SQL statement to be executed.
     *
     * @param int|string $name Parameter identifier. For a prepared statement using named placeholders, this will be a
     * parameter name of the form `:name`. For a prepared statement using question mark placeholders, this will be the
     * 1-indexed position of the parameter.
     * @param mixed $value The PHP variable to bind to the SQL statement parameter (passed by reference).
     * @param int|null $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the
     * value.
     * @param int|null $length Length of the data type.
     * @param mixed|null $driverOptions The driver-specific options.
     *
     * @throws Exception
     *
     * @return static The current command being executed.
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
     * @param int|null $dataType SQL data type of the parameter. If null, the type is determined by the PHP type of the
     * value.
     *
     * @return static The current command being executed.
     */
    public function bindValue(int|string $name, mixed $value, int $dataType = null): static;

    /**
     * Binds a list of values to the corresponding parameters.
     *
     * This is similar to {@see bindValue()} except that it binds multiple values at a time.
     *
     * Note that the SQL data type of each value is determined by its PHP type.
     *
     * @param array|ParamInterface[] $values The values to be bound. This must be given in terms of an associative
     * array with array keys being the parameter names, and array values the corresponding parameter values,
     * e.g. `[':name' => 'John', ':age' => 25]`.
     * By default, the PDO type of each value is determined  by its PHP type. You may explicitly specify the PDO type by
     * using a {@see Param} class: `new Param(value, type)`,
     * e.g. `[':name' => 'John', ':profile' => new Param($profile, \PDO::PARAM_LOB)]`.
     *
     * @return static The current command being executed.
     */
    public function bindValues(array $values): static;

    /**
     * Enables query cache for this command.
     *
     * @param int|null $duration The number of seconds that query result of this command can remain valid in the cache.
     * If this is not set, the value of {@see QueryCache::getDuration()} will be used instead.
     * Use 0 to indicate that the cached data will never expire.
     * @param Dependency|null $dependency The cache dependency associated with the cached query result.
     *
     * @return static
     */
    public function cache(int $duration = null, Dependency $dependency = null): static;

    /**
     * Cancels the execution of the SQL statement.
     */
    public function cancel(): void;

    /**
     * Builds a SQL command for enabling or disabling integrity check.
     *
     * @param string $schema The schema name of the tables. Defaults to empty string, meaning the current or default
     * schema.
     * @param string $table The table name.
     * @param bool $check Whether to turn on or off the integrity check.
     *
     * @return static
     */
    public function checkIntegrity(string $schema, string $table, bool $check = true): static;

    /**
     * Create query builder instance.
     */
    public function queryBuilder(): QueryBuilderInterface;

    /**
     * Creates a SQL command for creating a new index.
     *
     * @param string $name The name of the index. The name will be properly quoted by the method.
     * @param string $table The table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param array|string $columns The column(s) that should be included in the index. If there are multiple columns,
     * please separate them by commas. The column names will be properly quoted by the method.
     * @param bool $unique Whether to add UNIQUE constraint on the created index.
     *
     * @return static
     */
    public function createIndex(
        string $name,
        string $table,
        array|string $columns,
        string $indexType = null,
        string $indexMethod = null
    ): static;

    /**
     * Creates a SQL command for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be properly quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The method {@see QueryBuilder::getColumnType()} will be called to convert the abstract column types to physical
     * ones. For example, `string` will be converted as `varchar(255)`, and `string not null` becomes
     * `varchar(255) not null`.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly inserted
     * into the generated SQL.
     *
     * @param string $table The name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns The columns (name => definition) in the new table.
     * @param string|null $options Additional SQL fragments that will be appended to the generated SQL.
     *
     * @return static
     */
    public function createTable(string $table, array $columns, string $options = null): static;

    /**
     * Creates a SQL View.
     *
     * @param string $viewName The name of the view to be created.
     * @param QueryInterface|string $subquery The select statement which defines the view. This can be either a string
     * or a {@see QueryInterface}.
     *
     * @return static
     */
    public function createView(string $viewName, QueryInterface|string $subquery): static;

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
     * The method will properly escape the table and column names.
     *
     * Note that the created command is not executed until {@see execute()} is called.
     *
     * @param string $table The table where the data will be deleted from.
     * @param array|string $condition The condition that will be put in the WHERE part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     *
     * @return static
     */
    public function delete(string $table, array|string $condition = '', array $params = []): static;

    /**
     * Creates a SQL command for dropping a check constraint.
     *
     * @param string $name The name of the check constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table The table whose check constraint is to be dropped. The name will be properly quoted by the
     * method.
     *
     * @return static
     */
    public function dropCheck(string $name, string $table): static;

    /**
     * Creates a SQL command for dropping a DB column.
     *
     * @param string $table The table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column The name of the column to be dropped. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function dropColumn(string $table, string $column): static;

    /**
     * Builds a SQL command for dropping comment from column.
     *
     * @param string $table The table whose column is to be commented. The table name will be properly quoted by the
     * method.
     * @param string $column The name of the column to be commented. The column name will be properly quoted by the
     * method.
     *
     * @return static
     */
    public function dropCommentFromColumn(string $table, string $column): static;

    /**
     * Builds a SQL command for dropping comment from table.
     *
     * @param string $table The table whose column is to be commented. The table name will be properly quoted by the
     * method.
     *
     * @return static
     */
    public function dropCommentFromTable(string $table): static;

    /**
     * Creates a SQL command for dropping a default value constraint.
     *
     * @param string $name The name of the default value constraint to be dropped. The name will be properly quoted by
     * the method.
     * @param string $table The table whose default value constraint is to be dropped. The name will be properly quoted
     * by the method.
     *
     * @return static
     */
    public function dropDefaultValue(string $name, string $table): static;

    /**
     * Creates a SQL command for dropping a foreign key constraint.
     *
     * @param string $name The name of the foreign key constraint to be dropped. The name will be properly quoted by
     * the method.
     * @param string $table The table whose foreign is to be dropped. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function dropForeignKey(string $name, string $table): static;

    /**
     * Creates a SQL command for dropping an index.
     *
     * @param string $name The name of the index to be dropped. The name will be properly quoted by the method.
     * @param string $table The table whose index is to be dropped. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function dropIndex(string $name, string $table): static;

    /**
     * Creates a SQL command for removing a primary key constraint to an existing table.
     *
     * @param string $name The name of the primary key constraint to be removed.
     * @param string $table The table that the primary key constraint will be removed from.
     *
     * @return static
     */
    public function dropPrimaryKey(string $name, string $table): static;

    /**
     * Creates a SQL command for dropping a DB table.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function dropTable(string $table): static;

    /**
     * Creates a SQL command for dropping a unique constraint.
     *
     * @param string $name The name of the unique constraint to be dropped. The name will be properly quoted by the
     * method.
     * @param string $table The table whose unique constraint is to be dropped. The name will be properly quoted by
     * the method.
     *
     * @return static
     */
    public function dropUnique(string $name, string $table): static;

    /**
     * Drops a SQL View.
     *
     * @param string $viewName The name of the view to be dropped.
     *
     * @return static
     */
    public function dropView(string $viewName): static;

    /**
     * Executes the SQL statement.
     *
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     * No result set will be returned.
     *
     * @throws Exception|Throwable execution failed.
     *
     * @return int Number of rows affected by the execution.
     */
    public function execute(): int;

    /**
     * Executes a db command resetting the sequence value of a table's primary key.
     *
     * Reason for execute is that some databases (Oracle) need several queries to do so.
     *
     * The sequence is reset such that the primary key of the next new row inserted will have the specified value or the
     * maximum existing value +1.
     *
     * @param string $table The name of the table whose primary key sequence is reset.
     * @param array|int|string|null $value The value for the primary key of the next new row inserted. If this is not
     * set, the next new row's primary key will have the maximum existing value +1.
     *
     * @return static
     */
    public function executeResetSequence(string $table, array|int|string $value = null): static;

    /**
     * Return the params used in the last query.
     *
     * @param bool $asParams - by default - returned array of pair name => value
     * if true - be returned array of ParamInterface
     *
     * @psalm-return array|ParamInterface[]
     *
     * @return array
     */
    public function getParams(bool $asValues = true): array;

    /**
     * Returns the raw SQL by inserting parameter values into the corresponding placeholders in {@see sql}.
     *
     * Note that the return value of this method should mainly be used for logging purpose.
     *
     * It is likely that this method returns an invalid SQL due to improper replacement of parameter placeholders.
     *
     * @return string The raw SQL with parameter values inserted into the corresponding placeholders in {@see sql}.
     */
    public function getRawSql(): string;

    /**
     * Returns the SQL statement for this command.
     *
     * @return string the SQL statement to be executed.
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
     * The method will properly escape the column names, and bind the values to be inserted.
     *
     * Note that the created command is not executed until {@see execute()} is called.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array|QueryInterface $columns The column data (name => value) to be inserted into the table or instance of
     * {@see QueryInterface} to perform INSERT INTO ... SELECT SQL statement.
     *
     * @return static
     */
    public function insert(string $table, QueryInterface|array $columns): static;

    /**
     * Executes the INSERT command, returning primary key inserted values.
     *
     * @param string $table the table that new rows will be inserted into.
     * @param array $columns the column data (name => value) to be inserted into the table.
     *
     * @throws Exception|InvalidCallException|InvalidConfigException|Throwable
     *
     * @return array|false primary key values or false if the command fails.
     */
    public function insertEx(string $table, array $columns): bool|array;

    /**
     * Disables query cache for this command.
     *
     * @return static
     */
    public function noCache(): static;

    /**
     * Prepares the SQL statement to be executed.
     *
     * For complex SQL statement that is to be executed multiple times, this may improve performance. For SQL statement
     * with binding parameters, this method is invoked automatically.
     *
     * @param bool|null $forRead Whether this method is called for a read query. If null, it means the SQL statement
     * should be used to determine whether it is for read or write.
     *
     * @throws Exception If there is any DB error.
     */
    public function prepare(bool $forRead = null): void;

    /**
     * Executes the SQL statement and returns query result.
     *
     * This method is for executing a SQL query that returns result set, such as `SELECT`.
     *
     * @throws Exception|Throwable execution failed.
     *
     * @return DataReaderInterface The reader object for fetching the query result.
     */
    public function query(): DataReaderInterface;

    /**
     * Executes the SQL statement and returns ALL rows at once.
     *
     * @throws Exception|Throwable Execution failed.
     *
     * @return array All rows of the query result. Each array element is an array representing a row of data.
     * Empty array is returned if the query results in nothing.
     */
    public function queryAll(): array;

    /**
     * Executes the SQL statement and returns the first column of the result.
     *
     * This method is best used when only the first column of result (i.e. the first element in each row) is needed for
     * a query.
     *
     * @throws Exception|Throwable Execution failed.
     *
     * @return array The first column of the query result. Empty array is returned if the query results in nothing.
     */
    public function queryColumn(): array;

    /**
     * Executes the SQL statement and returns the first row of the result.
     *
     * This method is best used when only the first row of result is needed for a query.
     *
     * @throws Exception|Throwable Execution failed.
     *
     * @return array|null The first row (in terms of an array) of the query result. Null is returned if the query
     * results in nothing.
     */
    public function queryOne(): array|null;

    /**
     * Executes the SQL statement and returns the value of the first column in the first row of data.
     *
     * This method is best used when only a single value is needed for a query.
     *
     * @throws Exception|Throwable Execution failed.
     *
     * @return false|float|int|string|null The value of the first column in the first row of the query result.
     * False is returned if there is no value.
     */
    public function queryScalar(): bool|string|null|int|float;

    /**
     * Creates a SQL command for renaming a column.
     *
     * @param string $table The table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $oldName The old name of the column. The name will be properly quoted by the method.
     * @param string $newName The new name of the column. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function renameColumn(string $table, string $oldName, string $newName): static;

    /**
     * Creates a SQL command for renaming a DB table.
     *
     * @param string $table The table to be renamed. The name will be properly quoted by the method.
     * @param string $newName The new table name. The name will be properly quoted by the method.
     *
     * @return static
     */
    public function renameTable(string $table, string $newName): static;

    /**
     * Creates a SQL command for resetting the sequence value of a table's primary key.
     *
     * The sequence will be reset such that the primary key of the next new row inserted will have the specified value
     * or 1.
     *
     * @param string $table The name of the table whose primary key sequence will be reset.
     * @param array|int|string|null $value The value for the primary key of the next new row inserted. If this is not
     * set, the next new row's primary key will have a value 1.
     *
     * @return static
     */
    public function resetSequence(string $table, array|int|string $value = null): static;

    /**
     * Specifies the SQL statement to be executed. The SQL statement will not be modified in any way.
     *
     * The previous SQL (if any) will be discarded, and {@see Param} will be cleared as well. See {@see reset()}
     * for details.
     *
     * @param string $sql The SQL statement to be set.
     *
     * @return static
     *
     * {@see reset()}
     * {@see cancel()}
     */
    public function setRawSql(string $sql): static;

    /**
     * Specifies the SQL statement to be executed. The SQL statement will be quoted using
     * {@see ConnectionInterface::quoteSql()}.
     *
     * The previous SQL (if any) will be discarded, and {@see Param} will be cleared as well. See {@see reset()} for
     * details.
     *
     * @param string $sql The SQL statement to be set.
     *
     * @return static
     *
     * {@see reset()}
     * {@see cancel()}
     */
    public function setSql(string $sql): static;

    /**
     * Creates a SQL command for truncating a DB table.
     *
     * @param string $table The table to be truncated. The name will be properly quoted by the method.
     *
     * @return static
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
     * The method will properly escape the column names and bind the values to be updated.
     *
     * Note that the created command is not executed until {@see execute()} is called.
     *
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param array|string $condition The condition that will be put in the WHERE part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the command.
     *
     * @return static
     */
    public function update(string $table, array $columns, array|string $condition = '', array $params = []): static;

    /**
     * Creates a command to insert rows into a database table if they do not already exist (matching unique constraints)
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
     * The method will properly escape the table and column names.
     *
     * @param string $table The table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns The column data (name => value) to be inserted into the table or
     * instance of {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns The column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params The parameters to be bound to the command.
     *
     * @return static
     */
    public function upsert(
        string $table,
        QueryInterface|array $insertColumns,
        bool|array $updateColumns = true,
        array $params = []
    ): static;
}
