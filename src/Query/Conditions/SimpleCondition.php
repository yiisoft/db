<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\SimpleConditionInterface;

use function count;

/**
 * Class SimpleCondition represents a simple condition like `"column" operator value`.
 */
class SimpleCondition implements SimpleConditionInterface
{
    public function __construct(
        private string|array|ExpressionInterface $column,
        private string $operator,
        private array|int|string|Iterator|ExpressionInterface|null $value
    ) {
    }

    public function getColumn(): string|array|ExpressionInterface
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): array|int|string|Iterator|ExpressionInterface|null
    {
        return $this->value;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static($operands[0], $operator, $operands[1]);
    }
}
