<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents array and JSON overlaps conditions.
 */
interface OverlapsConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name or an Expression.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @return ExpressionInterface|iterable An array of values that {@see columns} value should overlap.
     */
    public function getValues(): iterable|ExpressionInterface;
}
