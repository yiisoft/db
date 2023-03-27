<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a simple condition, such as `column = value`.
 */
interface SimpleConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name. If it's an array, a composite `IN` condition will be
     * generated.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @return string The operator to use such as `>` or `<=`.
     */
    public function getOperator(): string;

    /**
     * @return mixed The value to the right of {@see operator}.
     */
    public function getValue(): mixed;
}
