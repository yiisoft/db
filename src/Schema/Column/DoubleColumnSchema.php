<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function is_float;

class DoubleColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_DOUBLE,
        string|null $phpType = SchemaInterface::PHP_TYPE_DOUBLE,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): float|ExpressionInterface|null
    {
        if (is_float($value)) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (float) $value,
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
