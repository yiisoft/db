<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\OverlapConditionInterface;

use function is_iterable;
use function is_string;

/**
 * Condition that's represented `OVERLAP` operator is used to check if a value is between two values.
 */
abstract class AbstractOverlapCondition implements OverlapConditionInterface
{
    public function __construct(
        private string|ExpressionInterface $column,
        private iterable|ExpressionInterface $values,
    ) {
    }

    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    public function getValues(): iterable|ExpressionInterface
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
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new static(
            self::validateColumn($operator, $operands[0]),
            self::validateValues($operator, $operands[1])
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

    /**
     * Validates the given values to be `iterable` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the values aren't an `iterable` or `ExpressionInterface`.
     */
    private static function validateValues(string $operator, mixed $values): iterable|ExpressionInterface
    {
        if (is_iterable($values) || $values instanceof ExpressionInterface) {
            return $values;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires values to be iterable or ExpressionInterface."
        );
    }
}
