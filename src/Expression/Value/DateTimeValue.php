<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use DateTimeInterface;
use Yiisoft\Db\Expression\ExpressionInterface;

final class DateTimeValue implements ExpressionInterface
{
    public function __construct(
        public readonly DateTimeInterface $value,
        public readonly DateTimeType $type = DateTimeType::DateTimeTz,
        public readonly int|null $size = 0,
    ) {
    }
}
