<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;

use function gettype;
use function is_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Represents the metadata for a bigint column.
 */
class BigIntColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIGINT;

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        /**
         * @var ExpressionInterface|int|string|null
         * @psalm-suppress MixedArgument
         */
        return match (gettype($value)) {
            GettypeResult::STRING => $this->dbTypecastString($value),
            GettypeResult::NULL => null,
            GettypeResult::INTEGER => $value,
            GettypeResult::DOUBLE => $this->dbTypecastString((string) $value),
            GettypeResult::BOOLEAN => $value ? 1 : 0,
            GettypeResult::OBJECT => match (true) {
                $value instanceof ExpressionInterface => $value,
                $value instanceof BackedEnum => is_int($value->value)
                    ? $value->value
                    : $this->dbTypecastString($value->value),
                $value instanceof DateTimeInterface => $value->getTimestamp(),
                $value instanceof Stringable => $this->dbTypecastString((string) $value),
                default => $this->throwWrongTypeException($value::class),
            },
            default => $this->throwWrongTypeException(gettype($value)),
        };
    }

    public function phpTypecast(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected function dbTypecastString(string $value): int|string|null
    {
        if ($value === '') {
            return null;
        }

        return PHP_INT_MAX >= $value && $value >= PHP_INT_MIN ? (int) $value : $value;
    }
}
