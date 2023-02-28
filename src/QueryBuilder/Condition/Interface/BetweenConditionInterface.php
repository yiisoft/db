<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Interface BetweenConditionInterface represents a condition which is used to check if a value is between two values.
 */
interface BetweenConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @return mixed End of the interval.
     */
    public function getIntervalEnd(): mixed;

    /**
     * @return mixed Beginning of the interval.
     */
    public function getIntervalStart(): mixed;

    /**
     * @return string The operator to use (e.g. `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string;
}
