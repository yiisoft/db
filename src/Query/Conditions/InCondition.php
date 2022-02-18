<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions;

use Iterator;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Interface\InConditionInterface;
use Yiisoft\Db\Query\QueryInterface;

/**
 * Class InCondition represents `IN` condition.
 */
class InCondition implements InConditionInterface
{
    public function __construct(
        private mixed $column,
        private string $operator,
        private array|int|Iterator|QueryInterface $values
    ) {
    }

    public function getColumn(): array|string|Iterator
    {
        return $this->column;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValues(): array|int|Iterator|QueryInterface
    {
        return $this->values;
    }

    public static function fromArrayDefinition(string $operator, array $operands): self
    {
        if (!isset($operands[0], $operands[1])) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new static($operands[0], $operator, $operands[1]);
    }
}
