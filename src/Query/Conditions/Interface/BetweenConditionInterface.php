<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

interface BetweenConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @psalm-return string|string[]|ExpressionInterface The column name. If it is an array, a composite `IN` condition
     * will be generated.
     */
    public function getColumn(): string|array|ExpressionInterface;

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
