<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_float;

class DoubleColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_DOUBLE,
        string|null $phpType = SchemaInterface::PHP_TYPE_DOUBLE,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): float|ExpressionInterface|null
    {
        return match (true) {
            is_float($value), $value === null, $value instanceof ExpressionInterface => $value,
            $value === '' => null,
            default => (float) $value,
        };
    }

    public function phpTypecast(mixed $value): float|null
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }
}
