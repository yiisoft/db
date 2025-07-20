<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;

use function array_key_exists;

/**
 * Represents a simple condition like `"column" operator value`.
 */
final class SimpleCondition implements ConditionInterface
{
    public function __construct(
        private string|ExpressionInterface $column,
        private string $operator,
        private mixed $value
    ) {
    }

    /**
     * @return ExpressionInterface|string The column name or an Expression.
     */
    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    /**
     * @return string The operator to use such as `>` or `<=`.
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return mixed The value to the right of {@see operator}.
     */
    public function getValue(): mixed
    {
        return $this->value;
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
