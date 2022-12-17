<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * @codeCoverageIgnore
 */
interface ConditionInterface extends ExpressionInterface
{
    /**
     * Creates object by array-definition as described in
     * [Query Builder – Operator format](guide:db-query-builder#operator-format) guide article.
     *
     * @param string $operator Operator in uppercase.
     * @param array  $operands Array of corresponding operands
     *
     * @throws InvalidArgumentException If input parameters are not suitable for this condition
     *
     * @return self
     */
    public static function fromArrayDefinition(string $operator, array $operands): self;
}
