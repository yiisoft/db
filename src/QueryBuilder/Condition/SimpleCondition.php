<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_key_exists;

/**
 * Represents a simple condition like `"column" operator value`.
 */
final class SimpleCondition implements ConditionInterface
{
    /**
     * @param string|ExpressionInterface $column The column name or an expression.
     * @param string $operator The operator to use such as `>` or `<=`.
     * @param mixed $value The value to the right of {@see $operator}.
     */
    public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly string $operator,
        public readonly mixed $value
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 2.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (isset($operands[0]) && array_key_exists(1, $operands)) {
            return new self(self::validateColumn($operator, $operands[0]), $operator, $operands[1]);
        }

        throw new InvalidArgumentException("Operator '$operator' requires two operands.");
    }

    /**
     * Validate the given column to be `string` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the column isn't a `string` or `ExpressionInterface`.
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
