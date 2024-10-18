<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Constant\PhpType;

use function gettype;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Represents the metadata for a bigint column.
 */
class BigIntColumnSchema extends AbstractColumnSchema
{
    protected const DEFAULT_TYPE = ColumnType::BIGINT;

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        /** @var ExpressionInterface|int|string|null */
        return match (gettype($value)) {
            GettypeResult::STRING => $value === '' ? null : (
                $value <= PHP_INT_MAX && $value >= PHP_INT_MIN
                    ? (int) $value
                    : $value
            ),
            GettypeResult::NULL => null,
            GettypeResult::INTEGER => $value,
            GettypeResult::BOOLEAN => $value ? 1 : 0,
            default => $value instanceof ExpressionInterface ? $value : (
                ($val = (string) $value) <= PHP_INT_MAX && $val >= PHP_INT_MIN
                    ? (int) $val
                    : $val
            ),
        };
    }

    public function getPhpType(): string
    {
        return PhpType::STRING;
    }

    public function phpTypecast(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
