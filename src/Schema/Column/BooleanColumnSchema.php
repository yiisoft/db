<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Constant\PhpType;

class BooleanColumnSchema extends AbstractColumnSchema
{
    /**
     * @psalm-param ColumnType::* $type
     */
    public function __construct(
        string $type = ColumnType::BOOLEAN,
    ) {
        parent::__construct($type);
    }

    public function dbTypecast(mixed $value): bool|ExpressionInterface|null
    {
        return match ($value) {
            true => true,
            false => false,
            null, '' => null,
            default => $value instanceof ExpressionInterface ? $value : (bool) $value,
        };
    }

    public function getPhpType(): string
    {
        return PhpType::BOOL;
    }

    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== "\0";
    }
}
