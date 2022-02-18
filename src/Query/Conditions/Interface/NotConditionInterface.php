<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;

interface NotConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @return mixed the condition to be negated.
     */
    public function getCondition(): mixed;
}
