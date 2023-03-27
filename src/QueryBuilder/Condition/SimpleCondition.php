<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\SimpleConditionInterface;

/**
 * Represents a simple condition like `"column" operator value`.
 */
final class SimpleCondition implements SimpleConditionInterface
{
    public function __construct(
        private string|ExpressionInterface $column,
        private string $operator,
        private mixed $value
    ) {
    }

    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

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
