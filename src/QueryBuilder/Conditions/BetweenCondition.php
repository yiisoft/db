<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\BetweenConditionInterface;
use Yiisoft\Db\Expression\Expression;

/**
 * Class BetweenCondition represents a `BETWEEN` condition.
 */
final class BetweenCondition implements BetweenConditionInterface
{
    public function __construct(
        private string|Expression $column,
        private string $operator,
        private mixed $intervalStart,
        private mixed $intervalEnd
    ) {
    }

    public function getColumn(): string|Expression
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
     * @throws InvalidArgumentException
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1], $operands[2])) {
            throw new InvalidArgumentException("Operator '$operator' requires three operands.");
        }

        if (!is_string($operands[0]) && !($operands[0] instanceof Expression)) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires column to be string or ExpressionInterface."
            );
        }

        return new self($operands[0], $operator, $operands[1], $operands[2]);
    }
}
