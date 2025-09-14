<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Traversable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function is_array;
use function is_string;

/**
 * Represents `IN` and `NOT IN` operators.
 */
abstract class AbstractIn implements ConditionInterface
{
    /**
     * @param ExpressionInterface|iterable|string $column The column name. If it's an array, a composite
     * condition will be generated.
     * @param iterable|QueryInterface $values An array of values that {@see $columns} value should be among.
     * If it's an empty array, the generated expression will be a `false` value if {@see $operator} is `IN` and empty if
     * operator is `NOT IN`.
     *
     * @psalm-param iterable<string|ExpressionInterface>|string|ExpressionInterface $column
     */
    final public function __construct(
        public readonly iterable|string|ExpressionInterface $column,
        public readonly iterable|QueryInterface $values
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
     * Prepare the given column to be `string`, `array` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the column isn't a `string`, `iterable` or `ExpressionInterface`.
     *
     * @psalm-return array<string|ExpressionInterface>|string|ExpressionInterface
     */
    private static function validateColumn(string $operator, mixed $column): string|array|ExpressionInterface
    {
        if (is_string($column) || $column instanceof ExpressionInterface) {
            return $column;
        }

        if ($column instanceof Traversable) {
            $column = iterator_to_array($column);
        }

        if (is_array($column)) {
            foreach ($column as $columnItem) {
                if (!is_string($columnItem) && !$columnItem instanceof ExpressionInterface) {
                    throw new InvalidArgumentException(
                        "Operator '$operator' requires column to be string, ExpressionInterface or iterable."
                    );
                }
            }
            /** @psalm-var array<string|ExpressionInterface> */
            return $column;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires column to be string, ExpressionInterface or iterable."
        );
    }

    /**
     * Validates the given values to be `iterable` or `QueryInterface`.
     *
     * @throws InvalidArgumentException If the values aren't an `iterable` or `QueryInterface`.
     */
    private static function validateValues(string $operator, mixed $values): iterable|QueryInterface
    {
        if (is_iterable($values) || $values instanceof QueryInterface) {
            return $values;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires values to be iterable or QueryInterface."
        );
    }
}
