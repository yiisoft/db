<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger\Context;

final class TransactionContext extends AbstractContext
{
    public function __construct(string $methodName, private int $level, private string|null $isolationLevel = null)
    {
        parent::__construct($methodName);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getIsolationLevel(): string|null
    {
        return $this->isolationLevel;
    }
}
