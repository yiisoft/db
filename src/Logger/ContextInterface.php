<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger;

use Throwable;

/**
 * Logger context.
 */
interface ContextInterface
{
    public function getMethodName(): string;

    public function getException(): Throwable|null;
    public function setException(Throwable $e): static;
}
