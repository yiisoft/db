<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\Command\Command;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidCallException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Transaction\Transaction;
use Yiisoft\Profiler\ProfilerInterface;

interface ConnectionInterface
{
    public const MODE_MASTER = 'master';
    public const MODE_SLAVE = 'slave';

    /**
     * Starts a transaction.
     *
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * @return Transaction the transaction initiated
     * @throws InvalidCallException
     * @throws NotSupportedException
     * @throws Exception
     * {@see Transaction::begin()} for details.
     */
    public function beginTransaction($isolationLevel = null): Transaction;

    /**
     * Closes the currently active DB connection.
     *
     * It does nothing if the connection is already closed.
     */
    public function close(): void;

    /**
     * Creates a command for execution.
     *
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand(?string $sql = null, array $params = []): Command;

    public function getDual(): self;

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $sequenceName name of the sequence object (required by some DBMS)
     * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
     * @throws InvalidCallException if the DB connection is not active
     * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
     */
    public function getLastInsertID(string $sequenceName = null): string;

    public function getLogger(): LoggerInterface;

    public function getMaster(): self;

    public function getProfiler(): ?ProfilerInterface;

    /**
     * Returns the query builder for the current DB connection.
     *
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilder;

    /**
     * Returns the schema information for the database opened by this connection.
     *
     * @return Schema the schema information for the database opened by this connection.
     */
    public function getSchema(): Schema;

    /**
     * Returns the currently active slave connection.
     *
     * If this method is called for the first time, it will try to open a slave connection when {@see setEnableSlaves()}
     * is true.
     *
     * @param bool $fallbackToMaster whether to return a master connection in case there is no slave connection
     * available.
     * @throws InvalidConfigException
     * @return self the currently active slave connection. `null` is returned if there is no slave available and
     * `$fallbackToMaster` is false.
     */
    public function getSlave(bool $fallbackToMaster = true): ?self;

    public function getTablePrefix(): string;

    /**
     * Returns the currently active transaction.
     *
     * @return Transaction|null the currently active transaction. Null if no active transaction.
     */
    public function getTransaction(): ?Transaction;

    /**
     * Returns a value indicating whether the DB connection is established.
     *
     * @return bool whether the DB connection is established
     */
    public function isActive(): bool;

    public function isLoggingEnabled(): bool;

    public function isProfilingEnabled(): bool;

    public function isQueryCacheEnabled(): bool;

    public function isSavepointEnabled(): bool;

    public function isSchemaCacheEnabled(): bool;

    public function isTransactionEnabled(): bool;

    /**
     * Disables query cache temporarily.
     *
     * Queries performed within the callable will not use query cache at all. For example,
     *
     * ```php
     * $db->useCache(function (ConnectionInterface $db, int $id) {
     *
     *     // ... queries that use query cache ...
     *
     *     return $db->noUseCache(function (Connection $db, int $id) {
     *         // this query will not use query cache
     *         return $db->createCommand('SELECT * FROM customer WHERE id=:id', [':id' => $id])->queryOne();
     *     }, $id);
     * }, 100);
     * ```
     *
     * @param callable $callback a PHP callable that contains DB queries which should not use query cache. The signature
     * of the callable is `function (Connection $db)`.
     * @param mixed ...$params
     * @return mixed the return result of the callable
     * @throws Throwable if there is any exception during query
     * {@see enableQueryCache}
     * {@see queryCache}
     * {@see cache()}
     */
    public function noUseCache(callable $callback, ...$params);

    /**
     * Uses query cache for the queries performed with the callable.
     *
     * When query caching is enabled ({@see enableQueryCache} is true and {@see queryCache} refers to a valid cache),
     * queries performed within the callable will be cached and their results will be fetched from cache if available.
     *
     * For example,
     *
     * ```php
     * // The customer will be fetched from cache if available.
     * // If not, the query will be made against DB and cached for use next time.
     * $customer = $db->useCache(function (ConnectionInterface $db, int $id) {
     *     return $db->createCommand('SELECT * FROM customer WHERE id=:id', [':id' => $id])->queryOne();
     * }, [1]);
     * ```
     *
     * Note that query cache is only meaningful for queries that return results. For queries performed with
     * {@see PDOCommand::execute()}, query cache will not be used.
     *
     * @param callable $callback a PHP callable that contains DB queries which will make use of query cache.
     * The signature of the callable is `function (Connection $db)`.
     * @param int|null $duration the number of seconds that query results can remain valid in the cache. If this is not
     * set, the value of {@see queryCacheDuration} will be used instead. Use 0 to indicate that the cached data will
     * never expire.
     * @param Dependency|null $dependency the cache dependency associated with the cached query results.
     * @param array $params
     * @return mixed the return result of the callable
     * @throws Throwable if there is any exception during query
     * {@see setEnableQueryCache()}
     * {@see queryCache}
     * {@see noCache()}
     */
    public function useCache(callable $callback, int $duration = null, Dependency $dependency = null, array $params = []);

    /**
     * Executes the provided callback by using a regular connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function ($db, $limit) {
     *
     *     // ... queries that use master connection ...
     *
     *     $result = $db->useDual(function ($db, $limit) {
     *         return $db->createCommand('SELECT * FROM user LIMIT :limit', [':limit' => $limit])->queryOne();
     *     }, $limit);
     * }, 1);
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     * @param mixed ...$params
     * @return mixed the return value of the callback
     * @throws Throwable if there is any exception thrown from the callback
     */
    public function useDual(callable $callback, ...$params);

    /**
     * Executes the provided callback by using the master connection.
     *
     * This method is provided so that you can temporarily force using the master connection to perform DB operations
     * even if they are read queries. For example,
     *
     * ```php
     * $result = $db->useMaster(function ($db, $limit) {
     *     return $db->createCommand('SELECT * FROM user LIMIT :limit', [':limit' => $limit])->queryOne();
     * }, 1);
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     * @param mixed ...$params
     * @return mixed the return value of the callback
     * @throws Throwable if there is any exception thrown from the callback
     */
    public function useMaster(callable $callback, ...$params);

    /**
     * ```php
     * $result = $db->useTransaction(function (Transaction $trans, $limit) {
     *     return $trans->getDb()->createCommand('SELECT * FROM user LIMIT :limit', [':limit' => $limit])->queryOne();
     * }, 1);
     * ```
     *
     * @param callable $callback
     * @param null $isolationLevel
     * @param array $params
     * @return mixed
     * @throws Throwable if there is any exception thrown from the callback
     */
    public function useTransaction(callable $callback, $isolationLevel = null, array $params = []);
}
