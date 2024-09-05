<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\PhpType;

use function is_float;

class DoubleColumnSchema extends AbstractColumnSchema
{
    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        string $type = ColumnType::DOUBLE,
    ) {
        parent::__construct($type);
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

    public function getPhpType(): string
    {
        return PhpType::FLOAT;
    }

    public function phpTypecast(mixed $value): float|null
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }
}
