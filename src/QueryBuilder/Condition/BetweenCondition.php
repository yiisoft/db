<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\BetweenConditionInterface;

/**
 * Class BetweenCondition represents a `BETWEEN` condition.
 */
final class BetweenCondition implements BetweenConditionInterface
{
    public function __construct(
        private string|ExpressionInterface $column,
        private string $operator,
        private mixed $intervalStart,
        private mixed $intervalEnd
    ) {
    }

    public function getColumn(): string|ExpressionInterface
    {
        return $this->column;
    }

    public function getIntervalEnd(): mixed
    {
        return $this->intervalEnd;
    }

    public function getIntervalStart(): mixed
    {
        return $this->intervalStart;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Creates a condition based on the given operator and operands.
     *
     * @throws InvalidArgumentException If the number of operands is not 3.
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        return new self(self::validateColumn($operator, $operands[0]), $operator, $operands[1], $operands[2]);
    }

    /**
     * Validates the given column to be string or ExpressionInterface.
     *
     * @throws InvalidArgumentException If the column is not string or ExpressionInterface.
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
