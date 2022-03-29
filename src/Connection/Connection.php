<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

use Psr\Log\LogLevel;
use Throwable;
use Yiisoft\Cache\Dependency\Dependency;
use Yiisoft\Db\AwareTrait\LoggerAwareTrait;
use Yiisoft\Db\AwareTrait\ProfilerAwareTrait;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Schema\TableSchema;
use Yiisoft\Db\Transaction\TransactionInterface;

abstract class Connection implements ConnectionInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    protected array $masters = [];
    protected array $slaves = [];
    protected ?ConnectionInterface $master = null;
    protected ?ConnectionInterface $slave = null;
    protected ?TransactionInterface $transaction = null;
    private ?bool $emulatePrepare = null;
    private bool $enableSavepoint = true;
    private bool $enableSlaves = true;
    private int $serverRetryInterval = 600;
    private bool $shuffleMasters = true;
    private string $tablePrefix = '';

    public function __construct(private QueryCache $queryCache)
    {
    }

    public function areSlavesEnabled(): bool
    {
        return $this->enableSlaves;
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

    public function getEmulatePrepare(): ?bool
    {
        return $this->emulatePrepare;
    }

    public function getLastInsertID(string $sequenceName = ''): string
    {
        return $this->getSchema()->getLastInsertID($sequenceName);
    }

    public function getMaster(): ?ConnectionInterface
    {
        if ($this->master === null) {
            $this->master = $this->shuffleMasters
                ? $this->openFromPool($this->masters)
                : $this->openFromPoolSequentially($this->masters);
        }

        return $this->master;
    }

    public function getSlave(bool $fallbackToMaster = true): ?ConnectionInterface
    {
        if (!$this->enableSlaves) {
            return $fallbackToMaster ? $this : null;
        }

        if ($this->slave === null) {
            $this->slave = $this->openFromPool($this->slaves);
        }

        return $this->slave === null && $fallbackToMaster ? $this : $this->slave;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getTableSchema(string $name, bool $refresh = false): ?TableSchema
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

    public function setEmulatePrepare(bool $value): void
    {
        $this->emulatePrepare = $value;
    }

    public function setEnableSavepoint(bool $value): void
    {
        $this->enableSavepoint = $value;
    }

    public function setEnableSlaves(bool $value): void
    {
        $this->enableSlaves = $value;
    }

    public function setMaster(string $key, ConnectionInterface $master): void
    {
        $this->masters[$key] = $master;
    }

    public function setServerRetryInterval(int $value): void
    {
        $this->serverRetryInterval = $value;
    }

    public function setShuffleMasters(bool $value): void
    {
        $this->shuffleMasters = $value;
    }

    public function setSlave(string $key, ConnectionInterface $slave): void
    {
        $this->slaves[$key] = $slave;
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

    public function useMaster(callable $callback): mixed
    {
        if ($this->enableSlaves) {
            $this->enableSlaves = false;

            try {
                /** @var mixed */
                $result = $callback($this);
            } catch (Throwable $e) {
                $this->enableSlaves = true;

                throw $e;
            }
            $this->enableSlaves = true;
        } else {
            /** @var mixed */
            $result = $callback($this);
        }

        return $result;
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in random order.
     *
     * @param array $pool The list of connection configurations in the server pool.
     *
     * @return ConnectionInterface|null The opened DB connection, or `null` if no server is available.
     */
    protected function openFromPool(array $pool): ?ConnectionInterface
    {
        shuffle($pool);
        return $this->openFromPoolSequentially($pool);
    }

    /**
     * Opens the connection to a server in the pool.
     *
     * This method implements the load balancing among the given list of the servers.
     *
     * Connections will be tried in sequential order.
     *
     * @param array $pool
     *
     * @return ConnectionInterface|null The opened DB connection, or `null` if no server is available.
     */
    protected function openFromPoolSequentially(array $pool): ?ConnectionInterface
    {
        if (!$pool) {
            return null;
        }

        /** @psalm-var array<array-key, ConnectionPDOInterface> $pool */
        foreach ($pool as $poolConnection) {
            $key = [__METHOD__, $poolConnection->getDriver()->getDsn()];

            if (
                $this->getSchema()->getSchemaCache()->isEnabled() &&
                $this->getSchema()->getSchemaCache()->getOrSet($key, null, $this->serverRetryInterval)
            ) {
                /** should not try this dead server now */
                continue;
            }

            try {
                $poolConnection->open();

                return $poolConnection;
            } catch (Exception $e) {
                $this->logger?->log(
                    LogLevel::WARNING,
                    "Connection ({$poolConnection->getDriver()->getDsn()}) failed: " . $e->getMessage() . ' ' . __METHOD__
                );

                if ($this->getSchema()->getSchemaCache()->isEnabled()) {
                    /** mark this server as dead and only retry it after the specified interval */
                    $this->getSchema()->getSchemaCache()->set($key, 1, $this->serverRetryInterval);
                }

                return null;
            }
        }

        return null;
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
