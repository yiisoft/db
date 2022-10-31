<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\SimpleConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function count;

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
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        if (
            !is_string($operands[0]) &&
            !($operands[0] instanceof Expression) &&
            !($operands[0] instanceof QueryInterface)
        ) {
            throw new InvalidArgumentException("Operator '$operator' requires column name as first operand.");
        }

        return new self($operands[0], $operator, $operands[1]);
    }
}
