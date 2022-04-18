<?php

declare(strict_types=1);

namespace Yiisoft\Db\Transaction;

use Psr\Log\LoggerAwareInterface;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

interface TransactionInterface extends LoggerAwareInterface
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const READ_COMMITTED = 'READ COMMITTED';

    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Begins a transaction.
     *
     * @param string|null $isolationLevel The {@see isolation level}[] to use for this transaction.
     * This can be one of {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE}
     * but also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgresSQL, where setting the isolation level before the transaction has
     * no effect. You have to call {@see setIsolationLevel()} in this case after the transaction has started.
     *
     * > Note: Some (DBMS) allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature you may need to set the
     * isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     *
     * @throws Exception|Throwable If DB connection fails or the current transaction is active.
     * @throws InvalidConfigException If {@see db} is `null` or invalid.
     * @throws NotSupportedException If the DBMS does not support nested transactions or the transaction is active.
     */
    public function begin(?string $isolationLevel = null): void;

    /**
     * Commits a transaction.
     *
     * @throws Exception|Throwable If the transaction is not active
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
     */
    public function rollBack(): void;

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However, this is not supported by all DBMS, so you might rather specify the isolation level directly when calling
     * {@see begin()}.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE}
     * but also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception|Throwable If the transaction is not active.
     *
     * @see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel(string $level): void;

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void;

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name The savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBackSavepoint(string $name): void;

    /**
     * Releases an existing savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function releaseSavepoint(string $name): void;
}
