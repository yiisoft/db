<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

interface ExistConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @return string The operator to use (e.g. `EXISTS` or `NOT EXISTS`).
     */
    public function getOperator(): string;

    /**
     * @return QueryInterface The {@see Query} object representing the sub-query.
     */
    public function getQuery(): QueryInterface;
}
