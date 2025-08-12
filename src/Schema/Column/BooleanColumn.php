<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents the metadata for a boolean column.
 */
class BooleanColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BOOLEAN;

    public function dbTypecast(mixed $value): bool|ExpressionInterface|null
    {
        return match ($value) {
            true => true,
            false => false,
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (bool) $value,
        };
    }

    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== "\0";
    }
}
