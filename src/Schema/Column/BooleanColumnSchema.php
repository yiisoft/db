<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

class BooleanColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        string $type = SchemaInterface::TYPE_BOOLEAN,
        string|null $phpType = SchemaInterface::PHP_TYPE_BOOLEAN,
    ) {
        parent::__construct($type, $phpType);
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

    public function phpTypecast(mixed $value): bool|null
    {
        if ($value === null) {
            return null;
        }

        return $value && $value !== "\0";
    }
}
