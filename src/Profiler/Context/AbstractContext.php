<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler\Context;

use Throwable;
use Yiisoft\Db\Profiler\ContextInterface;

abstract class AbstractContext implements ContextInterface
{
    protected const METHOD = 'method';
    protected const EXCEPTION = 'exception';

    private Throwable|null $exception = null;

    public function __construct(private string $method)
    {
    }

    public function setException(Throwable $e): static
    {
        $this->exception = $e;
        return $this;
    }

    public function asArray(): array
    {
        return [
            self::METHOD => $this->method,
            self::EXCEPTION => $this->exception,
        ];
    }
}
