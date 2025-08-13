<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_key_exists;
use function is_string;

/**
 * @internal
 *
 * Abstract condition that represents comparison operators.
 */
abstract class AbstractCompare implements ConditionInterface
{
    /**
     * @param ExpressionInterface|string $column The column name or an expression.
     * @param mixed $value The value to compare with.
     */
    final public function __construct(
        public readonly string|ExpressionInterface $column,
        public readonly mixed $value
    ) {
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException
     */
    final public static function fromArrayDefinition(string $operator, array $operands): static
    {
        if (!array_key_exists(0, $operands)) {
            throw new InvalidArgumentException("Operator '$operator' requires first operand as column.");
        }
        if (!array_key_exists(1, $operands)) {
            throw new InvalidArgumentException("Operator '$operator' requires second operand as value.");
        }

        [$column, $value] = $operands;

        if (!is_string($column) && !$column instanceof ExpressionInterface) {
            throw new InvalidArgumentException("Operator '$operator' requires column to be string or ExpressionInterface.");
        }

        return new static($column, $value);
    }
}
