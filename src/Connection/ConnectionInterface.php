<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\CommandInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Transaction\TransactionInterface;

use function version_compare;

interface ConnectionInterface
{
    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     *
     * {@see TransactionInterface::begin()} for details.
     *
     * @throws Exception|InvalidConfigException|NotSupportedException|Throwable
     *
     * @return TransactionInterface The transaction initiated
     */
    public function beginTransaction(string $isolationLevel = null): TransactionInterface;

    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ({@see enableQueryCache} is true and {@see QueryCache} refers to a valid cache),
     * queries performed within the callable will be cached and their results will be fetched from cache if available.
     *
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->cache(function (ConnectionInterface $db) {
     *     return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     * });
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * {@see Command::execute()}, query cache will not be used.
     *
     * @param callable $callable A PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (ConnectionInterface $db)`.
     * @param int|null $duration The number of seconds that query results can remain valid in the cache. If this is not
     * set, the value of {@see QueryCache::getDuration()} will be used instead. Use 0 to indicate that the cached data
     * will never expire.
     * @param Dependency|null $dependency The cache dependency associated with the cached query results.
     *
     * @throws Throwable If there is any exception during query.
     *
     * @return mixed The return result of the callable.
     *
     * {@see setEnableQueryCache()}
     * {@see queryCache}
     * {@see noCache()}
     */
    public function cache(callable $callable, int $duration = null, Dependency $dependency = null): mixed;

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql The SQL statement to be executed
     * @param array $params The parameters to be bound to the SQL statement
     *
     * @throws Exception|InvalidConfigException
     *
     * @return CommandInterface
     */
    public function createCommand(?string $sql = null, array $params = []): CommandInterface;

    /**
     * Create a transaction instance.
     */
    public function createTransaction(): TransactionInterface;

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void;

    /**
     * Return cache key as array.
     * For example in PDO implementation: [$dsn, $username]
     *
     * @return array
     */
    public function getCacheKey(): array;

    /**
     * Returns the name of the DB driver.
     *
     * @return string name of the DB driver
     */
    public function getDriverName(): string;

    /**
     * Return emulate prepare value.
     */
    public function getEmulatePrepare(): ?bool;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $sequenceName name of the sequence object (required by some DBMS)
     *
     * @throws Exception|InvalidCallException
     *
     * @return string The row ID of the last row inserted, or the last value retrieved from the sequence object
     *
     * @link http://php.net/manual/en/pdo.lastinsertid.php'>http://php.net/manual/en/pdo.lastinsertid.php
     */
    public function getLastInsertID(?string $sequenceName = null): string;

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilderInterface the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilderInterface;

    /**
     * Return quoter helper for current DB connection.
     */
    public function getQuoter(): QuoterInterface;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return SchemaInterface the schema information for the database opened by this connection.
     */
    public function getSchema(): SchemaInterface;

    /**
     * Returns a server version as a string comparable by {@see version_compare()}.
     *
     * @return string server version as a string.
     */
    public function getServerVersion(): string;

    /**
     * Return table prefix for current DB connection.
     */
    public function getTablePrefix(): string;

    /**
     * Obtains the schema information for the named table.
     *
     * @param string $name table name.
     * @param bool $refresh whether to reload the table schema even if it is found in the cache.
     *
     * @return TableSchema|null
     */
    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema;

    /**
     * Returns the currently active transaction.
     *
     * @return TransactionInterface|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?TransactionInterface;

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established.
     */
    public function isActive(): bool;

    public function isSavepointEnabled(): bool;

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->cache(function (ConnectionInterface $db) {
     *
     *     // ... queries that use query cache ...
     *
     *     return $db->noCache(function (ConnectionInterface $db) {
     *         // this query will not use query cache
     *         return $db->createCommand('SELECT * FROM customer WHERE id=1')->queryOne();
     *     });
     * });
     * ```
     *
     * @param callable $callable A PHP callable that contains DB queries which should not use query cache. The signature
     * of the callable is `function (ConnectionInterface $db)`.
     *
     * @throws Throwable If there is any exception during query.
     *
     * @return mixed The return result of the callable.
     *
     * {@see enableQueryCache}
     * {@see queryCache}
     * {@see cache()}
     */
    public function noCache(callable $callable): mixed;

    /**
     * Establishes a DB connection.
     *
     * It does nothing if a DB connection has already been established.
     *
     * @throws Exception|InvalidConfigException if connection fails
     */
    public function open(): void;

    /**
     * Whether to turn on prepare emulation. Defaults to false, meaning PDO will use the native prepare support if
     * available. For some databases (such as MySQL), this may need to be set true so that PDO can emulate to prepare
     * support to bypass the buggy native prepare support. The default value is null, which means the PDO
     * ATTR_EMULATE_PREPARES value will not be changed.
     *
     * @param bool $value whether to turn on prepare emulation.
     */
    public function setEmulatePrepare(bool $value): void;

    /**
     * Whether to enable [savepoint](http://en.wikipedia.org/wiki/Savepoint). Note that if the underlying DBMS does not
     * support savepoint, setting this property to be true will have no effect.
     *
     * @param bool $value Whether to enable savepoint.
     */
    public function setEnableSavepoint(bool $value): void;

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
     * @param callable $callback A valid PHP callback that performs the job. Accepts connection instance as parameter.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * {@see TransactionInterface::begin()} for details.
     *
     *@throws Throwable If there is any exception during query. In this case the transaction will be rolled back.
     *
     * @return mixed Result of callback function.
     */
    public function transaction(callable $callback, string $isolationLevel = null): mixed;
}
