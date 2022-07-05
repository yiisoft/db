<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;

interface BetweenColumnsConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name or expression that is an end of the interval.
     */
    public function getIntervalEndColumn(): string|ExpressionInterface;

    /**
     * @return ExpressionInterface|string The column name or expression that is a beginning of the interval.
     */
    public function getIntervalStartColumn(): string|ExpressionInterface;

    /**
     * @return string The operator to use (e.g. `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string;

    /**
     * @return array|ExpressionInterface|int|Iterator|string The value to compare against.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface;
}
