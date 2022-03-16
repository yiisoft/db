<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

interface NotConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|array|null|string the condition to be negated.
     */
    public function getCondition(): ExpressionInterface|array|null|string;
}
