<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Condition that's represented `BETWEEN` operator is used to check if a value is between two values.
 */
final class Between implements ConditionInterface
{
    /**
     * @param ExpressionInterface|string $column The column name.
     * @param string $operator The operator to use (for example `BETWEEN` or `NOT BETWEEN`).
     * @param mixed $intervalStart Beginning of the interval.
     * @param mixed $intervalEnd End of the interval.
     */
    public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly string $operator,
        public readonly mixed $intervalStart,
        public readonly mixed $intervalEnd
    ) {
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
