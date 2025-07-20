<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Represents a condition that's composed by many other conditions connected by a conjunction
 * (for example, `AND`, `OR`).
 */
abstract class AbstractConjunctionCondition implements ConditionInterface
{
    final public function __construct(
        protected array $expressions,
    ) {
    }

    /**
     * @return string The operator that's represented by this condition class, such as `AND`, `OR`.
     */
    abstract public function getOperator(): string;

    /**
     * @return array The expressions that are connected by this condition.
     */
    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
