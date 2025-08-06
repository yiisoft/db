<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Iterator;
use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_array;
use function is_int;
use function is_string;

/**
 * @internal
 *
 * Represents `IN` and `NOT IN` operators.
 */
abstract class AbstractIn implements ConditionInterface
{
    /**
     * @param array|ExpressionInterface|Iterator|string $column The column name. If it's an array, a composite `IN` condition
     * will be generated.
     * @param int|iterable|Iterator|QueryInterface $values An array of values that {@see $columns} value should be among.
     * If it's an empty array, the generated expression will be a `false` value if {@see $operator} is `IN` and empty if
     * operator is `NOT IN`.
     */
    final public function __construct(
        public readonly array|string|Iterator|ExpressionInterface $column,
        public readonly int|iterable|Iterator|QueryInterface $values
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 2.
     */
    final public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static(
            self::validateColumn($operator, $operands[0]),
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
