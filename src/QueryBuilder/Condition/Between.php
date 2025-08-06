<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Condition that's represented `BETWEEN` operator is used to check if a value is between two values.
 */
final class Between extends AbstractBetween
{
    public function isNot(): bool
    {
        return false;
    }
}
