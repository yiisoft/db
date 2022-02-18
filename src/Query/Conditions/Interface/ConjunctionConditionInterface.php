<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface ConjunctionConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * Returns the operator that is represented by this condition class, e.g. `AND`, `OR`.
     *
     * @return string
     */
    public function getOperator(): string;
}
