<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\SimpleConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class SimpleCondition represents a simple condition like `"column" operator value`.
 */
final class SimpleCondition implements SimpleConditionInterface
{
    public function __construct(
        private string|Expression|QueryInterface $column,
        private string $operator,
        private mixed $value
    ) {
    }

    public function getColumn(): string|Expression|QueryInterface
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
     * @throws InvalidArgumentException
     */
    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new self(self::validateColumn($operator, $operands[0]), $operator, $operands[1]);
    }

    private static function validateColumn(string $operator, mixed $operands): string|Expression|QueryInterface
    {
        if (
            !is_string($operands) &&
            !($operands instanceof Expression) &&
            !($operands instanceof QueryInterface)
        ) {
            throw new InvalidArgumentException(
                "Operator '$operator' requires column to be string, ExpressionInterface or QueryInterface."
            );
        }

        return $operands;
    }
}
