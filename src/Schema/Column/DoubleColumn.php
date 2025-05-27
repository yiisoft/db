<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\PhpType;

use function gettype;
use function is_int;

/**
 * Represents the metadata for a double column.
 */
class DoubleColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::DOUBLE;

    public function dbTypecast(mixed $value): ExpressionInterface|float|int|null
    {
        /** @var ExpressionInterface|float|int|null */
        return match (gettype($value)) {
            GettypeResult::DOUBLE => $value,
            GettypeResult::INTEGER => $value,
            GettypeResult::NULL => null,
            GettypeResult::STRING => $value === '' ? null : (float) $value,
            GettypeResult::BOOLEAN => $value ? 1.0 : 0.0,
            GettypeResult::OBJECT => match (true) {
                $value instanceof ExpressionInterface => $value,
                $value instanceof BackedEnum => $value->value === ''
                    ? null
                    : (is_int($value->value) ? $value->value : (float) $value->value),
                $value instanceof DateTimeInterface => (float) $value->format('U.u'),
                $value instanceof Stringable => ($val = (string) $value) === '' ? null : (float) $val,
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    /** @psalm-mutation-free */
    public function getPhpType(): string
    {
        return PhpType::FLOAT;
    }

    public function phpTypecast(mixed $value): float|null
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }
}
