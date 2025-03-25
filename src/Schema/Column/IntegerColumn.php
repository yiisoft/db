<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use BackedEnum;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\PhpType;

use function is_int;

/**
 * Represents the schema for an integer column.
 */
class IntegerColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::INTEGER;

    public function dbTypecast(mixed $value): int|ExpressionInterface|null
    {
        if (is_int($value)) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
            default => match (true) {
                $value instanceof ExpressionInterface => $value,
                $value instanceof BackedEnum => (int) $value->value,
                default => (int) $value,
            },
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
