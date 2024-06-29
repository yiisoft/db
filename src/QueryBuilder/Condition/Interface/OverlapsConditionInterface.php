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
     * @return ExpressionInterface|string The column name. If it's an array, a composite `IN` condition will be
     * generated.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @return iterable|ExpressionInterface An array of values that {@see columns} value should overlap.
     */
    public function getValues(): iterable|ExpressionInterface;
}
