<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\AwareTrait\LoggerAwareTrait;
use Yiisoft\Db\AwareTrait\ProfilerAwareTrait;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class Connection implements ConnectionInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    protected ?TransactionInterface $transaction = null;
    private bool $enableSavepoint = true;
    private int $serverRetryInterval = 600;
    private string $tablePrefix = '';

    public function __construct(private QueryCache $queryCache)
    {
    }

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

    public function cache(callable $callable, int $duration = null, Dependency $dependency = null): mixed
    {
        $this->queryCache->setInfo(
            [$duration ?? $this->queryCache->getDuration(), $dependency]
        );
        /** @var mixed */
        $result = $callable($this);
        $this->queryCache->removeLastInfo();

        return $result;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchemaInterface
    {
        return $this->getSchema()->getTableSchema($name, $refresh);
    }

    public function getTransaction(): ?TransactionInterface
    {
        return $this->transaction && $this->transaction->isActive() ? $this->transaction : null;
    }

    public function isSavepointEnabled(): bool
    {
        return $this->enableSavepoint;
    }

    public function noCache(callable $callable): mixed
    {
        $queryCache = $this->queryCache;
        $queryCache->setInfo(false);
        /** @var mixed */
        $result = $callable($this);
        $queryCache->removeLastInfo();

        return $result;
    }

    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    public function setTablePrefix(string $value): void
    {
        $this->tablePrefix = $value;
    }

    public function transaction(callable $callback, string $isolationLevel = null): mixed
    {
        $transaction = $this->beginTransaction($isolationLevel);

        $level = $transaction->getLevel();

        try {
            /** @var mixed */
            $result = $callback($this);

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
     */
    private function rollbackTransactionOnLevel(TransactionInterface $transaction, int $level): void
    {
        if ($transaction->isActive() && $transaction->getLevel() === $level) {
            /**
             * {@see https://github.com/yiisoft/yii2/pull/13347}
             */
            try {
                $transaction->rollBack();
            } catch (\Exception $e) {
                if ($this->logger !== null) {
                    $this->logger->log(LogLevel::ERROR, (string) $e, [__METHOD__]);
                    /** hide this exception to be able to continue throwing original exception outside */
                }
            }
        }
    }
}
