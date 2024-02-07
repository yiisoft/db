<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class BigIntColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_BIGINT,
        string|null $phpType = SchemaInterface::PHP_TYPE_INTEGER,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): int|string|ExpressionInterface|null
    {
        return match (true) {
            is_int($value), $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            $value === false => 0,
            PHP_INT_MIN <= $value && $value <= PHP_INT_MAX => (int) $value,
            default => (string) $value,
        };
    }

    public function phpTypecast(mixed $value): int|string|null
    {
        return match (true) {
            $value === null => null,
            PHP_INT_MIN <= $value && $value <= PHP_INT_MAX => (int) $value,
            default => (string) $value,
        };
    }
}
