<?php

declare(strict_types=1);

namespace Yiisoft\Db\Transaction;

use Psr\Log\LoggerInterface;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling {@see Connection::beginTransaction()}.
 *
 * The following code is a typical example of using transactions (note that some DBMS may not support transactions):
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch (\Throwable $e) {
 *     $transaction->rollBack();
 *     throw $e;
 * }
 * ```
 *
 * @property bool $isActive Whether this transaction is active. Only an active transaction can {@see commit()} or
 * {@see rollBack()}. This property is read-only.
 * @property string $isolationLevel The transaction isolation level to use for this transaction. This can be one of
 * {@see LEVEL_READ_UNCOMMITTED}, {@see LEVEL_READ_COMMITTED}, {@see LEVEL_REPEATABLE_READ} and {@see LEVEL_SERIALIZABLE} but also a string
 * containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is write-only.
 * @property int $level The current nesting level of the transaction. This property is read-only.
 */
class Transaction
{
    /**
     * A constant representing the transaction isolation level `READ UNCOMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const LEVEL_READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * A constant representing the transaction isolation level `READ COMMITTED`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const LEVEL_READ_COMMITTED = 'READ COMMITTED';

    /**
     * A constant representing the transaction isolation level `REPEATABLE READ`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const LEVEL_REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * A constant representing the transaction isolation level `SERIALIZABLE`.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public const LEVEL_SERIALIZABLE = 'SERIALIZABLE';

    private Connection $db;
    private int $level = 0;
    private LoggerInterface $logger;
    private object $transactionManager;

    public function __construct(Connection $db, LoggerInterface $logger, object $transactionManager)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->transactionManager = $transactionManager;
    }

    /**
     * Returns a value indicating whether this transaction is active.
     *
     * @return bool whether this transaction is active. Only an active transaction can {@see commit()} or
     * {@see rollBack()}.
     */
    public function isActive(): bool
    {
        return $this->level > 0 && $this->db->isActive();
    }

    /**
     * Begins a transaction.
     *
     * @param string|null $isolationLevel The {@see isolation level}[] to use for this transaction.
     * This can be one of {@see LEVEL_READ_UNCOMMITTED}, {@see LEVEL_READ_COMMITTED}, {@see LEVEL_REPEATABLE_READ} and {@see LEVEL_SERIALIZABLE}
     * but also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgreSQL, where setting the isolation level before the transaction has
     * no effect. You have to call {@see setIsolationLevel()} in this case after the transaction has started.
     *
     * > Note: Some DBMS allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature you may need to set the
     * isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     *
     *
     * @throws InvalidConfigException if {@see db} is `null`
     * @throws NotSupportedException if the DBMS does not support nested transactions
     * @throws Exception if DB connection fails
     */
    public function begin(string $isolationLevel = null): void
    {
        $this->db->open();

        if ($this->level === 0) {
            if ($isolationLevel !== null) {
                $this->db->getSchema()->setTransactionIsolationLevel($isolationLevel);
            }

            $this->logger->debug('Begin transaction' . ($isolationLevel ? " with isolation level $isolationLevel" : '') . '.', [__METHOD__]);

            $this->transactionManager->beginTransaction();
            $this->level = 1;

            return;
        }

        $schema = $this->db->getSchema();

        if (!$schema->supportsSavepoint()) {
            $this->logger->warning('Transaction not started: nested transaction not supported.', [__METHOD__]);

            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->logger->debug("Set transaction savepoint level: {$this->level}.", [__METHOD__]);

        $schema->createSavepoint('LEVEL' . $this->level);

        $this->level++;
    }

    /**
     * Commits a transaction.
     *
     * @throws Exception if the transaction is not active
     */
    public function commit(): void
    {
        if (!$this->isActive()) {
            $this->logger->error('Failed to commit transaction: transaction was inactive.', [__METHOD__]);

            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->level--;
        if ($this->level === 0) {
            $this->logger->debug('Commit transaction.', [__METHOD__]);

            $this->transactionManager->commit();

            return;
        }

        $this->logger->debug("Release transaction savepoint level: {$this->level}.", [__METHOD__]);

        $this->db->getSchema()->releaseSavepoint('LEVEL' . $this->level);
    }

    /**
     * Rolls back a transaction.
     *
     * @throws Exception
     */
    public function rollBack(): void
    {
        if (!$this->isActive()) {
            $this->logger->error('Failed to rollback transaction: transaction was inactive.', [__METHOD__]);

            throw new Exception('Failed to rollback transaction: transaction was inactive.');
        }

        $this->level--;

        if ($this->level === 0) {
            $this->logger->info('Rollback transaction.', [__METHOD__]);

            $this->transactionManager->rollBack();

            return;
        }

        $this->logger->debug("Rollback to transaction savepoint: {$this->level}." . [__METHOD__]);

        $this->db->getSchema()->rollBackSavepoint('LEVEL' . $this->level);
    }

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However this is not supported by all DBMS so you might rather specify the isolation level directly when calling
     * {@see begin()}.
     *
     * @param string $level The transaction isolation level to use for this transaction.
     * This can be one of {@see LEVEL_READ_UNCOMMITTED}, {@see LEVEL_READ_COMMITTED}, {@see LEVEL_REPEATABLE_READ} and {@see LEVEL_SERIALIZABLE}
     * but also a string containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception if the transaction is not active.
     *
     * {@see http://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels}
     */
    public function setIsolationLevel(string $level): void
    {
        if (!$this->isActive()) {
            $this->logger->error('Failed to set transaction isolation level: transaction was inactive.', [__METHOD__]);

            throw new Exception('Failed to set transaction isolation level: transaction was inactive.');
        }

        $this->logger->debug("Setting transaction isolation level to {$this->level}.", [__METHOD__]);

        $this->db->getSchema()->setTransactionIsolationLevel($level);
    }

    /**
     * @return int the nesting level of the transaction. 0 means the outermost level.
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return Connection the database connection that this transaction.
     */
    public function getDb(): Connection
    {
        return $this->db;
    }
}
