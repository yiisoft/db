<?php

declare(strict_types=1);

namespace Yiisoft\Db\Debug;

use Yiisoft\Db\Transaction\TransactionInterface;

final class TransactionInterfaceDecorator implements TransactionInterface
{
    public function __construct(
        private TransactionInterface $decorated,
        private DatabaseCollector $collector
    ) {
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    public function begin(string $isolationLevel = null): void
    {
        [$callStack] = debug_backtrace();

        $this->collector->collectTransactionStart($isolationLevel, $callStack['file'] . ':' . $callStack['line']);

        $this->decorated->begin($isolationLevel);
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    public function commit(): void
    {
        [$callStack] = debug_backtrace();

        $this->decorated->commit();

        $this->collector->collectTransactionCommit($callStack['file'] . ':' . $callStack['line']);
    }

    public function getLevel(): int
    {
        return $this->decorated->getLevel();
    }

    public function isActive(): bool
    {
        return $this->decorated->isActive();
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    public function rollBack(): void
    {
        [$callStack] = debug_backtrace();

        $this->decorated->rollBack();

        $this->collector->collectTransactionRollback($callStack['file'] . ':' . $callStack['line']);
    }

    public function setIsolationLevel(string $level): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function createSavepoint(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function rollBackSavepoint(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }

    public function releaseSavepoint(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...func_get_args());
    }
}
