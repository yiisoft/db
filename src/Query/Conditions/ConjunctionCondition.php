<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

/**
 * Class ConjunctionCondition.
 */
abstract class ConjunctionCondition implements ConditionInterface
{
    public function __construct(protected mixed $expressions)
    {
    }

    public function getExpressions(): array
    {
        return $this->expressions;
    }

    /**
     * Returns the operator that is represented by this condition class, e.g. `AND`, `OR`.
     *
     * @return string
     */
    abstract public function getOperator(): string;

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
