<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

interface NotConditionInterface extends ConditionInterface
{
    /**
     * @return array|ExpressionInterface|string|null the condition to be negated.
     */
    public function getCondition(): ExpressionInterface|array|null|string;
}
