<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\SchemaInterface;

class BooleanColumn extends Column
{
    public function __construct(
        string|null $type = SchemaInterface::TYPE_BOOLEAN,
        string|null $phpType = SchemaInterface::PHP_TYPE_BOOLEAN,
    ) {
        parent::__construct($type, $phpType);
    }

    public function dbTypecast(mixed $value): bool|ExpressionInterface|null
    {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        return match ($value) {
            null, '' => null,
            default => (bool) $value,
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
