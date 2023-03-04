<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

use Throwable;

interface ContextInterface
{
    /**
     * @return string Type of the context
     */
    public function getType(): string;

    public function setException(Throwable $e): static;

    public function __toArray(): array;
}
