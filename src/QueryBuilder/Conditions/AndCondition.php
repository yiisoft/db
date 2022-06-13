<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

/**
 * Condition that connects two or more SQL expressions with the `AND` operator.
 */
final class AndCondition extends ConjunctionCondition
{
    /**
     * Returns the operator that is represented by this condition class, e.g. `AND`, `OR`.
     *
     * @psalm-return 'AND'
     */
    public function getOperator(): string
    {
        return 'AND';
    }
}
