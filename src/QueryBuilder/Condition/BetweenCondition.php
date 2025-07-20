<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Condition that's represented `BETWEEN` operator is used to check if a value is between two values.
 */
final class BetweenCondition implements ConditionInterface
{
    public function __construct(
        private string|ExpressionInterface $column,
        private string $operator,
        private mixed $intervalStart,
        private mixed $intervalEnd
    ) {
    }

    /**
     * @return ExpressionInterface|string The column name.
     */
    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    /**
     * @return mixed End of the interval.
     */
    public function getIntervalEnd(): mixed
    {
        return $this->intervalEnd;
    }

    /**
     * @return mixed Beginning of the interval.
     */
    public function getIntervalStart(): mixed
    {
        return $this->intervalStart;
    }

    /**
     * @return string The operator to use (for example `BETWEEN` or `NOT BETWEEN`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 3.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new self(self::validateColumn($operator, $operands[0]), $operator, $operands[1], $operands[2]);
    }

    /**
     * Validates the given column to be string or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the column isn't a string or `ExpressionInterface`.
     */
    private static function validateColumn(string $operator, mixed $column): string|ExpressionInterface
    {
        if (is_string($column) || $column instanceof ExpressionInterface) {
            return $column;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires column to be string or ExpressionInterface."
        );
    }
}
