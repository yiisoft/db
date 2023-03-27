<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a condition that's based on `NOT` operator.
 */
interface NotConditionInterface extends ConditionInterface
{
    /**
     * @return array|ExpressionInterface|string|null the condition to negate.
     */
    public function getCondition(): ExpressionInterface|array|null|string;
}
