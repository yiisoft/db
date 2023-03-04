<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Interface ConditionInterface should be implemented by classes that represent a condition in DBAL of framework.
 */
interface ConditionInterface extends ExpressionInterface
{
    /**
     * Creates object by array-definition.
     *
     * @param string $operator Operator in uppercase.
     * @param array  $operands Array of corresponding operands
     *
     * @throws InvalidArgumentException If input parameters are not suitable for this condition.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self;
}
