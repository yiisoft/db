<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Should be implemented by classes that represent a condition in the {@see \Yiisoft\Db\QueryBuilder\QueryBuilder}.
 */
interface ConditionInterface extends ExpressionInterface
{
    /**
     * Creates object by array-definition.
     *
     * @param string $operator Operator in uppercase.
     * @param array  $operands Array of corresponding operands
     *
     * @throws InvalidArgumentException If input parameters aren't suitable for this condition.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self;
}
