<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use function count;

use Yiisoft\Db\Exception\InvalidArgumentException;

/**
 * Class SimpleCondition represents a simple condition like `"column" operator value`.
 */
class SimpleCondition implements ConditionInterface
{
    private string $operator;
    private $column;
    private $value;

    public function __construct($column, string $operator, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return string the operator to use. Anything could be used e.g. `>`, `<=`, etc.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed the column name to the left of {@see operator}.
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return mixed the value to the right of the {@see operator}.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static($operands[0], $operator, $operands[1]);
    }
}
