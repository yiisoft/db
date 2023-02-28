<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Interface SimpleConditionInterface represents a simple condition, such as `column = value`.
 */
interface SimpleConditionInterface extends ConditionInterface
{
    /**
     * @return string|ExpressionInterface The column name. If it is an array, a composite `IN` condition
     * will be generated.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @return string The operator to use. Anything could be used e.g. `>`, `<=`, etc.
     */
    public function getOperator(): string;

    /**
     * @return mixed The value to the right of the {@see operator}.
     */
    public function getValue(): mixed;
}
