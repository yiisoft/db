<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use DateTimeInterface;

final class TimeTzValue implements ExpressionInterface
{
    public function __construct(
        public readonly DateTimeInterface $value,
    ) {
    }
}
