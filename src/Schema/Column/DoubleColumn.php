<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

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
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
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
