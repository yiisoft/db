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

/**
 * Represents the schema for an integer column.
 */
class IntegerColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::INTEGER;

    public function dbTypecast(mixed $value): int|ExpressionInterface|null
    {
        /** @var int|ExpressionInterface|null */
        return match (gettype($value)) {
            GettypeResult::INTEGER => $value,
            GettypeResult::NULL => null,
            GettypeResult::STRING => $value === '' ? null : (int) $value,
            GettypeResult::DOUBLE => (int) $value,
            GettypeResult::BOOLEAN => $value ? 1 : 0,
            GettypeResult::OBJECT => match (true) {
                $value instanceof ExpressionInterface => $value,
                $value instanceof BackedEnum => (int) $value->value,
                $value instanceof DateTimeInterface => $value->getTimestamp(),
                $value instanceof Stringable => (int)(string) $value,
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    /** @psalm-mutation-free */
    public function getPhpType(): string
    {
        return PhpType::INT;
    }

    public function phpTypecast(mixed $value): int|null
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
