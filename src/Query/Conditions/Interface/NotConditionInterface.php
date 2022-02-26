<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

interface NotConditionInterface extends ConditionInterface
{
    /**
     * @return mixed the condition to be negated.
     */
    public function getCondition(): mixed;
}
