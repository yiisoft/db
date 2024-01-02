<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger\Context;

use Throwable;
use Yiisoft\Db\Logger\ContextInterface;

abstract class AbstractContext implements ContextInterface
{
    private Throwable|null $exception = null;

    public function __construct(private string $methodName)
    {
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function setException(Throwable $e): static
    {
        $this->exception = $e;
        return $this;
    }

    public function getException(): Throwable|null
    {
        return $this->exception;
    }
}
