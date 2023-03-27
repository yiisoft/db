<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\NotConditionInterface;

use function array_shift;
use function count;
use function is_array;
use function is_string;

/**
 * Condition that represents `NOT` operator (negation).
 */
final class NotCondition implements NotConditionInterface
{
    public function __construct(private ExpressionInterface|array|null|string $condition)
    {
    }

    public function getCondition(): ExpressionInterface|array|null|string
    {
        return $this->condition;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands isn't 1.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        return new self(self::validateCondition($operator, $operands));
    }

    /**
     * Validate the given condition have at least 1 condition and to be `array`, `string`, `null` or `ExpressionInterface`.
     *
     * @throws InvalidArgumentException If the number of operands isn't 1.
     */
    private static function validateCondition(string $operator, array $condition): ExpressionInterface|array|null|string
    {
        if (count($condition) !== 1) {
            throw new InvalidArgumentException("Operator '$operator' requires exactly one operand.");
        }

        /** @psalm-var mixed $firstValue */
        $firstValue = array_shift($condition);

        if (
            is_array($firstValue) ||
            $firstValue instanceof ExpressionInterface ||
            is_string($firstValue) ||
            $firstValue === null
        ) {
            return $firstValue;
        }

        throw new InvalidArgumentException(
            "Operator '$operator' requires condition to be array, string, null or ExpressionInterface."
        );
    }
}
