<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Closure;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Db\Profiler\ProfilerAwareTrait;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

/**
 * Represents a connection to a database. It provides methods for interacting with the database, such as executing SQL
 * queries and performing data manipulation.
 */
abstract class AbstractConnection implements ConnectionInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    protected TransactionInterface|null $transaction = null;
    private bool $enableSavepoint = true;
    private int $serverRetryInterval = 600;
    private string $tablePrefix = '';

    public function beginTransaction(string $isolationLevel = null): TransactionInterface
    {
        $this->open();
        $this->transaction = $this->getTransaction();

        if ($this->transaction === null) {
            $this->transaction = $this->createTransaction();
        }

        if ($this->logger !== null) {
            $this->transaction->setLogger($this->logger);
        }

        $this->transaction->begin($isolationLevel);

        return $this->transaction;
    }

    public function createBatchQueryResult(QueryInterface $query, bool $each = false): BatchQueryResultInterface
    {
        return new BatchQueryResult($query, $each);
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getTableSchema(string $name, bool $refresh = false): TableSchemaInterface|null
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    public function getTransaction(): TransactionInterface|null
    {
        return $this->transaction && $this->transaction->isActive() ? $this->transaction : null;
    }

    public function isSavepointEnabled(): bool
    {
        return $this->enableSavepoint;
    }

    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function transaction(Closure $closure, string $isolationLevel = null): mixed
    {
        $transaction = $this->beginTransaction($isolationLevel);
        $level = $transaction->getLevel();

        try {
            /** @psalm-var mixed $result */
            $result = $closure($this);

            if ($transaction->isActive() && $transaction->getLevel() === $level) {
                $transaction->commit();
            }
        } catch (Throwable $e) {
            $this->rollbackTransactionOnLevel($transaction, $level);

            throw $e;
        }

        return $result;
    }

    /**
     * Rolls back given {@see TransactionInterface} object if it's still active and level match. In some cases rollback
     * can fail, so this method is fail-safe. Exceptions thrown from rollback will be caught and just logged with
     * {@see logger->log()}.
     *
     * @param TransactionInterface $transaction TransactionInterface object given from {@see beginTransaction()}.
     * @param int $level TransactionInterface level just after {@see beginTransaction()} call.
     *
     * @throws Throwable If transaction was not rolled back.
     */
    private function rollbackTransactionOnLevel(TransactionInterface $transaction, int $level): void
    {
        if ($transaction->isActive() && $transaction->getLevel() === $level) {
            /**
             * @link https://github.com/yiisoft/yii2/pull/13347
             */
            try {
                $transaction->rollBack();
            } catch (Exception $e) {
                $this->logger?->log(LogLevel::ERROR, (string) $e, [__METHOD__]);
                /** hide this exception to be able to continue throwing original exception outside */
            }
        }
    }
}
