<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

final class ColumnName implements ExpressionInterface
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
