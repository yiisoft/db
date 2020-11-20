<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Class InCondition represents `IN` condition.
 */
class InCondition implements ConditionInterface
{
    private string $operator;
    private $column;

    /** @var ExpressionInterface[]|int|string[] @values */
    private $values;

    public function __construct($column, string $operator, $values)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->values = $values;
    }

    /**
     * @return string the operator to use (e.g. `IN` or `NOT IN`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string|string[] the column name. If it is an array, a composite `IN` condition will be generated.
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return ExpressionInterface[]|int|string[] (ExpressionInterface|string)[]|int an array of values that
     * {@see columns} value should be among.
     *
     * If it is an empty array the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     *
     * @psalm-return array<array-key, ExpressionInterface|string>|int
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static($operands[0], $operator, $operands[1]);
    }
}
