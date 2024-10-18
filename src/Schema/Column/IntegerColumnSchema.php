<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\PhpType;

use function is_int;

/**
 * Represents the schema for an integer column.
 */
class IntegerColumnSchema extends AbstractColumnSchema
{
    protected const DEFAULT_TYPE = ColumnType::INTEGER;

    public function dbTypecast(mixed $value): int|ExpressionInterface|null
    {
        if (is_int($value)) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (int) $value,
        };
    }

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
