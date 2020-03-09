<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

/**
 * Condition that connects two or more SQL expressions with the `AND` operator.
 */
class OrCondition extends ConjunctionCondition
{
    /**
     * Returns the operator that is represented by this condition class, e.g. `AND`, `OR`.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return 'OR';
    }
}
