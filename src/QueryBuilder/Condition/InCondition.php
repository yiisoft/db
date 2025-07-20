<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\ConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Condition that represents `IN` operator.
 */
final class InCondition implements ConditionInterface
{
    public function __construct(
        private array|string|Iterator|ExpressionInterface $column,
        private string $operator,
        private int|iterable|Iterator|QueryInterface $values
    ) {
    }

    /**
     * @return array|ExpressionInterface|Iterator|string The column name. If it's an array, a composite `IN` condition
     * will be generated.
     */
    public function getColumn(): array|string|ExpressionInterface|Iterator
    {
        return $this->column;
    }

    /**
     * @return string The operator to use (for example, `IN` or `NOT IN`).
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return int|iterable|Iterator|QueryInterface An array of values that {@see columns} value should be among.
     *
     * If it's an empty array, the generated expression will be a `false` value if {@see operator} is `IN` and empty if
     * operator is `NOT IN`.
     */
    public function getValues(): int|iterable|Iterator|QueryInterface
    {
        return $this->values;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 2.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new self(
            self::validateColumn($operator, $operands[0]),
            $operator,
            self::validateValues($operator, $operands[1]),
        );
    }

    /**
     * Validates the given column to be `string`, `array` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the column isn't a `string`, `array` or `ExpressionInterface`.
     */
    private static function validateColumn(string $operator, mixed $column): array|string|Iterator|ExpressionInterface
    {
        if (is_string($column) || is_array($column) || $column instanceof Iterator || $column instanceof ExpressionInterface) {
            return $column;
        }

        throw new InvalidArgumentException("Operator '$operator' requires column to be string, array or Iterator.");
    }

    /**
     * Validates the given values to be `array`, `Iterator`, `int` or `QueryInterface`.
     *
     * @throws InvalidArgumentException If the values aren't an `array`, `Iterator`, `int` or `QueryInterface`.
     */
    private static function validateValues(string $operator, mixed $values): int|iterable|Iterator|QueryInterface
    {
        if (is_array($values) || $values instanceof Iterator || is_int($values) || $values instanceof QueryInterface) {
            return $values;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires values to be array, Iterator, int or QueryInterface."
        );
    }
}
