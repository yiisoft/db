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
use Yiisoft\Db\Profiler\ProfilerInterface;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
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
    public function beginTransaction(string $isolationLevel = null): TransactionInterface;

    /**
     * Create a batch query result instance.
     *
     * @param QueryInterface $query The query to be executed.
     * @param bool $each Whether to return each row of the result set one at a time.
     *
     * @return BatchQueryResultInterface The batch query result instance.
     */
    public function createBatchQueryResult(QueryInterface $query, bool $each = false): BatchQueryResultInterface;

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql The SQL statement to be executed
     * @param array $params The parameters to be bound to the SQL statement
     *
     * @throws Exception
     * @throws InvalidConfigException
     *
     * @return CommandInterface The database command instance.
     */
    public function createCommand(string $sql = null, array $params = []): CommandInterface;

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
     * Return a cache key as an array.
     *
     * For example, in PDO implementation: [$dsn, $username]
     *
     * @return array The cache key as an array.
     */
    public function getCacheKey(): array;

    /**
     * Returns the name of the DB driver for the current `dsn`.
     *
     * Use this method for information only.
     *
     * @return string The name of the DB driver for the current `dsn`.
     */
    public function getName(): string;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $sequenceName The name of the sequence object (required by some DBMS).
     *
     * @throws Exception
     * @throws InvalidCallException
     *
     * @return string The row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * @link http://php.net/manual/en/pdo.lastinsertid.php'>http://php.net/manual/en/pdo.lastinsertid.php
     */
    public function getLastInsertID(string $sequenceName = null): string;

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
     * Returns a server version as a string comparable by {@see \version_compare()}.
     *
     * @return string The server version as a string.
     */
    public function getServerVersion(): string;

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
     * @param bool $refresh Whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchemaInterface|null The schema information for the named table. Null if the named table does not
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
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool Whether the DB connection is established.
     */
    public function isActive(): bool;

    /**
     * @return bool Whether this DBMS supports [savepoint](http://en.wikipedia.org/wiki/Savepoint).
     */
    public function isSavepointEnabled(): bool;

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception
     * @throws InvalidConfigException If connection fails.
     */
    public function open(): void;

    /**
     * Quotes a value for use in a query.
     *
     * @return mixed The properly quoted string.
     */
    public function quoteValue(mixed $value): mixed;

    /**
     * Whether to enable [savepoint](http://en.wikipedia.org/wiki/Savepoint). Note that if the underlying DBMS does not
     * support savepoint, setting this property to be true will have no effect.
     *
     * @param bool $value Whether to enable savepoint.
     */
    public function setEnableSavepoint(bool $value): void;

    /**
     * Sets the profiler instance.
     *
     * @param ProfilerInterface|null $profiler The profiler instance.
     */
    public function setProfiler(ProfilerInterface|null $profiler): void;

    /**
     * The common prefix or suffix for table names. If a table name is given as `{{%TableName}}`, then the percentage
     * character `%` will be replaced with this property value. For example, `{{%post}}` becomes `{{tbl_post}}`.
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
     */
    public function transaction(Closure $closure, string $isolationLevel = null): mixed;
}
