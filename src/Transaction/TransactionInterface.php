<?php

declare(strict_types=1);

namespace Yiisoft\Db\Transaction;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

/**
 * Defines the interface for a database transaction.
 *
 * A transaction is a set of operations that are executed as a single logical unit of work.
 *
 * The main benefit of using transactions is that they allow for the atomic, consistent, isolated for many database
 * operations.
 *
 * The class defines several methods for working with transactions, such as {@see begin()}, {@see commit()}, and
 * {@see rollBack()}.
 */
interface TransactionInterface
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public const READ_COMMITTED = 'READ COMMITTED';
    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public const REPEATABLE_READ = 'REPEATABLE READ';
    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Begins a transaction.
     *
     * @param string|null $isolationLevel The {@see isolation level}[] to use for this transaction.
     * This can be one of {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE}
     * but also a string containing DBMS-specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     * If not specified (`null`), the isolation level won't be set explicitly and the DBMS default will be used.
     *
     * Note: This setting doesn't work for PostgresSQL, where setting the isolation level before the transaction has no
     * effect.
     *
     * You have to call {@see setIsolationLevel()} in this case after the transaction has started.
     *
     * Note: Some (DBMS) allow setting of the isolation level only for the whole connection so later transactions may
     * get the same isolation level even if you didn't specify any. When using this feature, you may need to set the
     * isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     *
     * @throws Exception
     * @throws Throwable If DB connection fails or the current transaction is active.
     * @throws InvalidConfigException If {@see \Yiisoft\Db\Connection\ConnectionInterface} is `null` or invalid.
     * @throws NotSupportedException If the DBMS doesn't support nested transactions or the transaction is active.
     */
    public function begin(string $isolationLevel = null): void;

    /**
     * Commits a transaction.
     *
     * @throws Exception
     * @throws Throwable If the transaction isn't active
     */
    public function commit(): void;

    /**
     * @return int The nesting level of the transaction. 0 means the outermost level.
     */
    public function getLevel(): int;

    /**
     * Returns a value indicating whether this transaction is active.
     *
     * @return bool Whether this transaction is active. Only an active transaction can {@see commit()} or
     * {@see rollBack()}.
     */
    public function isActive(): bool;

    /**
     * Rolls back a transaction.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function rollBack(): void;

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * You can use this method to set the isolation level while the transaction is already active.
     * However, this isn't supported by all DBMS, so you might rather specify the isolation level directly when calling
     * {@see begin()}.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE}
     * but also a string containing DBMS-specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception
     * @throws Throwable If the transaction isn't active.
     *
     * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel(string $level): void;

    /**
     * Creates a new savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function createSavepoint(string $name): void;

    /**
     * Rolls back to a before created savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function rollBackSavepoint(string $name): void;

    /**
     * Releases an existing savepoint.
     *
     * @param string $name The savepoint name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function releaseSavepoint(string $name): void;
}
