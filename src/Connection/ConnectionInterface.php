<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Closure;
use Throwable;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Query\QueryPartsInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * This interface provides methods for establishing a connection to a database, executing SQL statements, and performing
 * other tasks related to interacting with a database.
 *
 * It allows you to access and manipulate databases in a database-agnostic way, so you can write code that works with
 * different database systems without having to worry about the specific details of each one.
 *
 * @psalm-type ParamsType = array<non-empty-string, mixed>|list<mixed>
 * @psalm-import-type SelectValue from QueryPartsInterface
 */
interface ConnectionInterface
{
    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see TransactionInterface::begin()} for details.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     *
     * @return TransactionInterface The transaction initiated.
     */
    public function beginTransaction(?string $isolationLevel = null): TransactionInterface;

    /**
     * Create a batch query result instance.
     *
     * @param QueryInterface $query The query to execute.
     *
     * @return BatchQueryResultInterface The batch query result instance.
     */
    public function createBatchQueryResult(QueryInterface $query): BatchQueryResultInterface;

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql The SQL statement to execute.
     * @param array $params The parameters to bind to the SQL statement.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return CommandInterface The database command instance.
     *
     * @psalm-param ParamsType $params
     */
    public function createCommand(?string $sql = null, array $params = []): CommandInterface;

    /**
     * Creates a query instance.
     *
     * @return QueryInterface The query instance.
     */
    public function createQuery(): QueryInterface;

    /**
     * Create a transaction instance.
     *
     * @return TransactionInterface The transaction instance.
     */
    public function createTransaction(): TransactionInterface;

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void;

    /**
     * Returns the column factory for creating column instances.
     */
    public function getColumnFactory(): ColumnFactoryInterface;

    /**
     * Returns the name of the DB driver for the current `dsn`.
     *
     * Use this method for information only.
     *
     * @return string The name of the DB driver for the current `dsn`.
     */
    public function getDriverName(): string;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $sequenceName The name of the sequence object (required by some DBMS).
     *
     * @throws Exception
     * @throws InvalidCallException
     *
     * @return string The row ID of the last row inserted, or the last value retrieved from the sequence object.
     */
    public function getLastInsertId(?string $sequenceName = null): string;

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilderInterface The query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Return quoter helper for current DB connection.
     *
     * @return QuoterInterface The quoter helper for the current DB connection.
     */
    public function getQuoter(): QuoterInterface;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return SchemaInterface The schema information for the database opened by this connection.
     */
    public function getSchema(): SchemaInterface;

    /**
     * Returns {@see ServerInfoInterface} instance that provides information about the database server.
     */
    public function getServerInfo(): ServerInfoInterface;

    /**
     * Return table prefix for current DB connection.
     *
     * @return string The table prefix for the current DB connection.
     */
    public function getTablePrefix(): string;

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name The table name.
     * @param bool $refresh Whether to reload the table schema even if it's found in the cache.
     *
     * @return TableSchemaInterface|null The schema information for the named table. Null if the named table doesn't
     * exist.
     */
    public function getTableSchema(string $name, bool $refresh = false): TableSchemaInterface|null;

    /**
     * Returns the currently active transaction.
     *
     * @return TransactionInterface|null The currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): TransactionInterface|null;

    /**
     * Returns a value indicating whether the DB connection is active.
     *
     * @return bool Whether the DB connection is active.
     */
    public function isActive(): bool;

    /**
     * @return bool Whether this DBMS supports [savepoint](https://en.wikipedia.org/wiki/Savepoint).
     */
    public function isSavepointEnabled(): bool;

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection is active.
     *
     * @throws Exception If connection fails.
     * @throws InvalidConfigException If a connection can't be established because of incomplete configuration.
     */
    public function open(): void;

    /**
     * Quotes a value for use in a query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return mixed The quoted string.
     */
    public function quoteValue(mixed $value): mixed;

    /**
     * Whether to enable [savepoint](https://en.wikipedia.org/wiki/Savepoint).
     *
     * Note that if the underlying DBMS doesn't support savepoint, setting this property to be true will have no effect.
     *
     * @param bool $value Whether to enable savepoint.
     */
    public function setEnableSavepoint(bool $value): void;

    /**
     * Creates a new {@see Query} instance with the specified columns to be selected.
     *
     * @param array|ExpressionInterface|scalar $columns The columns to be selected.
     * Columns can be specified in either a string (for example `id, name`) or an array (such as `['id', 'name']`).
     * Columns can be prefixed with table names (such as `user.id`) and/or contain column aliases
     * (for example `user.id AS user_id`).
     * The method will automatically quote the column names unless a column has some parenthesis (which means the
     * column has a DB expression).
     * A DB expression may also be passed in form of an {@see ExpressionInterface} object.
     * Note that if you are selecting an expression like `CONCAT(first_name, ' ', last_name)`, you should use an array
     * to specify the columns. Otherwise, the expression may be incorrectly split into several parts.
     * When the columns are specified as an array, you may also use array keys as the column aliases (if a column
     * doesn't need alias, don't use a string key).
     * @param string|null $option More option that should be appended to the 'SELECT' keyword. For example, in MySQL,
     * the option 'SQL_CALC_FOUND_ROWS' can be used.
     *
     * @psalm-param SelectValue|scalar|ExpressionInterface $columns
     */
    public function select(
        array|bool|float|int|string|ExpressionInterface $columns = [],
        ?string $option = null,
    ): QueryInterface;

    /**
     * The common prefix or suffix for table names.
     * If a table name is `{{%TableName}}`, then the percentage
     * character `%` will be replaced with this property value.
     * For example, `{{%post}}` becomes `{{tbl_post}}`.
     *
     * @param string $value The common prefix or suffix for table names.
     */
    public function setTablePrefix(string $value): void;

    /**
     * Executes callback provided in a transaction.
     *
     * @param Closure $closure A valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * {@see TransactionInterface::begin()} for details.
     *
     * @throws Throwable If there is any exception during query. In this case, the transaction will be rolled back.
     *
     * @return mixed Result of callback function.
     *
     * @psalm-param Closure(ConnectionInterface):mixed|Closure(ConnectionInterface):void $closure
     */
    public function transaction(Closure $closure, ?string $isolationLevel = null): mixed;
}
