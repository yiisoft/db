<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use DateTimeInterface;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;

/**
 * Represents a value that should be treated as a date and time value for specific column type.
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
final class DateTimeValue implements ExpressionInterface
{
    /**
     * @psalm-param ColumnType::* $type
     * @psalm-param ColumnInfo $info
     */
    public function __construct(
        public readonly int|float|string|Stringable|DateTimeInterface $value,
        public readonly string $type = ColumnType::DATETIMETZ,
        public readonly array $info = [],
    ) {
    }
}
