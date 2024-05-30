<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class BigIntColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_BIGINT,
        string|null $phpType = SchemaInterface::PHP_TYPE_STRING,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        return match (gettype($value)) {
            'string' => $value === '' ? null : (
                $value <= PHP_INT_MAX && $value >= PHP_INT_MIN
                    ? (int) $value
                    : $value
            ),
            'NULL' => null,
            'integer' => $value,
            'boolean' => $value ? 1 : 0,
            default => $value instanceof ExpressionInterface ? $value : (
                ($val = (string) $value) <= PHP_INT_MAX && $val >= PHP_INT_MIN
                    ? (int) $val
                    : $val
            ),
        };
    }

    public function phpTypecast(mixed $value): string|null
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
