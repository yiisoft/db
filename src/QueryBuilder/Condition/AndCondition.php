<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Condition that connects two or more SQL expressions with the `AND` operator.
 */
final class AndCondition extends AbstractConjunctionCondition
{
    /**
     * @return string The operator that's represented by this condition class, such as `AND`, `OR`.
     */
    public function getOperator(): string
    {
        return 'AND';
    }
}
