<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function is_string;

/**
 * @internal
 *
 * Condition that's represented `BETWEEN` or `NOT BETWEEN` operator is used to check if a value is between two values.
 */
abstract class AbstractBetween implements ConditionInterface
{
    /**
     * @param ExpressionInterface|string $column The column name.
     * @param mixed $intervalStart Beginning of the interval.
     * @param mixed $intervalEnd End of the interval.
     */
    final public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly mixed $intervalStart,
        public readonly mixed $intervalEnd,
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 3.
     */
    final public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new static(
            self::validateColumn($operator, $operands[0]),
            $operands[1],
            $operands[2],
        );
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
