<?php

declare(strict_types=1);

namespace Yiisoft\Db\Querys\Conditions;

/**
 * Class ConjunctionCondition.
 */
abstract class ConjunctionCondition implements ConditionInterface
{
    /**
     * @var mixed[]
     */
    protected $expressions;

    /**
     * @param mixed $expressions
     */
    public function __construct($expressions)
    {
        $this->expressions = $expressions;
    }

    /**
     * @return mixed[]
     */
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

    /**
     * {@inheritdoc}
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new static($operands);
    }
}
