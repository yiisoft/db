<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a condition which is used to check if a value is between two values.
 */
interface BetweenColumnsConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name or expression that's an end of the interval.
     */
    public function getIntervalEndColumn(): string|ExpressionInterface;

    /**
     * @return ExpressionInterface|string The column name or expression that's the beginning of the interval.
     */
    public function getIntervalStartColumn(): string|ExpressionInterface;

    /**
     * @return string The operator to use (for example `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string;

    /**
     * @return array|ExpressionInterface|int|Iterator|string The value to compare against.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface;
}
