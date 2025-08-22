<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use Yiisoft\Db\Constant\ColumnType;

enum DateTimeType
{
    case Timestamp;
    case DateTime;
    case DateTimeTz;
    case Time;
    case TimeTz;
    case Date;
    case Integer;
    case Float;

    /**
     * @psalm-return ColumnType::*
     */
    public function getColumnType(): string
    {
        return match ($this) {
            self::Timestamp,
            self::DateTime => ColumnType::DATETIME,
            self::DateTimeTz => ColumnType::DATETIMETZ,
            self::Time => ColumnType::TIME,
            self::TimeTz => ColumnType::TIMETZ,
            self::Date => ColumnType::DATE,
            self::Integer => ColumnType::INTEGER,
            self::Float => ColumnType::FLOAT,
        };
    }
}
