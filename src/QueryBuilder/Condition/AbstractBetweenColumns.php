<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function is_string;

/**
 * @internal
 *
 * Represents a `BETWEEN` and `NOT BETWEEN` operator where values are between two columns.
 */
abstract class AbstractBetweenColumns implements ConditionInterface
{
    /**
     * @param mixed $value The value to compare against.
     * @param ExpressionInterface|string $intervalStartColumn The column name or expression that's the beginning of the interval.
     * @param ExpressionInterface|string $intervalEndColumn The column name or expression that's the end of the interval.
     */
    final public function __construct(
        public readonly mixed $value,
        public readonly string|ExpressionInterface $intervalStartColumn,
        public readonly string|ExpressionInterface $intervalEndColumn,
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
            $operands[0],
            self::validateIntervalStartColumn($operator, $operands[1]),
            self::validateIntervalEndColumn($operator, $operands[2]),
        );
    }

    /**
     * Validates the given interval start column to be string or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the interval start column isn't string or `ExpressionInterface`.
     */
    private static function validateIntervalStartColumn(
        string $operator,
        mixed $intervalStartColumn
    ): string|ExpressionInterface {
        if (
            is_string($intervalStartColumn) ||
            $intervalStartColumn instanceof ExpressionInterface
        ) {
            return $intervalStartColumn;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires interval start column to be string or ExpressionInterface."
        );
    }

    /**
     * Validates the given interval end column to be string or ExpressionInterface.
     *
     * @throws InvalidArgumentException If the interval end column isn't a string or ExpressionInterface.
     */
    private static function validateIntervalEndColumn(
        string $operator,
        mixed $intervalEndColumn
    ): string|ExpressionInterface {
        if (
            is_string($intervalEndColumn) ||
            $intervalEndColumn instanceof ExpressionInterface
        ) {
            return $intervalEndColumn;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires interval end column to be string or ExpressionInterface."
        );
    }
}
