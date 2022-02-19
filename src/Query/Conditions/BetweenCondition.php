<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;

/**
 * Class BetweenCondition represents a `BETWEEN` condition.
 */
class BetweenCondition implements ConditionInterface
{
    public function __construct(
        private mixed $column,
        private string $operator,
        private mixed $intervalStart,
        private mixed $intervalEnd
    ) {
    }

    /**
     * @return string the operator to use (e.g. `BETWEEN` or `NOT BETWEEN`).
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
     * @return mixed beginning of the interval.
     */
    public function getIntervalStart()
    {
        return $this->intervalStart;
    }

    /**
     * @return mixed end of the interval.
     */
    public function getIntervalEnd()
    {
        return $this->intervalEnd;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new static($operands[0], $operator, $operands[1], $operands[2]);
    }
}
