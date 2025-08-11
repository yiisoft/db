<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

final class Value implements ExpressionInterface
{
    public function __construct(
        public readonly mixed $value,
    ) {
    }
}
