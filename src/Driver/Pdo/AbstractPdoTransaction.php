<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Represents a DB transaction.
 *
 * A transaction is a set of SQL statements that must either all succeed or all fail.
 *
 * It's usually created by calling {@see \Yiisoft\Db\Connection\AbstractConnectionAbstractConnection::beginTransaction()}.
 *
 * The following code is a typical example of using transactions (note that some DBMS may not support transactions):
 *
 * ```php
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     // ... other SQL executions
 *     $transaction->commit();
 * } catch (\Throwable $e) {
 *     $transaction->rollBack();
 *     throw $e;
 * }
 * ```
 */
abstract class AbstractPdoTransaction implements TransactionInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int The nesting level of the transaction.
     */
    private int $level = 0;

    public function __construct(protected PdoConnectionInterface $db)
    {
    }

    public function begin(string $isolationLevel = null): void
    {
        $this->db->open();

        if ($this->level === 0) {
            if ($isolationLevel !== null) {
                $this->setTransactionIsolationLevel($isolationLevel);
            }

            $this->logger?->log(
                LogLevel::DEBUG,
                'Begin transaction' . ($isolationLevel ? ' with isolation level ' . $isolationLevel : '')
                . ' ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );

            $this->db->getPDO()?->beginTransaction();
            $this->level = 1;

            return;
        }

        if ($this->db->isSavepointEnabled()) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Set savepoint ' . $this->level . ' ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );

            $this->createSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Transaction not started: nested transaction not supported ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );

            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->level++;
    }

    public function commit(): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->level--;

        if ($this->level === 0) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Commit transaction ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
            $this->db->getPDO()?->commit();

            return;
        }

        if ($this->db->isSavepointEnabled()) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Release savepoint ' . $this->level . ' ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
            $this->releaseSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not committed: nested transaction not supported ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
        }
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isActive(): bool
    {
        /** Extra check pdo->inTransaction {@link https://github.com/yiisoft/yii2/pull/18407/} */
        return $this->level > 0 && $this->db->isActive() && $this->db->getPDO()?->inTransaction();
    }

    public function rollBack(): void
    {
        if (!$this->isActive()) {
            /**
             * Do nothing if a transaction isn't active: this could be the transaction is committed but the event
             * handler to "commitTransaction" throw an exception
             */
            return;
        }

        $this->level--;

        if ($this->level === 0) {
            $this->logger?->log(
                LogLevel::INFO,
                'Roll back transaction ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
            $this->db->getPDO()?->rollBack();

            return;
        }

        if ($this->db->isSavepointEnabled()) {
            $this->logger?->log(
                LogLevel::DEBUG,
                'Roll back to savepoint ' . $this->level . ' ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
            $this->rollBackSavepoint('LEVEL' . $this->level);
        } else {
            $this->logger?->log(
                LogLevel::INFO,
                'Transaction not rolled back: nested transaction not supported ' . __METHOD__,
                ['type' => LogType::TRANSACTION]
            );
        }
    }

    public function setIsolationLevel(string $level): void
    {
        if (!$this->isActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }

        $this->logger?->log(
            LogLevel::DEBUG,
            'Setting transaction isolation level to ' . $this->level . ' ' . __METHOD__,
            ['type' => LogType::TRANSACTION]
        );
        $this->setTransactionIsolationLevel($level);
    }

    public function createSavepoint(string $name): void
    {
        $this->db->createCommand("SAVEPOINT $name")->execute();
    }

    public function rollBackSavepoint(string $name): void
    {
        $this->db->createCommand("ROLLBACK TO SAVEPOINT $name")->execute();
    }

    public function releaseSavepoint(string $name): void
    {
        $this->db->createCommand("RELEASE SAVEPOINT $name")->execute();
    }

    /**
     * Sets the transaction isolation level.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }
}
