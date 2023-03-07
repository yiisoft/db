<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Yiisoft\Db\Query\QueryInterface;

/**
 * Represents a condition that checks whether a sub-query returns any rows.
 */
interface ExistConditionInterface extends ConditionInterface
{
    /**
     * @return string The operator to use (for example, `EXISTS` or `NOT EXISTS`).
     */
    public function getOperator(): string;

    /**
     * @return QueryInterface The {@see Query} object representing the sub-query.
     */
    public function getQuery(): QueryInterface;
}
