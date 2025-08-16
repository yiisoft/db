<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Represents condition that always evaluates to true.
 * This condition is used when no filtering is needed.
 */
final class All implements ConditionInterface
{
    public static function fromArrayDefinition(string $operator, array $operands): ConditionInterface
    {
        return new self();
    }
}
