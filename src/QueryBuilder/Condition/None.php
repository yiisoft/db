<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Represents condition that always evaluates to false.
 * This condition is used to filter out all records.
 */
final class None implements ConditionInterface
{
    public static function fromArrayDefinition(string $operator, array $operands): ConditionInterface
    {
        return new self();
    }
}
