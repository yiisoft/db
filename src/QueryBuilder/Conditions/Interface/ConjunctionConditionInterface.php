<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

interface ConjunctionConditionInterface extends ConditionInterface
{
    /**
     * Returns the operator that is represented by this condition class, e.g. `AND`, `OR`.
     *
     * @return string
     */
    public function getOperator(): string;

    /**
     * Returns the expressions that are connected by this condition.
     */
    public function getExpressions(): array;
}
