<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Condition that's represented `NOT BETWEEN` operator is used to check if a value is not between two values.
 */
final class NotBetween extends AbstractBetween
{
    public function isNot(): bool
    {
        return true;
    }
}
