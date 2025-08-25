<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnFactoryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * Represents a value that should be treated as a date and time value for specific column type.
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
final class DateTimeValue implements ExpressionInterface
{
    /**
     * @param int|float|string|Stringable|DateTimeInterface $value The value to be treated as a date and time value.
     * @param string $type The column type. The following types are supported:
     * - {@see ColumnType::DATE}
     * - {@see ColumnType::DATETIME}
     * - {@see ColumnType::DATETIMETZ}
     * - {@see ColumnType::TIME}
     * - {@see ColumnType::TIMETZ}
     * - {@see ColumnType::TIMESTAMP}
     * - {@see ColumnType::BIGINT}
     * - {@see ColumnType::INTEGER}
     * - {@see ColumnType::FLOAT}
     * - {@see ColumnType::DOUBLE}
     * - {@see ColumnType::DECIMAL}
     * @param array $info Additional information about {@see ColumnInterface the column}.
     *
     * @psalm-param ColumnType::* $type
     * @psalm-param ColumnInfo $info
     */
    public function __construct(
        public readonly int|float|string|Stringable|DateTimeInterface $value,
        public readonly string $type = ColumnType::DATETIMETZ,
        public readonly array $info = [],
    ) {
        match ($type) {
            ColumnType::DATE,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::TIMESTAMP,
            ColumnType::BIGINT,
            ColumnType::INTEGER,
            ColumnType::FLOAT,
            ColumnType::DOUBLE,
            ColumnType::DECIMAL => null,
            default => throw new InvalidArgumentException("The type '$type' is not supported by DateTimeValue."),
        };
    }
}
