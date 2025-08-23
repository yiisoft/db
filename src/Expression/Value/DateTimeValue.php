<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use DateTimeInterface;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;

final class DateTimeValue implements ExpressionInterface
{
    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        public readonly DateTimeInterface $value,
        public readonly string $type = ColumnType::DATETIMETZ,
        public readonly ?array $info = null,
    ) {
    }
}
