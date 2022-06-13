<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

use Yiisoft\Db\Query\QueryInterface;

interface ExistConditionInterface extends ConditionInterface
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
