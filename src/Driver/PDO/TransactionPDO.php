<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionInterface;

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
 * {@see READ_UNCOMMITTED}, {@see READ_COMMITTED}, {@see REPEATABLE_READ} and {@see SERIALIZABLE} but also a string
 * containing DBMS specific syntax to be used after `SET TRANSACTION ISOLATION LEVEL`. This property is write-only.
 * @property int $level The current nesting level of the transaction. This property is read-only.
 */
abstract class TransactionPDO implements TransactionInterface
{
    use LoggerAwareTrait;

    private int $level = 0;

    public function __construct(protected ConnectionPDOInterface $db)
    {
    }

    /**
     * @inheritDoc
     */
    public function begin(?string $isolationLevel = null): void
    {
        $this->db->open();

        if ($this->level === 0) {
            if ($isolationLevel !== null) {
                $this->setTransactionIsolationLevel($isolationLevel);
            }

            $this->logger?->log(
                LogLevel::DEBUG,
                'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : '')
                . ' ' . __METHOD__
            );

            $this->db->getPDO()?->beginTransaction();
            $this->level = 1;

            return;
        }

        $schema = $this->db->getSchema();

        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Set savepoint ' . $this->level . ' ' . __METHOD__);

            $this->createSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Transaction not started: nested transaction not supported ' . __METHOD__
            );

            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->level++;
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->level--;
        if ($this->level === 0) {
            $this->logger?->log(LogLevel::DEBUG, 'Commit transaction ' . __METHOD__);
            $this->db->getPDO()?->commit();

            return;
        }

        $schema = $this->db->getSchema();

        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Release savepoint ' . $this->level . ' ' . __METHOD__);
            $this->releaseSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not committed: nested transaction not supported ' . __METHOD__
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @inheritDoc
     */
    public function isActive(): bool
    {
        return $this->level > 0 && $this->db->isActive();
    }

    /**
     * @inheritDoc
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function rollBack(): void
    {
        if (!$this->isActive()) {
            /**
             * do nothing if transaction is not active: this could be the transaction is committed but the event handler
             * to "commitTransaction" throw an exception
             */
            return;
        }

        $this->level--;
        if ($this->level === 0) {
            $this->logger?->log(LogLevel::INFO, 'Roll back transaction ' . __METHOD__);
            $this->db->getPDO()?->rollBack();

            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            $this->logger?->log(LogLevel::DEBUG, 'Roll back to savepoint ' . $this->level . ' ' . __METHOD__);
            $this->rollBackSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not rolled back: nested transaction not supported ' . __METHOD__
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function setIsolationLevel(string $level): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }

        $this->logger?->log(
            LogLevel::DEBUG,
            'Setting transaction isolation level to ' . $this->level . ' ' . __METHOD__
        );
        $this->setTransactionIsolationLevel($level);
    }

    /**
     * @throws Exception|InvalidConfigException|Throwable
     */
    protected function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $name the savepoint name
     *
     * @throws Exception|InvalidConfigException|Throwable
     */
    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    /**
     * @inheritDoc
     */
    public function releaseSavepoint(string $name): void
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }
}
