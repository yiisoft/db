<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

/**
 * ConjunctionConditionInterface represents a condition that is composed by multiple other conditions connected by a
 * conjunction (e.g. `AND`, `OR`).
 */
interface ConjunctionConditionInterface extends ConditionInterface
{
    /**
     * @return string The operator that is represented by this condition class, e.g. `AND`, `OR`.
     */
    public function getOperator(): string;

    /**
     * @return array The expressions that are connected by this condition.
     */
    public function getExpressions(): array;
}
