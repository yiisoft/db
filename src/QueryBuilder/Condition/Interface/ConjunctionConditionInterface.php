<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

/**
 * Represents a condition that's composed by many other conditions connected by a conjunction
 * (for example, `AND`, `OR`).
 */
interface ConjunctionConditionInterface extends ConditionInterface
{
    /**
     * @return string The operator that's represented by this condition class, such as `AND`, `OR`.
     */
    public function getOperator(): string;

    /**
     * @return array The expressions that are connected by this condition.
     */
    public function getExpressions(): array;
}
