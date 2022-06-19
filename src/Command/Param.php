<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Yiisoft\Db\Expression\ExpressionInterface;

final class Param implements ParamInterface, ExpressionInterface
{
    public function __construct(private mixed $value, private int $type)
    {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
