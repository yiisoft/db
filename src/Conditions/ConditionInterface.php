<?php

declare(strict_types=1);

namespace Yiisoft\Db\Conditions;

use Yiisoft\Db\Contracts\ExpressionInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;

/**
 * Interface ConditionInterface should be implemented by classes that represent a condition in DBAL of framework.
 */
interface ConditionInterface extends ExpressionInterface
{
    /**
     * Creates object by array-definition as described in
     * [Query Builder – Operator format](guide:db-query-builder#operator-format) guide article.
     *
     * @param string $operator operator in uppercase.
     * @param array  $operands array of corresponding operands
     *
     * @throws InvalidArgumentException if input parameters are not suitable for this condition
     *
     * @return $this
     */
    public static function fromArrayDefinition(string $operator, array $operands): self;
}
